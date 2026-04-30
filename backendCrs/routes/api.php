    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\MemberController;
    use App\Http\Controllers\Api\LoanController;
    use App\Http\Controllers\Api\SettingController;
    use App\Http\Controllers\Api\LoanTypeSettingController;
    use App\Http\Controllers\Api\PaymentController;
    use App\Http\Controllers\Api\EligibilityController;
    use App\Http\Controllers\Api\ReportController;
    

    Route::prefix('v1')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PUBLIC ROUTES (NO AUTH)
        |--------------------------------------------------------------------------
        */

        Route::post('login', [AuthController::class, 'login']);

        /*
        |--------------------------------------------------------------------------
        | PROTECTED ROUTES (SANCTUM AUTH)
        |--------------------------------------------------------------------------
        */

        Route::middleware('auth:sanctum')->group(function () {

            /*
            |------------------------
            | AUTH
            |------------------------
            */
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);

            /*
            |------------------------
            | MEMBERS
            |------------------------
            */
            Route::get('members/dropdown', [MemberController::class, 'dropdown']);
            Route::apiResource('members', MemberController::class);

            /*
            |------------------------
            | LOANS
            |------------------------
            */
            Route::get('loans/pipeline', [LoanController::class, 'pipeline']);
            Route::post('loans/calculate', [LoanController::class, 'calculate']);
            Route::get('loan-types', [LoanController::class, 'loanTypes']);
            Route::post('loans/{loan}/approve', [LoanController::class, 'approve']);
            Route::apiResource('loans', LoanController::class);

            /*
            |------------------------
            | LOAN ELIGIBILITY
            |------------------------
            */
            Route::post('loans/eligibility-check', [EligibilityController::class, 'check']);

            /*
            |------------------------
            | PAYMENTS
            |------------------------
            */
            Route::get('loans/{loan}/payments', [PaymentController::class, 'byLoan']);
            Route::apiResource('payments', PaymentController::class)
                ->only(['index', 'store', 'show']);

            /*
            |------------------------
            | SETTINGS
            |------------------------
            */
            Route::prefix('settings')->group(function () {

                Route::get('profile', [SettingController::class, 'getProfile']);
                Route::put('profile', [SettingController::class, 'updateProfile']);

                Route::get('preferences', [SettingController::class, 'getPreferences']);
                Route::put('preferences', [SettingController::class, 'updatePreferences']);

                Route::apiResource('loan-types', LoanTypeSettingController::class);

            });

            /*
            |------------------------
            | REPORTS
            |------------------------
            */
            Route::prefix('reports')->group(function () {

                Route::get('collection', [ReportController::class, 'collection']);
                Route::get('aging', [ReportController::class, 'aging']);
                Route::get('outstanding', [ReportController::class, 'outstanding']);

            });

        });

    });