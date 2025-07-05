<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class RxNormService
{
    private $baseUrl = 'https://rxnav.nlm.nih.gov/REST';
    private $timeout = 15;
    private $maxRetries = 3;
    private $retryDelay = 500;

    public function searchDrugs(string $drugName)
    {
        $cacheKey = "drug_search_" . md5($drugName);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($drugName) {
            try {
                $response = Http::timeout($this->timeout)
                    ->retry($this->maxRetries, $this->retryDelay)
                    ->get("{$this->baseUrl}/drugs.json", [
                        'name' => $drugName,
                        'tty' => 'SBD'
                    ]);

                if (!$response->successful()) {
                    Log::warning('RxNorm API request failed', [
                        'status' => $response->status(),
                        'drugName' => $drugName
                    ]);
                    return collect();
                }

                $data = $response->json();

                if (empty($data['drugGroup']['conceptGroup'])) {
                    return collect();
                }

                return collect($data['drugGroup']['conceptGroup'])
                    ->flatMap(function ($group) {
                        return $group['conceptProperties'] ?? [];
                    })
                    ->take(5)
                    ->map(function ($drug) {
                        return $this->formatDrugResult($drug);
                    })
                    ->filter()
                    ->values();

            } catch (RequestException $e) {
                Log::error('Drug search failed', [
                    'drug' => $drugName,
                    'error' => $e->getMessage()
                ]);
                return collect();
            }
        });
    }

    public function getDrugDetails(string $rxcui): array
    {
        if (!$this->validateRxcui($rxcui)) {
            throw new \InvalidArgumentException("Invalid RxCUI format");
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->maxRetries, $this->retryDelay)
                ->get("{$this->baseUrl}/rxcui/{$rxcui}/historystatus.json");

            if (!$response->successful()) {
                throw new \RuntimeException("API request failed with status: {$response->status()}");
            }

            $data = $response->json();

            if (!isset($data['rxcuiStatusHistory'])) {
                throw new \RuntimeException("Invalid RxNorm API response structure");
            }

            return [
                'base_names' => $this->extractBaseNames($data['rxcuiStatusHistory'] ?? []),
                'dosage_forms' => $this->extractDosageForms($data['rxcuiStatusHistory'] ?? []),
                'rxcui_status' => $data['rxcuiStatusHistory']['metaData']['status'] ?? 'unknown'
            ];

        } catch (RequestException $e) {
            Log::error('Failed to get drug details', [
                'rxcui' => $rxcui,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Could not retrieve drug details from RxNorm");
        }
    }

    protected function formatDrugResult(array $drug): ?array
    {
        if (empty($drug['rxcui'])) {
            return null;
        }

        try {
            $details = $this->getDrugDetails($drug['rxcui']);
            $name = $this->getDrugName($drug['rxcui']) ?? $drug['name'] ?? 'Unknown Drug';

            return [
                'rxcui' => $drug['rxcui'],
                'name' => $name,
                'base_names' => $details['base_names'],
                'dosage_forms' => $details['dosage_forms'],
                'status' => $details['rxcui_status']
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to format drug result', [
                'rxcui' => $drug['rxcui'] ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function extractBaseNames(array $rxcuiStatusHistory): array
    {
        if (empty($rxcuiStatusHistory)) {
            return [];
        }

        $baseNames = [];

        // Check all possible locations for base names
        $locations = [
            $rxcuiStatusHistory['definitionalFeatures']['ingredientAndStrength'] ?? [],
            $rxcuiStatusHistory['attributes']['ingredientAndStrength'] ?? [],
            $rxcuiStatusHistory['derivedConcepts']['ingredientConcept'] ?? []
        ];

        foreach ($locations as $source) {
            if (!empty($source)) {
                if (isset($source[0]['baseName'])) {
                    $baseNames = array_merge($baseNames, array_column($source, 'baseName'));
                } elseif (isset($source[0]['ingredientName'])) {
                    $baseNames = array_merge($baseNames, array_column($source, 'ingredientName'));
                }
            }
        }

        // If no names found, try alternative API
        if (empty($baseNames)) {
            $rxcui = $rxcuiStatusHistory['metaData']['rxcui'] ?? null;
            if ($rxcui) {
                $baseNames = $this->getAlternativeIngredientNames($rxcui);
            }
        }

        return array_values(array_unique(array_filter($baseNames)));
    }

    protected function extractDosageForms(array $rxcuiStatusHistory): array
    {
        if (empty($rxcuiStatusHistory)) {
            return [];
        }

        $forms = [];

        // Check all possible locations for dosage forms
        $locations = [
            $rxcuiStatusHistory['definitionalFeatures']['doseFormGroupConcept'] ?? [],
            $rxcuiStatusHistory['attributes']['doseFormGroupConcept'] ?? [],
            $rxcuiStatusHistory['definitionalFeatures']['doseFormConcept'] ?? []
        ];

        foreach ($locations as $source) {
            if (!empty($source)) {
                if (isset($source[0]['doseFormGroupName'])) {
                    $forms = array_merge($forms, array_column($source, 'doseFormGroupName'));
                } elseif (isset($source[0]['doseFormName'])) {
                    $forms = array_merge($forms, array_column($source, 'doseFormName'));
                }
            }
        }

        // If no forms found, try alternative API
        if (empty($forms)) {
            $rxcui = $rxcuiStatusHistory['metaData']['rxcui'] ?? null;
            if ($rxcui) {
                $forms = $this->getAlternativeDosageForms($rxcui);
            }
        }

        return array_values(array_unique(array_filter($forms)));
    }

    protected function getAlternativeIngredientNames(string $rxcui): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->maxRetries, $this->retryDelay)
                ->get("{$this->baseUrl}/rxcui/{$rxcui}/allrelated.json", [
                    'tty' => 'IN+PIN'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['allRelatedGroup']['conceptGroup'] ?? [])
                    ->flatMap(function ($group) {
                        return $group['conceptProperties'] ?? [];
                    })
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
        } catch (RequestException $e) {
            Log::debug('Alternative ingredient fetch failed', ['rxcui' => $rxcui]);
        }

        return [];
    }

    protected function getAlternativeDosageForms(string $rxcui): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->maxRetries, $this->retryDelay)
                ->get("{$this->baseUrl}/rxcui/{$rxcui}/property.json", [
                    'propName' => 'DOSE_FORM'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return array_filter([$data['propConceptGroup']['propConcept'][0]['propValue'] ?? null]);
            }
        } catch (RequestException $e) {
            Log::debug('Alternative dosage form fetch failed', ['rxcui' => $rxcui]);
        }

        return [];
    }

    public function getDrugName(string $rxcui): ?string
    {
        if (!$this->validateRxcui($rxcui)) {
            return null;
        }



        $cacheKey = "drug_name_" . md5($rxcui);

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($rxcui) {
            try {
                $response = Http::timeout($this->timeout)
                    ->retry($this->maxRetries, $this->retryDelay)
                    ->get("{$this->baseUrl}/rxcui/{$rxcui}/related.json", [
                        'tty' => 'SBD' 
                    ]);

                if (!$response->successful()) {
                    return null;
                }

                $data = $response->json();
                

                // Search through conceptGroups for SBD entries
                foreach ($data['drugGroup']['conceptGroup'] ?? [] as $conceptGroup) {
                   
                    if (($conceptGroup['tty'] ?? null) === 'SBD') {
                        foreach ($conceptGroup['conceptProperties'] ?? [] as $concept) {
                            
                            if ($concept['rxcui'] == (int)$rxcui) {
                                return $concept['name'] ?? $concept['synonym'] ?? null;
                            }
                        }
                    }
                }

                foreach ($data['relatedGroup']['conceptGroup'] ?? [] as $conceptGroup) {
                   
                    if (($conceptGroup['tty'] ?? null) === 'SBD') {
                        foreach ($conceptGroup['conceptProperties'] ?? [] as $concept) {
                           
                            if ($concept['rxcui'] == (int)$rxcui) {
                                return $concept['name'] ?? $concept['synonym'] ?? null;
                            }
                        }
                    }
                }
                return null;

            } catch (RequestException $e) {
                Log::warning('Drug name fetch failed', [
                    'rxcui' => $rxcui,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    public function validateRxcui(string $rxcui): bool
    {
        if (!preg_match('/^\d+$/', $rxcui)) {
            return false;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->maxRetries, $this->retryDelay)
                ->get("{$this->baseUrl}/rxcui/{$rxcui}/historystatus.json");

            return $response->successful();
        } catch (RequestException $e) {
            Log::warning('RxCUI validation failed', ['rxcui' => $rxcui]);
            return false;
        }
    }
}