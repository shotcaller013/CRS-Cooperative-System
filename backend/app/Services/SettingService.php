<?php
// app/Services/SettingService.php
namespace App\Services;

use App\Models\ApprovalThreshold;
use App\Models\CoopProfile;
use App\Models\LoanType;
use App\Models\SystemPreference;
use Illuminate\Support\Facades\DB;

class SettingService
{
    // ── Coop Profile ──────────────────────────────────────────

    public function getProfile(): CoopProfile
    {
        return CoopProfile::current();
    }

    public function updateProfile(array $data): CoopProfile
    {
        $profile = CoopProfile::current();
        $profile->update($data);
        return $profile->fresh();
    }

    // ── Loan Types ────────────────────────────────────────────

    public function getLoanTypes(): \Illuminate\Database\Eloquent\Collection
    {
        return LoanType::with('approvalThresholds')->orderBy('label')->get();
    }

    public function findLoanType(int $id): LoanType
    {
        return LoanType::with('approvalThresholds')->findOrFail($id);
    }

    public function createLoanType(array $data): LoanType
    {
        return DB::transaction(function () use ($data) {
            $thresholds = $data['thresholds'] ?? [];
            unset($data['thresholds']);

            $loanType = LoanType::create($data);
            $this->syncThresholds($loanType, $thresholds);

            return $loanType->load('approvalThresholds');
        });
    }

    public function updateLoanType(LoanType $loanType, array $data): LoanType
    {
        return DB::transaction(function () use ($loanType, $data) {
            $thresholds = $data['thresholds'] ?? null;
            unset($data['thresholds']);

            $loanType->update($data);

            if ($thresholds !== null) {
                $this->syncThresholds($loanType, $thresholds);
            }

            return $loanType->load('approvalThresholds');
        });
    }

    public function deleteLoanType(LoanType $loanType): void
    {
        // Don't delete if active loans exist
        if ($loanType->loans()->whereIn('status', ['ACTIVE', 'APPROVED', 'PENDING'])->exists()) {
            throw new \RuntimeException('Cannot delete a loan type with active loans.');
        }
        $loanType->delete();
    }

    private function syncThresholds(LoanType $loanType, array $thresholds): void
    {
        $loanType->approvalThresholds()->delete();
        foreach ($thresholds as $t) {
            ApprovalThreshold::create(array_merge($t, ['loan_type_id' => $loanType->id]));
        }
    }

    // ── System Preferences ────────────────────────────────────

    public function getAllPreferences(): array
    {
        return SystemPreference::all()
            ->groupBy('group')
            ->map(fn($g) => $g->pluck('value', 'key'))
            ->toArray();
    }

    public function updatePreferences(array $data): void
    {
        foreach ($data as $key => $value) {
            SystemPreference::set($key, $value);
        }
    }

    // ── Next required approval for a loan ────────────────────

    public function getNextApprovalLevel(int $loanId): ?ApprovalThreshold
    {
        $loan    = \App\Models\Loan::with('loanType.approvalThresholds', 'approvals')->findOrFail($loanId);
        $doneSeq = $loan->approvals
            ->where('decision', 'approved')
            ->pluck('sequence')
            ->toArray();

        return $loan->loanType->approvalThresholds
            ->filter(fn($t) =>
                !in_array($t->sequence, $doneSeq) &&
                (float) $loan->amount >= (float) $t->amount_from &&
                ($t->amount_to === null || (float) $loan->amount <= (float) $t->amount_to)
            )
            ->sortBy('sequence')
            ->first();
    }
}
