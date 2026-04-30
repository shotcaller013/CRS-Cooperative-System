<?php
// app/Services/PaymentService.php
namespace App\Services;

use App\Models\AmortizationSchedule;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Payment::with(['loan.member', 'schedule', 'receiver']);

        if (!empty($filters['loan_id'])) {
            $query->where('loan_id', $filters['loan_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('payment_date')->paginate($perPage);
    }

    public function record(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $schedule = AmortizationSchedule::findOrFail($data['schedule_id']);
            $loan     = Loan::with('loanType')->findOrFail($data['loan_id']);

            $amountDue   = (float) $schedule->amount_due;
            $amountPaid  = (float) $data['amount_paid'];
            $penaltyPaid = (float) ($data['penalty_paid'] ?? 0);

            // Determine new schedule status
            $totalPaid = (float) $schedule->paid_amount + $amountPaid;
            $newStatus = match (true) {
                $totalPaid >= $amountDue => 'PAID',
                $totalPaid > 0           => 'PARTIAL',
                default                  => $schedule->status,
            };

            // Update schedule
            $schedule->update([
                'paid_amount' => $totalPaid,
                'paid_date'   => $newStatus === 'PAID' ? ($data['payment_date'] ?? now()->toDateString()) : $schedule->paid_date,
                'or_number'   => $data['or_number'] ?? $schedule->or_number,
                'status'      => $newStatus,
            ]);

            // Compute balance after (remaining unpaid schedule amounts)
            $balanceAfter = AmortizationSchedule::where('loan_id', $loan->id)
                ->where('status', '!=', 'PAID')
                ->sum(DB::raw('amount_due - paid_amount'));

            // Record payment row
            $payment = Payment::create([
                'loan_id'       => $loan->id,
                'schedule_id'   => $schedule->id,
                'amount_paid'   => $amountPaid,
                'payment_type'  => $data['payment_type'],
                'or_number'     => $data['or_number'] ?? null,
                'payment_date'  => $data['payment_date'] ?? now()->toDateString(),
                'penalty_paid'  => $penaltyPaid,
                'balance_after' => max(0, $balanceAfter),
                'notes'         => $data['notes'] ?? null,
                'received_by'   => Auth::id(),
            ]);

            // If all periods are paid → close loan
            $remaining = AmortizationSchedule::where('loan_id', $loan->id)
                ->whereNotIn('status', ['PAID'])
                ->count();
            if ($remaining === 0) {
                $loan->update(['status' => 'CLOSED']);
            }

            return $payment->load(['loan.member', 'schedule', 'receiver']);
        });
    }

    public function getByLoan(int $loanId): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::with('receiver')
            ->where('loan_id', $loanId)
            ->orderByDesc('payment_date')
            ->get();
    }
}
