<?php
// app/Models/ApprovalThreshold.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalThreshold extends Model
{
    protected $fillable = [
        'loan_type_id', 'level', 'approver_role',
        'amount_from', 'amount_to', 'sequence',
    ];

    protected $casts = [
        'amount_from' => 'decimal:2',
        'amount_to'   => 'decimal:2',
    ];

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }
}
