<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Loan::class);
    }

    public function rules(): array
    {
        return [
            'member_id'       => 'required|exists:members,id',
            'loan_type_id'    => 'required|exists:loan_types,id',
            'amount'          => 'required|numeric|min:1',
            'term_months'     => 'required|integer|min:1',
            'frequency'       => 'required|in:monthly,bimonthly,weekly',
            'purpose'         => 'nullable|string',
            'co_maker_1_id'   => 'nullable|exists:members,id|different:member_id',
            'co_maker_2_id'   => 'nullable|exists:members,id|different:member_id',
            'status'          => 'sometimes|in:DRAFT,PENDING,APPROVED,ACTIVE,CLOSED,REJECTED',
            'application_date'=> 'nullable|date',
            'first_due_date'  => 'nullable|date',
            'notes'           => 'nullable|string',
        ];
    }
}
