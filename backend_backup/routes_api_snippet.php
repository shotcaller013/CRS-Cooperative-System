<?php

// routes/api.php
// Paste this inside your existing api.php after the sanctum auth middleware group

use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\LoanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // ── Members ──────────────────────────────────────────────
    Route::get('members/dropdown', [MemberController::class, 'dropdown']);
    Route::apiResource('members', MemberController::class);

    // ── Loans ────────────────────────────────────────────────
    Route::get('loans/pipeline',              [LoanController::class, 'pipeline']);
    Route::post('loans/calculate',            [LoanController::class, 'calculate']);
    Route::get('loan-types',                  [LoanController::class, 'loanTypes']);
    Route::post('loans/{loan}/approve',       [LoanController::class, 'approve']);
    Route::apiResource('loans', LoanController::class);

});
