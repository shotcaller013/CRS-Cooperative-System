<?php
namespace App\Services;

use App\Models\AmortizationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private function applyMemberFilters($query, array $filters): void
    {
        if (!empty($filters['company'])) {
            $query->where('members.company', $filters['company']);
        }
        if (!empty($filters['department'])) {
            $query->where('members.department', $filters['department']);
        }
        if (!empty($filters['loan_officer_id'])) {
            $query->where('loans.created_by', $filters['loan_officer_id']);
        }
        if (!empty($filters['loan_type_id'])) {
            $query->where('loans.loan_type_id', $filters['loan_type_id']);
        }
    }

    // ── 1. Collection Summary ────────────────────────────────

    public function collectionSummary(array $filters): array
    {
        $dateFrom = $this->resolveFrom($filters);
        $dateTo   = $this->resolveTo($filters);

        $base = AmortizationSchedule::query()
            ->join('loans',       'loans.id',        '=', 'amortization_schedules.loan_id')
            ->join('members',     'members.id',       '=', 'loans.member_id')
            ->join('loan_types',  'loan_types.id',    '=', 'loans.loan_type_id')
            ->whereBetween('amortization_schedules.due_date', [$dateFrom, $dateTo]);

        $this->applyMemberFilters($base, $filters);

        $byType = (clone $base)
            ->select([
                'loan_types.id as loan_type_id',
                'loan_types.label as loan_type_label',
                DB::raw('COUNT(*) as period_count'),
                DB::raw("SUM(CASE WHEN amortization_schedules.status='PAID' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw('SUM(amortization_schedules.amount_due) as expected'),
                DB::raw('SUM(amortization_schedules.paid_amount) as collected'),
                DB::raw('SUM(amortization_schedules.penalty_amount) as penalty'),
            ])
            ->groupBy('loan_types.id', 'loan_types.label')
            ->get()
            ->map(fn($r) => $this->mapRow($r, $r->loan_type_label));

        $byStatus = (clone $base)
            ->select([
                'loans.status as loan_status',
                DB::raw('COUNT(*) as period_count'),
                DB::raw("SUM(CASE WHEN amortization_schedules.status='PAID' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw('SUM(amortization_schedules.amount_due) as expected'),
                DB::raw('SUM(amortization_schedules.paid_amount) as collected'),
                DB::raw('SUM(amortization_schedules.penalty_amount) as penalty'),
            ])
            ->groupBy('loans.status')
            ->get()
            ->map(fn($r) => $this->mapRow($r, ucfirst(strtolower($r->loan_status)) . ' loans'));

        $totals = (clone $base)
            ->select([
                DB::raw('COUNT(*) as period_count'),
                DB::raw("SUM(CASE WHEN amortization_schedules.status='PAID' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw('SUM(amortization_schedules.amount_due) as expected'),
                DB::raw('SUM(amortization_schedules.paid_amount) as collected'),
                DB::raw('SUM(amortization_schedules.penalty_amount) as penalty'),
            ])->first();

        $totExpected  = (float) ($totals->expected ?? 0);
        $totCollected = (float) ($totals->collected ?? 0);

        return [
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'filters'        => $filters,
            'by_loan_type'   => $byType->values(),
            'by_loan_status' => $byStatus->values(),
            'summary' => [
                'total_expected'   => round($totExpected, 2),
                'total_collected'  => round($totCollected, 2),
                'total_shortfall'  => round(max(0, $totExpected - $totCollected), 2),
                'total_penalty'    => round((float) ($totals->penalty ?? 0), 2),
                'total_periods'    => (int) ($totals->period_count ?? 0),
                'paid_periods'     => (int) ($totals->paid_count ?? 0),
                'collection_rate'  => $totExpected > 0
                    ? round($totCollected / $totExpected * 100, 2) : 0,
            ],
        ];
    }

    private function mapRow($r, string $label): array
    {
        $expected  = (float) ($r->expected ?? 0);
        $collected = (float) ($r->collected ?? 0);
        return [
            'label'        => $label,
            'period_count' => (int) ($r->period_count ?? 0),
            'paid_count'   => (int) ($r->paid_count ?? 0),
            'expected'     => round($expected, 2),
            'collected'    => round($collected, 2),
            'shortfall'    => round(max(0, $expected - $collected), 2),
            'penalty'      => round((float) ($r->penalty ?? 0), 2),
            'rate'         => $expected > 0 ? round($collected / $expected * 100, 2) : 0,
        ];
    }

    // ── 2. Aging Report ──────────────────────────────────────

    public function agingReport(array $filters): array
    {
        $asOf = !empty($filters['as_of_date'])
            ? Carbon::parse($filters['as_of_date'])
            : Carbon::today();

        $query = AmortizationSchedule::query()
            ->join('loans',      'loans.id',       '=', 'amortization_schedules.loan_id')
            ->join('members',    'members.id',      '=', 'loans.member_id')
            ->join('loan_types', 'loan_types.id',   '=', 'loans.loan_type_id')
            ->leftJoin('users',  'users.id',        '=', 'loans.created_by')
            ->whereIn('amortization_schedules.status', ['OVERDUE', 'PARTIAL'])
            ->where('loans.status', 'ACTIVE')
            ->whereNotNull('amortization_schedules.due_date')
            ->where('amortization_schedules.due_date', '<', $asOf);

        $this->applyMemberFilters($query, $filters);

        $rows = $query->select([
            'members.first_name', 'members.last_name', 'members.member_no',
            'members.company', 'members.department',
            'loans.loan_no', 'loans.id as loan_id',
            'loan_types.label as loan_type_label',
            'users.name as loan_officer',
            'amortization_schedules.id as schedule_id',
            'amortization_schedules.period_no',
            'amortization_schedules.due_date',
            'amortization_schedules.amount_due',
            'amortization_schedules.paid_amount',
            'amortization_schedules.penalty_amount',
            'amortization_schedules.days_overdue',
            'amortization_schedules.status',
        ])->orderByDesc('amortization_schedules.days_overdue')->get();

        $buckets = ['0_30' => [], '31_60' => [], '61_90' => [], '90_plus' => []];

        foreach ($rows as $r) {
            $days    = (int) $r->days_overdue;
            $balance = (float) $r->amount_due - (float) $r->paid_amount;
            $row = [
                'member_name'     => "{$r->first_name} {$r->last_name}",
                'member_no'       => $r->member_no,
                'company'         => $r->company,
                'department'      => $r->department,
                'loan_no'         => $r->loan_no,
                'loan_type_label' => $r->loan_type_label,
                'loan_officer'    => $r->loan_officer,
                'period_no'       => $r->period_no,
                'due_date'        => $r->due_date,
                'amount_due'      => round((float) $r->amount_due, 2),
                'paid_amount'     => round((float) $r->paid_amount, 2),
                'balance'         => round($balance, 2),
                'penalty_amount'  => round((float) $r->penalty_amount, 2),
                'days_overdue'    => $days,
                'status'          => $r->status,
            ];
            match (true) {
                $days <= 30 => $buckets['0_30'][]   = $row,
                $days <= 60 => $buckets['31_60'][]  = $row,
                $days <= 90 => $buckets['61_90'][]  = $row,
                default     => $buckets['90_plus'][] = $row,
            };
        }

        $bSum = fn($k) => [
            'count'   => count($buckets[$k]),
            'balance' => round(collect($buckets[$k])->sum('balance'), 2),
            'penalty' => round(collect($buckets[$k])->sum('penalty_amount'), 2),
        ];

        return [
            'as_of_date' => $asOf->toDateString(),
            'filters'    => $filters,
            'buckets'    => $buckets,
            'summary' => [
                '0_30'          => $bSum('0_30'),
                '31_60'         => $bSum('31_60'),
                '61_90'         => $bSum('61_90'),
                '90_plus'       => $bSum('90_plus'),
                'total_overdue' => $rows->count(),
                'total_balance' => round($rows->sum(fn($r) => (float)$r->amount_due - (float)$r->paid_amount), 2),
                'total_penalty' => round($rows->sum(fn($r) => (float)$r->penalty_amount), 2),
            ],
        ];
    }

    // ── 3. Outstanding Balance ───────────────────────────────

    public function outstandingBalance(array $filters): array
    {
        $query = \App\Models\Loan::query()
            ->join('members',    'members.id',    '=', 'loans.member_id')
            ->join('loan_types', 'loan_types.id', '=', 'loans.loan_type_id')
            ->leftJoin('users',  'users.id',      '=', 'loans.created_by')
            ->leftJoin(
                DB::raw('(SELECT loan_id,
                    SUM(amount_due) as total_due,
                    SUM(paid_amount) as total_paid,
                    SUM(CASE WHEN status="OVERDUE" THEN amount_due - paid_amount ELSE 0 END) as overdue_balance,
                    SUM(penalty_amount) as total_penalty,
                    COUNT(CASE WHEN status="OVERDUE" THEN 1 END) as overdue_count
                    FROM amortization_schedules GROUP BY loan_id) as sched'),
                'sched.loan_id', '=', 'loans.id'
            )
            ->whereIn('loans.status', ['ACTIVE', 'APPROVED']);

        $this->applyMemberFilters($query, $filters);

        if (!empty($filters['status'])) {
            $query->where('loans.status', $filters['status']);
        }

        $rows = $query->select([
            'loans.id', 'loans.loan_no', 'loans.status as loan_status',
            'loans.amount as original_amount',
            'loans.application_date', 'loans.end_date',
            'loan_types.label as loan_type_label',
            'members.first_name', 'members.last_name', 'members.member_no',
            'members.company', 'members.department',
            'users.name as loan_officer',
            DB::raw('COALESCE(sched.total_due,0) as total_due'),
            DB::raw('COALESCE(sched.total_paid,0) as total_paid'),
            DB::raw('COALESCE(sched.total_due,0)-COALESCE(sched.total_paid,0) as remaining_balance'),
            DB::raw('COALESCE(sched.overdue_balance,0) as overdue_balance'),
            DB::raw('COALESCE(sched.total_penalty,0) as total_penalty'),
            DB::raw('COALESCE(sched.overdue_count,0) as overdue_count'),
        ])->orderByDesc('remaining_balance')->get();

        $mapped = $rows->map(fn($r) => [
            'loan_id'           => $r->id,
            'loan_no'           => $r->loan_no,
            'loan_status'       => $r->loan_status,
            'member_name'       => "{$r->first_name} {$r->last_name}",
            'member_no'         => $r->member_no,
            'company'           => $r->company,
            'department'        => $r->department,
            'loan_officer'      => $r->loan_officer,
            'loan_type_label'   => $r->loan_type_label,
            'original_amount'   => round((float) $r->original_amount, 2),
            'total_paid'        => round((float) $r->total_paid, 2),
            'remaining_balance' => round((float) $r->remaining_balance, 2),
            'overdue_balance'   => round((float) $r->overdue_balance, 2),
            'total_penalty'     => round((float) $r->total_penalty, 2),
            'overdue_count'     => (int) $r->overdue_count,
            'application_date'  => $r->application_date,
            'end_date'          => $r->end_date,
        ]);

        return [
            'filters' => $filters,
            'rows'    => $mapped->values(),
            'summary' => [
                'total_loans'        => $mapped->count(),
                'total_original'     => round($mapped->sum('original_amount'), 2),
                'total_paid'         => round($mapped->sum('total_paid'), 2),
                'total_outstanding'  => round($mapped->sum('remaining_balance'), 2),
                'total_overdue'      => round($mapped->sum('overdue_balance'), 2),
                'total_penalty'      => round($mapped->sum('total_penalty'), 2),
                'loans_with_overdue' => $mapped->where('overdue_count', '>', 0)->count(),
            ],
        ];
    }

    // ── Date helpers ─────────────────────────────────────────

    private function resolveFrom(array $f): string
    {
        if (!empty($f['date_from'])) return Carbon::parse($f['date_from'])->toDateString();
        if (!empty($f['month']))     return Carbon::parse($f['month'] . '-01')->startOfMonth()->toDateString();
        if (!empty($f['fiscal_year'])) {
            $month = (int) (\App\Models\SystemPreference::get('fiscal_year_start_month', 1));
            return Carbon::create($f['fiscal_year'], $month, 1)->startOfMonth()->toDateString();
        }
        return Carbon::now()->startOfMonth()->toDateString();
    }

    private function resolveTo(array $f): string
    {
        if (!empty($f['date_to']))  return Carbon::parse($f['date_to'])->toDateString();
        if (!empty($f['month']))    return Carbon::parse($f['month'] . '-01')->endOfMonth()->toDateString();
        if (!empty($f['fiscal_year'])) {
            $month = (int) (\App\Models\SystemPreference::get('fiscal_year_start_month', 1));
            return Carbon::create($f['fiscal_year'], $month, 1)->addYear()->subDay()->toDateString();
        }
        return Carbon::now()->endOfMonth()->toDateString();
    }
}
