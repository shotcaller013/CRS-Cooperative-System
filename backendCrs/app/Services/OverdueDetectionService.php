<?php
// app/Services/OverdueDetectionService.php
namespace App\Services;

use App\Models\AmortizationSchedule;
use Illuminate\Support\Facades\DB;

class OverdueDetectionService
{
    /**
     * Flag PENDING periods whose due_date < today as OVERDUE and compute penalty.
     * Returns the count of newly flagged periods.
     */
    public function run(): int
    {
        $today    = now()->toDateString();
        $flagged  = 0;

        $overdue = AmortizationSchedule::where('status', 'PENDING')
            ->where('due_date', '<', $today)
            ->with('loan.loanType')
            ->get();

        foreach ($overdue as $period) {
            DB::transaction(function () use ($period, $today, &$flagged) {
                $daysOverdue  = now()->diffInDays($period->due_date);
                $unpaid       = max(0, (float) $period->amount_due - (float) $period->paid_amount);
                $penaltyRate  = (float) ($period->loan->loanType->penalty_rate ?? 0.02);
                $penalty      = $unpaid * $penaltyRate * ($daysOverdue / 30);

                $period->update([
                    'status'         => 'OVERDUE',
                    'days_overdue'   => $daysOverdue,
                    'penalty_amount' => round($penalty, 2),
                ]);
                $flagged++;
            });
        }

        return $flagged;
    }
}
