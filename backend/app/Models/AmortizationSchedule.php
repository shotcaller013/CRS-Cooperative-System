<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmortizationSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'period_no', 'due_date',
        'principal', 'interest', 'amount_due', 'balance',
        'status', 'paid_amount', 'paid_date', 'or_number',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'paid_date'   => 'date',
        'principal'   => 'decimal:2',
        'interest'    => 'decimal:2',
        'amount_due'  => 'decimal:2',
        'balance'     => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
