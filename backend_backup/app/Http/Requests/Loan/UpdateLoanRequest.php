<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('loan'));
    }

    public function rules(): array
    {
        return [
            'member_id'        => 'sometimes|required|exists:members,id',
            'loan_type_id'     => 'sometimes|required|exists:loan_types,id',
            'amount'           => 'sometimes|required|numeric|min:1',
            'term_months'      => 'sometimes|required|integer|min:1',
            'frequency'        => 'sometimes|required|in:monthly,bimonthly,weekly',
            'purpose'          => 'nullable|string',
            'co_maker_1_id'    => 'nullable|exists:members,id',
            'co_maker_2_id'    => 'nullable|exists:members,id',
            'status'           => 'sometimes|in:DRAFT,PENDING,APPROVED,ACTIVE,CLOSED,REJECTED',
            'application_date' => 'nullable|date',
            'first_due_date'   => 'nullable|date',
            'approval_date'    => 'nullable|date',
            'approved_by_hr'   => 'nullable|string|max:150',
            'approved_by_coop' => 'nullable|string|max:150',
            'notes'            => 'nullable|string',
        ];
    }
}
