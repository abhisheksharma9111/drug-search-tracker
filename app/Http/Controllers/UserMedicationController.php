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

    public function index()
    {
        try {
            return response()->json(Auth::user()->medications, 200);
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
            $validated = $this->validateRequest($request);
            $user = Auth::user();
            $rxcui = $validated['rxcui'];

            $this->checkForDuplicate($user, $rxcui);
            $this->validateRxCui($rxcui);

            $medication = $this->createMedication($user, $rxcui);

            return $this->successResponse($medication);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Medication store failed', [
                'user_id' => Auth::id(),
                'rxcui' => $request->rxcui ?? null,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'message' => 'Failed to add medication',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], $this->getStatusCodeForException($e));
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

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'rxcui' => 'required|string|min:1|max:20'
        ]);
    }

    protected function checkForDuplicate($user, string $rxcui): void
    {
        if ($user->medications()->where('rxcui', $rxcui)->exists()) {
            throw new \Exception('This medication already exists in your list', 409);
        }
    }

    protected function validateRxCui(string $rxcui): void
    {
        if (!preg_match('/^\d+$/', $rxcui)) {
            throw ValidationException::withMessages([
                'rxcui' => ['The RxCUI must be a valid numeric identifier']
            ]);
        }

        if (!$this->rxNormService->validateRxcui($rxcui)) {
            throw new \Exception('Invalid RxCUI - not found in RxNorm database', 404);
        }
    }

    protected function createMedication($user, string $rxcui): UserMedication
    {
        $drugDetails = $this->rxNormService->getDrugDetails($rxcui);
        $drugName = $this->rxNormService->getDrugName($rxcui) 
                   ?? $drugDetails['name'] 
                   ?? 'Unknown Drug';

        return $user->medications()->create([
            'rxcui' => $rxcui,
            'name' => $drugName,
            'base_names' => $drugDetails['base_names'] ?? [],
            'dosage_forms' => $drugDetails['dosage_forms'] ?? [],
            'status' => $drugDetails['rxcui_status'] ?? 'unknown'
        ]);
    }

    protected function successResponse(UserMedication $medication)
    {
        return response()->json([
            'message' => 'Medication added successfully',
            'data' => [
                'id' => $medication->id,
                'rxcui' => $medication->rxcui,
                'name' => $medication->name,
                'ingredients' => $medication->base_names,
                'forms' => $medication->dosage_forms,
                'status' => $medication->status,
                'created_at' => $medication->created_at->toDateTimeString()
            ]
        ], 201);
    }

    protected function getStatusCodeForException(\Exception $e): int
    {
        return match($e->getCode()) {
            404 => 404,
            409 => 409,
            default => 500
        };
    }
}