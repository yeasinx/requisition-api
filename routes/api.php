<?php

use App\Http\Controllers\ApprovalStepController;
use App\Http\Controllers\RequisitionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. Get an authenticated user (useful for React to check role/session)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 2. Protected Requisition Routes
Route::middleware('auth:sanctum')->group(function () {

    // Standard CRUD for Requisitions
    Route::apiResource('requisitions', RequisitionController::class);

    // Specific Actions for Approvals
   Route::post('requisitions/{requisition}/approve', [ApprovalStepController::class, 'approve']);
   Route::post('requisitions/{requisition}/deny', [ApprovalStepController::class, 'deny']);
});
