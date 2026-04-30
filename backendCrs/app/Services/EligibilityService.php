<?php
// app/Services/EligibilityService.php
namespace App\Services;

use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Member;

class EligibilityService
{
    /**
     * Run all eligibility checks for a member + loan type + requested amount.
     *
     * Returns:
     *   ['eligible' => bool, 'checks' => [['rule'=>'...','pass'=>bool,'reason'=>'...']]]
     */
    public function check(Member $member, LoanType $loanType, float $amount): array
    {
        $checks = [];

        // 1 — Employment status
        $allowedStatuses = $loanType->allowed_emp_statuses ?? [];
        if (empty($allowedStatuses) || in_array($member->employment_status, $allowedStatuses)) {
            $checks[] = ['rule' => 'employment_status', 'pass' => true, 'reason' => null];
        } else {
            $checks[] = ['rule' => 'employment_status', 'pass' => false,
                'reason' => "Employment status '{$member->employment_status}' is not eligible for this loan type."];
        }

        // 2 — Tenure
        $tenureMonths = $member->date_hired
            ? (int) now()->diffInMonths($member->date_hired)
            : 0;
        $minTenure = (int) $loanType->min_tenure_months;
        $checks[] = $tenureMonths >= $minTenure
            ? ['rule' => 'tenure', 'pass' => true, 'reason' => null]
            : ['rule' => 'tenure', 'pass' => false,
               'reason' => "Minimum {$minTenure} months of tenure required (member has {$tenureMonths} months)."];

        // 3 — Share capital
        $shareCapital = (float) ($member->share_capital ?? 0);
        $minCapital   = (float) $loanType->min_share_capital;
        $checks[] = $shareCapital >= $minCapital
            ? ['rule' => 'share_capital', 'pass' => true, 'reason' => null]
            : ['rule' => 'share_capital', 'pass' => false,
               'reason' => "Minimum share capital of ₱" . number_format($minCapital, 2) . " required."];

        // 4 — Amount cap
        $salary  = (float) ($member->monthly_salary ?? 0);
        $maxLoan = $loanType->computeMaxAmount($salary);
        $checks[] = $amount <= $maxLoan
            ? ['rule' => 'amount_cap', 'pass' => true, 'reason' => null]
            : ['rule' => 'amount_cap', 'pass' => false,
               'reason' => "Requested amount exceeds maximum loanable of ₱" . number_format($maxLoan, 2) . "."];

        // 5 — Concurrent loans
        if (!$loanType->allow_concurrent) {
            $hasActive = Loan::where('member_id', $member->id)
                ->whereIn('status', ['ACTIVE', 'APPROVED', 'PENDING'])
                ->exists();
            $checks[] = $hasActive
                ? ['rule' => 'concurrent', 'pass' => false,
                   'reason' => 'Member already has an active loan. Concurrent loans are not allowed for this type.']
                : ['rule' => 'concurrent', 'pass' => true, 'reason' => null];
        } else {
            $checks[] = ['rule' => 'concurrent', 'pass' => true, 'reason' => null];
        }

        $eligible = collect($checks)->every(fn ($c) => $c['pass']);

        return compact('eligible', 'checks');
    }
}
