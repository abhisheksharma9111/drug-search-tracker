<?php


use App\Http\Controllers\UserController;
use App\Http\Controllers\DrugSearchController;
use App\Http\Controllers\UserMedicationController;
use Illuminate\Support\Facades\Route;


Route::get('missing-token', [UserMedicationController::class, 'handleMissingToken']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/drugs/search', [DrugSearchController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/medications', [UserMedicationController::class, 'index']);
    Route::post('/user/medications', [UserMedicationController::class, 'store']);
    Route::delete('/user/medications/{rxcui}', [UserMedicationController::class, 'destroy']);
});