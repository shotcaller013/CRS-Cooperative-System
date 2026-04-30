<?php
// app/Http/Resources/PaymentResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'loan_id'       => $this->loan_id,
            'schedule_id'   => $this->schedule_id,
            'amount_paid'   => (float) $this->amount_paid,
            'payment_type'  => $this->payment_type,
            'or_number'     => $this->or_number,
            'payment_date'  => $this->payment_date?->toDateString(),
            'penalty_paid'  => (float) $this->penalty_paid,
            'balance_after' => (float) $this->balance_after,
            'notes'         => $this->notes,
            'received_by'   => $this->receiver?->name,
            'member_name'   => $this->loan?->member?->full_name,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
