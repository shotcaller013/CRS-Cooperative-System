<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_no',
        'last_name',
        'first_name',
        'middle_name',
        'address',
        'contact',
        'email',
        'company',
        'branch',
        'department',
        'status',
        'position',
        'supervisor',
        'date_hired',
        'monthly_salary',
        'share_capital',
        'member_status',
        'photo_url',
        'created_by',
    ];

    protected $casts = [
        'date_hired'     => 'date',
        'monthly_salary' => 'decimal:2',
        'share_capital'  => 'decimal:2',
    ];

    // ── Relationships ────────────────────────────────────────

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id');
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id')->where('status', 'ACTIVE');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('member_status', 'ACTIVE');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('member_no', 'like', "%{$term}%")
              ->orWhere('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
        });
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
