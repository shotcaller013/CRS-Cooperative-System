<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_types', function (Blueprint $table) {
            // Amount cap method
            $table->enum('amount_cap_method', ['fixed', 'salary_multiplier', 'both'])
                  ->default('fixed')->after('max_amount');
            $table->decimal('salary_multiplier', 5, 2)->nullable()->after('amount_cap_method');

            // Rate range (officer can override within band)
            $table->decimal('annual_rate_min', 6, 4)->nullable()->after('annual_rate');
            $table->decimal('annual_rate_max', 6, 4)->nullable()->after('annual_rate_min');
            // annual_rate becomes the default rate

            // Qualifications
            $table->json('allowed_emp_statuses')->nullable()->after('annual_rate_max');
            $table->decimal('min_share_capital', 12, 2)->default(0)->after('allowed_emp_statuses');
            $table->unsignedInteger('min_tenure_months')->default(0)->after('min_share_capital');
            $table->boolean('allow_concurrent')->default(false)->after('min_tenure_months');
            $table->decimal('penalty_rate', 6, 4)->default(0.02)->after('allow_concurrent');

            // Rename for clarity
            $table->renameColumn('annual_rate', 'annual_rate_default');
        });
    }

    public function down(): void
    {
        Schema::table('loan_types', function (Blueprint $table) {
            $table->renameColumn('annual_rate_default', 'annual_rate');
            $table->dropColumn([
                'amount_cap_method', 'salary_multiplier',
                'annual_rate_min', 'annual_rate_max',
                'allowed_emp_statuses', 'min_share_capital',
                'min_tenure_months', 'allow_concurrent', 'penalty_rate',
            ]);
        });
    }
};
