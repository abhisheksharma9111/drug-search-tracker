<?php

namespace App\Http\Controllers;

use App\Models\UserMedication;
use App\Services\RxNormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Routing\Controller as BaseController;

class UserMedicationController extends BaseController
{
    protected $rxNormService;

    public function __construct(RxNormService $rxNormService)
    {
        $this->rxNormService = $rxNormService;
        $this->middleware('auth:sanctum');
    }

    public function handleMissingToken()
    {
        return response()->json([
            'success' => false,
            'message' => 'Authentication token is missing',
            'error' => 'missing_token',
            'solution' => 'Please include your API token in the Authorization header'
        ], 401);
    }

    public function index()
    {
        try {
            $medications = Auth::user()->medications;


            if ($medications->isEmpty()) {
                return response()->json([
                    'message' => 'No medications found'
                ], 404);
            }

            $medications = $medications->map(function ($medication) {
                return [
                    'id' => $medication->id,
                    'rxcui' => $medication->rxcui,
                    'name' => $medication->name,
                    'ingredients' => $medication->base_names,
                    'forms' => $medication->dosage_forms,
                    'status' => $medication->status,
                    'created_at' => $medication->created_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json($medications, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch medications', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to retrieve medications'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'rxcui' => 'required|string|min:1|max:20|regex:/^\d+$/'
            ]);

            $user = Auth::user();
            $rxcui = $validated['rxcui'];

            if ($user->medications()->where('rxcui', $rxcui)->exists()) {
                throw new \Exception('This medication already exists in your list', 409);
            }

            if (!$this->rxNormService->validateRxcui($rxcui)) {
                throw new \Exception('Invalid RxCUI - not found in RxNorm database', 404);
            }

            $drugDetails = $this->rxNormService->getDrugDetails($rxcui);
            $drugName = $this->rxNormService->getDrugName($rxcui)
                ?? $drugDetails['name']
                ?? 'Unknown Drug';

               
            $medication = $user->medications()->create([
                'rxcui' => $rxcui,
                'name' => $drugName,
                'base_names' => $drugDetails['base_names'] ?? null,
                'dosage_forms' => $drugDetails['dosage_forms'] ?? null,
                'status' => (string)$drugDetails['rxcui_status'] ?? 'unknown'
            ]);

            return response()->json([
                'message' => 'Medication added successfully',
                'data' => [
                    'id' => $medication->id,
                    'rxcui' => $medication->rxcui,
                    'name' => $medication->name,
                    'ingredients' => $medication->base_names,
                    'forms' => $medication->dosage_forms,
                    'status' => $medication->status,
                    'created_at' => $medication->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Medication store failed', [
                'user_id' => Auth::id(),
                'rxcui' => $request->rxcui ?? null,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);

            $statusCode = method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : ($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);

            return response()->json([
                'message' => 'Failed to add medication',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $statusCode);
        }
    }

    public function destroy($rxcui)
    {
        try {
            $medication = Auth::user()->medications()
                ->where('rxcui', $rxcui)
                ->firstOrFail();

            $medication->delete();

            return response()->json([
                'message' => 'Medication deleted successfully',
                'deleted_rxcui' => $rxcui
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Medication not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Medication deletion failed', [
                'user_id' => Auth::id(),
                'rxcui' => $rxcui,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to delete medication'
            ], 500);
        }
    }



}