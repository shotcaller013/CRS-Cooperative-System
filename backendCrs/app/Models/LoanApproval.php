<?php
// app/Models/LoanApproval.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanApproval extends Model
{
    protected $fillable = [
        'loan_id', 'level', 'sequence',
        'approver_id', 'approver_name',
        'decision', 'notes', 'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
