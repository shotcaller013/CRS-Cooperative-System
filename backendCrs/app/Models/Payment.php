<?php
// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'loan_id', 'schedule_id', 'amount_paid', 'payment_type',
        'or_number', 'payment_date', 'penalty_paid', 'balance_after',
        'notes', 'received_by',
    ];

    protected $casts = [
        'amount_paid'  => 'decimal:2',
        'penalty_paid' => 'decimal:2',
        'balance_after'=> 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AmortizationSchedule::class, 'schedule_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
