<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'member_no'      => $this->member_no,
            'last_name'      => $this->last_name,
            'first_name'     => $this->first_name,
            'middle_name'    => $this->middle_name,
            'full_name'      => $this->full_name,
            'address'        => $this->address,
            'contact'        => $this->contact,
            'email'          => $this->email,
            'company'        => $this->company,
            'branch'         => $this->branch,
            'department'     => $this->department,
            'status'         => $this->status,
            'position'       => $this->position,
            'supervisor'     => $this->supervisor,
            'date_hired'     => $this->date_hired?->toDateString(),
            'monthly_salary' => (float) $this->monthly_salary,
            'share_capital'  => (float) $this->share_capital,
            'member_status'  => $this->member_status,
            'photo_url'      => $this->photo_url,
            'active_loans_count' => $this->whenCounted('activeLoans'),
            'loans_count'        => $this->whenCounted('loans'),
            'loans'          => LoanResource::collection($this->whenLoaded('loans')),
            'creator'        => new UserResource($this->whenLoaded('creator')),
            'created_at'     => $this->created_at?->toDateTimeString(),
            'updated_at'     => $this->updated_at?->toDateTimeString(),
        ];
    }
}
