<?php

namespace App\Services;

use App\Models\AmortizationSchedule;
use App\Models\Loan;
use App\Models\LoanType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanService
{
    // ── Query ─────────────────────────────────────────────────

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Loan::with(['member', 'loanType']);

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['member_id'])) {
            $query->forMember((int) $filters['member_id']);
        }

        if (!empty($filters['loan_type_id'])) {
            $query->where('loan_type_id', $filters['loan_type_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('loan_no', 'like', '%' . $filters['search'] . '%');
        }

        $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function find(int $id): Loan
    {
        return Loan::with([
            'member', 'loanType', 'coMaker1', 'coMaker2',
            'amortizationSchedules', 'creator',
        ])->findOrFail($id);
    }

    public function pipeline(): array
    {
        $loans = Loan::with(['member', 'loanType'])
            ->orderByDesc('updated_at')
            ->get();

        $pipeline = [];
        foreach (['DRAFT', 'PENDING', 'APPROVED', 'ACTIVE', 'CLOSED', 'REJECTED'] as $status) {
            $pipeline[$status] = $loans->where('status', $status)->values();
        }
        return $pipeline;
    }

    // ── Mutations ─────────────────────────────────────────────

    public function create(array $data): Loan
    {
        $loanType = LoanType::findOrFail($data['loan_type_id']);
        $rate     = (float) ($data['annual_rate'] ?? $loanType->annual_rate);
        $calc     = $this->computeSchedule(
            (float) $data['amount'],
            (int)   $data['term_months'],
            $data['frequency'],
            $rate
        );

        $loanNo = $this->generateLoanNo();
        $dueDates = !empty($data['first_due_date'])
            ? $this->generateDueDates($data['first_due_date'], $calc['n_periods'], $data['frequency'])
            : [];

        DB::transaction(function () use (&$loan, $data, $calc, $loanNo, $rate, $dueDates) {
            $loan = Loan::create([
                'loan_no'           => $loanNo,
                'member_id'         => $data['member_id'],
                'loan_type_id'      => $data['loan_type_id'],
                'amount'            => $data['amount'],
                'term_months'       => $data['term_months'],
                'frequency'         => $data['frequency'],
                'annual_rate'       => $rate,
                'purpose'           => $data['purpose'] ?? null,
                'co_maker_1_id'     => $data['co_maker_1_id'] ?? null,
                'co_maker_2_id'     => $data['co_maker_2_id'] ?? null,
                'status'            => $data['status'] ?? 'DRAFT',
                'total_payment'     => $calc['total_payment'],
                'total_interest'    => $calc['total_interest'],
                'n_periods'         => $calc['n_periods'],
                'first_payment_amt' => $calc['first_payment'],
                'last_payment_amt'  => $calc['last_payment'],
                'application_date'  => $data['application_date'] ?? now()->toDateString(),
                'first_due_date'    => $data['first_due_date'] ?? null,
                'end_date'          => !empty($dueDates) ? end($dueDates) : null,
                'notes'             => $data['notes'] ?? null,
                'created_by'        => Auth::id(),
            ]);

            $schedules = [];
            foreach ($calc['schedule'] as $i => $row) {
                $schedules[] = [
                    'loan_id'    => $loan->id,
                    'period_no'  => $row['period'],
                    'due_date'   => $dueDates[$i] ?? null,
                    'principal'  => $row['principal'],
                    'interest'   => $row['interest'],
                    'amount_due' => $row['payment'],
                    'balance'    => $row['balance'],
                    'status'     => 'PENDING',
                    'paid_amount'=> 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            AmortizationSchedule::insert($schedules);
        });

        return $this->find($loan->id);
    }

    public function update(Loan $loan, array $data): Loan
    {
        $loan->update($data);
        return $this->find($loan->id);
    }

    public function approve(Loan $loan, array $data): Loan
    {
        $loan->update([
            'status'           => 'APPROVED',
            'approval_date'    => now()->toDateString(),
            'approved_by_hr'   => $data['approved_by_hr'] ?? null,
            'approved_by_coop' => $data['approved_by_coop'] ?? Auth::user()->name,
        ]);
        return $this->find($loan->id);
    }

    public function delete(Loan $loan): void
    {
        $loan->delete();
    }

    // ── Loan math ─────────────────────────────────────────────

    public function computeSchedule(
        float  $principal,
        int    $termMonths,
        string $frequency,
        float  $annualRate = 0.12
    ): array {
        $monthlyRate = $annualRate / 12;

        [$periodsPerMonth, $periodRateFactor] = match ($frequency) {
            'bimonthly' => [2, 0.5],
            'weekly'    => [4, 0.25],
            default     => [1, 1.0],
        };

        $nPeriods           = $termMonths * $periodsPerMonth;
        $principalPerPeriod = $principal / $nPeriods;
        $schedule           = [];
        $remaining          = $principal;
        $totalInterest      = 0.0;

        for ($i = 0; $i < $nPeriods; $i++) {
            $interest  = $remaining * $monthlyRate * $periodRateFactor;
            $payment   = $principalPerPeriod + $interest;
            $balance   = max(0, $remaining - $principalPerPeriod);

            $schedule[] = [
                'period'    => $i + 1,
                'principal' => round($principalPerPeriod, 2),
                'interest'  => round($interest, 2),
                'payment'   => round($payment, 2),
                'balance'   => round($balance, 2),
            ];

            $remaining    -= $principalPerPeriod;
            $totalInterest += $interest;
        }

        return [
            'schedule'       => $schedule,
            'n_periods'      => $nPeriods,
            'total_interest' => round($totalInterest, 2),
            'total_payment'  => round($principal + $totalInterest, 2),
            'first_payment'  => $schedule[0]['payment'],
            'last_payment'   => $schedule[$nPeriods - 1]['payment'],
        ];
    }

    private function generateLoanNo(): string
    {
        $year  = now()->year;
        $count = Loan::whereYear('created_at', $year)->withTrashed()->count() + 1;
        return 'LN-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    private function generateDueDates(string $firstDate, int $nPeriods, string $frequency): array
    {
        $dates   = [];
        $current = \Carbon\Carbon::parse($firstDate);
        for ($i = 0; $i < $nPeriods; $i++) {
            $dates[] = $current->toDateString();
            match ($frequency) {
                'bimonthly' => $current->addDays(15),
                'weekly'    => $current->addWeek(),
                default     => $current->addMonth(),
            };
        }
        return $dates;
    }
}
