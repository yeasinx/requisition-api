<?php

use App\Http\Controllers\ApprovalStepController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequisitionAttachmentController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// 1. Public Auth Routes
Route::post('/login', [AuthController::class, 'login']);

// 2. Protected Routes (Requires Sanctum Bearer Token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Current User
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // User Management (Admin / HR)
    Route::apiResource('users', UserController::class);

    // Requisitions Management
    Route::apiResource('requisitions', RequisitionController::class);

    // Requisition Attachments (attachment must belong to the requisition)
    Route::scopeBindings()->group(function () {
        Route::post('requisitions/{requisition}/attachments', [RequisitionAttachmentController::class, 'store']);
        Route::get('requisitions/{requisition}/attachments/{attachment}', [RequisitionAttachmentController::class, 'download']);
        Route::delete('requisitions/{requisition}/attachments/{attachment}', [RequisitionAttachmentController::class, 'destroy']);
    });

    // Approval / Denial Actions
    Route::post('requisitions/{requisition}/approve', [ApprovalStepController::class, 'approve']);
    Route::post('requisitions/{requisition}/deny', [ApprovalStepController::class, 'deny']);

    // System Settings (Approver Role Configuration)
    Route::get('settings', [SystemSettingsController::class, 'show']);
    Route::put('settings', [SystemSettingsController::class, 'update']);
});
