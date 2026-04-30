<?php
// app/Http/Requests/LoanType/StoreLoanTypeRequest.php
namespace App\Http\Requests\LoanType;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-setting');
    }

    public function rules(): array
    {
        return [
            'code'                 => 'required|string|max:50|unique:loan_types,code',
            'label'                => 'required|string|max:100',
            'min_amount'           => 'required|numeric|min:0',
            'max_amount'           => 'required|numeric|gt:min_amount',
            'amount_cap_method'    => 'required|in:fixed,salary_multiplier,both',
            'salary_multiplier'    => 'nullable|numeric|min:0',
            'min_term'             => 'required|integer|min:1',
            'max_term'             => 'required|integer|gte:min_term',
            'annual_rate_default'  => 'required|numeric|min:0|max:1',
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
