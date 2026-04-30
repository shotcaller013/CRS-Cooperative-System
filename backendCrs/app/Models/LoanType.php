<?php
// app/Models/LoanType.php  (full replacement)
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanType extends Model
{
    protected $fillable = [
        'code', 'label',
        'min_amount', 'max_amount',
        'amount_cap_method', 'salary_multiplier',
        'min_term', 'max_term',
        'annual_rate_default', 'annual_rate_min', 'annual_rate_max',
        'allowed_emp_statuses',
        'min_share_capital', 'min_tenure_months',
        'allow_concurrent', 'penalty_rate',
        'is_active',
    ];

    protected $casts = [
        'min_amount'            => 'decimal:2',
        'max_amount'            => 'decimal:2',
        'salary_multiplier'     => 'decimal:2',
        'annual_rate_default'   => 'decimal:4',
        'annual_rate_min'       => 'decimal:4',
        'annual_rate_max'       => 'decimal:4',
        'allowed_emp_statuses'  => 'array',
        'min_share_capital'     => 'decimal:2',
        'penalty_rate'          => 'decimal:4',
        'allow_concurrent'      => 'boolean',
        'is_active'             => 'boolean',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function approvalThresholds(): HasMany
    {
        return $this->hasMany(ApprovalThreshold::class)->orderBy('sequence');
    }

    // Compute effective max amount for a given monthly salary
    public function computeMaxAmount(float $monthlySalary): float
    {
        return match ($this->amount_cap_method) {
            'salary_multiplier' => $monthlySalary * (float) $this->salary_multiplier,
            'both'              => min(
                                       (float) $this->max_amount,
                                       $monthlySalary * (float) $this->salary_multiplier
                                   ),
            default             => (float) $this->max_amount,
        };
    }
}
