<?php
namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class AgingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['super-admin', 'manager', 'board']);
    }

    public function rules(): array
    {
        return [
            'as_of_date'       => 'nullable|date',
            'company'          => 'nullable|string|max:200',
            'department'       => 'nullable|string|max:100',
            'loan_officer_id'  => 'nullable|exists:users,id',
            'loan_type_id'     => 'nullable|exists:loan_types,id',
            'export'           => 'nullable|in:excel,pdf',
        ];
    }
}
