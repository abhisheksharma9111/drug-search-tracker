<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\DrugSearchController;
use App\Http\Controllers\UserMedicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::post('/register', [UserController::class, 'register'])->name('api.register');
Route::post('/login', [UserController::class, 'login'])->name('api.login');
Route::get('/drugs/search', [DrugSearchController::class, 'search'])->name('api.drugs.search');

// Protected routes (require valid Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    // User medication routes
    Route::prefix('user/medications')->group(function () {
        Route::get('/', [UserMedicationController::class, 'index'])->name('api.medications.index');
        Route::post('/', [UserMedicationController::class, 'store'])->name('api.medications.store');
        Route::delete('/{rxcui}', [UserMedicationController::class, 'destroy'])->name('api.medications.destroy');
    });
    
    // Optional: Add token verification endpoint
    Route::get('/verify-token', function () {
        return response()->json(['valid' => true]);
    })->name('api.verify-token');
});