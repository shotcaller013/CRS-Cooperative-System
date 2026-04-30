<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmortizationScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'loan_id'     => $this->loan_id,
            'period_no'   => $this->period_no,
            'due_date'    => $this->due_date?->toDateString(),
            'principal'   => (float) $this->principal,
            'interest'    => (float) $this->interest,
            'amount_due'  => (float) $this->amount_due,
            'balance'     => (float) $this->balance,
            'status'      => $this->status,
            'paid_amount' => (float) $this->paid_amount,
            'paid_date'   => $this->paid_date?->toDateString(),
            'or_number'   => $this->or_number,
        ];
    }
}
