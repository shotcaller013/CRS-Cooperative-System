<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\LoanTypeSettingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\EligibilityController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login',  [AuthController::class, 'login']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me',      [AuthController::class, 'me']);

    Route::get('members/dropdown', [MemberController::class, 'dropdown']);
    Route::apiResource('members', MemberController::class);

    Route::get('loans/pipeline', [LoanController::class, 'pipeline']);
    Route::post('loans/calculate', [LoanController::class, 'calculate']);
    Route::get('loan-types', [LoanController::class, 'loanTypes']);
    Route::post('loans/{loan}/approve', [LoanController::class, 'approve']);
    Route::apiResource('loans', LoanController::class);

    // ── Settings (super-admin only, enforced in controller) ──
    Route::prefix('settings')->group(function () {
        Route::get('profile',              [SettingController::class, 'getProfile']);
        Route::put('profile',              [SettingController::class, 'updateProfile']);
        Route::get('preferences',          [SettingController::class, 'getPreferences']);
        Route::put('preferences',          [SettingController::class, 'updatePreferences']);
        Route::apiResource('loan-types',   LoanTypeSettingController::class);
    });

    // ── Eligibility check (pre-submission) ───────────────────
    Route::post('loans/eligibility-check', [EligibilityController::class, 'check']);

    // ── Payments ─────────────────────────────────────────────
    Route::get('loans/{loan}/payments',    [PaymentController::class, 'byLoan']);
    Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);

    // ── Reports ──────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('collection',  [ReportController::class, 'collection']);
        Route::get('aging',       [ReportController::class, 'aging']);
        Route::get('outstanding', [ReportController::class, 'outstanding']);
    });

});
