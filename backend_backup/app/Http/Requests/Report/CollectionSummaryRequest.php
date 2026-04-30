<?php
namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class CollectionSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super-admin', 'manager', 'board']);
    }

    public function rules(): array
    {
        return [
            'date_from'        => 'nullable|date',
            'date_to'          => 'nullable|date|after_or_equal:date_from',
            'month'            => 'nullable|date_format:Y-m',
            'fiscal_year'      => 'nullable|integer|min:2000',
            'company'          => 'nullable|string|max:200',
            'department'       => 'nullable|string|max:100',
            'loan_officer_id'  => 'nullable|exists:users,id',
            'loan_type_id'     => 'nullable|exists:loan_types,id',
            'breakdown'        => 'nullable|in:loan_type,status,both',
            'export'           => 'nullable|in:excel,pdf',
        ];
    }
}
