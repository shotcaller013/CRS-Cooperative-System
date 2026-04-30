<?php
// app/Http/Resources/LoanTypeResource.php (full replacement)
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'code'                 => $this->code,
            'label'                => $this->label,
            'min_amount'           => (float) $this->min_amount,
            'max_amount'           => (float) $this->max_amount,
            'amount_cap_method'    => $this->amount_cap_method,
            'salary_multiplier'    => $this->salary_multiplier ? (float) $this->salary_multiplier : null,
            'min_term'             => $this->min_term,
            'max_term'             => $this->max_term,
            'annual_rate_default'  => (float) $this->annual_rate_default,
            'annual_rate_min'      => $this->annual_rate_min ? (float) $this->annual_rate_min : null,
            'annual_rate_max'      => $this->annual_rate_max ? (float) $this->annual_rate_max : null,
            'allowed_emp_statuses' => $this->allowed_emp_statuses ?? [],
            'min_share_capital'    => (float) $this->min_share_capital,
            'min_tenure_months'    => (int) $this->min_tenure_months,
            'allow_concurrent'     => (bool) $this->allow_concurrent,
            'penalty_rate'         => (float) $this->penalty_rate,
            'is_active'            => (bool) $this->is_active,
            'thresholds'           => ApprovalThresholdResource::collection(
                $this->whenLoaded('approvalThresholds')
            ),
        ];
    }
}
