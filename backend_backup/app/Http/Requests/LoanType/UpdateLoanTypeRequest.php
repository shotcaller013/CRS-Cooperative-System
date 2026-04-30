<?php
// app/Http/Requests/LoanType/UpdateLoanTypeRequest.php
namespace App\Http\Requests\LoanType;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-setting');
    }

    public function rules(): array
    {
        $id = $this->route('loan_type')?->id;
        return [
            'code'                 => "sometimes|string|max:50|unique:loan_types,code,{$id}",
            'label'                => 'sometimes|string|max:100',
            'min_amount'           => 'sometimes|numeric|min:0',
            'max_amount'           => 'sometimes|numeric',
            'amount_cap_method'    => 'sometimes|in:fixed,salary_multiplier,both',
            'salary_multiplier'    => 'nullable|numeric|min:0',
            'min_term'             => 'sometimes|integer|min:1',
            'max_term'             => 'sometimes|integer|min:1',
            'annual_rate_default'  => 'sometimes|numeric|min:0|max:1',
            'annual_rate_min'      => 'nullable|numeric|min:0|max:1',
            'annual_rate_max'      => 'nullable|numeric|min:0|max:1',
            'allowed_emp_statuses' => 'nullable|array',
            'allowed_emp_statuses.*'=> 'string',
            'min_share_capital'    => 'nullable|numeric|min:0',
            'min_tenure_months'    => 'nullable|integer|min:0',
            'allow_concurrent'     => 'boolean',
            'penalty_rate'         => 'nullable|numeric|min:0|max:1',
            'is_active'            => 'boolean',
            'thresholds'           => 'nullable|array',
            'thresholds.*.level'        => 'required|string',
            'thresholds.*.approver_role'=> 'required|string',
            'thresholds.*.amount_from'  => 'required|numeric|min:0',
            'thresholds.*.amount_to'    => 'nullable|numeric',
            'thresholds.*.sequence'     => 'required|integer|min:1',
        ];
    }
}
