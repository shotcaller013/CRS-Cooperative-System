<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'loan_no'           => $this->loan_no,
            'member_id'         => $this->member_id,
            'loan_type_id'      => $this->loan_type_id,
            'amount'            => (float) $this->amount,
            'term_months'       => $this->term_months,
            'frequency'         => $this->frequency,
            'annual_rate'       => (float) $this->annual_rate,
            'purpose'           => $this->purpose,
            'co_maker_1_id'     => $this->co_maker_1_id,
            'co_maker_2_id'     => $this->co_maker_2_id,
            'status'            => $this->status,
            'total_payment'     => (float) $this->total_payment,
            'total_interest'    => (float) $this->total_interest,
            'n_periods'         => $this->n_periods,
            'first_payment_amt' => (float) $this->first_payment_amt,
            'last_payment_amt'  => (float) $this->last_payment_amt,
            'application_date'  => $this->application_date?->toDateString(),
            'approval_date'     => $this->approval_date?->toDateString(),
            'first_due_date'    => $this->first_due_date?->toDateString(),
            'end_date'          => $this->end_date?->toDateString(),
            'approved_by_hr'    => $this->approved_by_hr,
            'approved_by_coop'  => $this->approved_by_coop,
            'signed_form_url'   => $this->signed_form_url,
            'notes'             => $this->notes,
            // Relationships
            'member'            => new MemberResource($this->whenLoaded('member')),
            'loan_type'         => new LoanTypeResource($this->whenLoaded('loanType')),
            'co_maker_1'        => new MemberResource($this->whenLoaded('coMaker1')),
            'co_maker_2'        => new MemberResource($this->whenLoaded('coMaker2')),
            'amortization_schedules' => AmortizationScheduleResource::collection(
                $this->whenLoaded('amortizationSchedules')
            ),
            'creator'           => new UserResource($this->whenLoaded('creator')),
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}
