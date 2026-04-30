<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Member::class);
    }

    public function rules(): array
    {
        return [
            'member_no'      => 'required|string|max:20|unique:members,member_no',
            'last_name'      => 'required|string|max:100',
            'first_name'     => 'required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'contact'        => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:150',
            'company'        => 'nullable|string|max:200',
            'branch'         => 'nullable|string|max:100',
            'department'     => 'nullable|string|max:100',
            'status'         => 'required|in:REGULAR,PROBI,SUSPENDED,INACTIVE',
            'position'       => 'nullable|string|max:150',
            'supervisor'     => 'nullable|string|max:150',
            'date_hired'     => 'nullable|date',
            'monthly_salary' => 'nullable|numeric|min:0',
            'share_capital'  => 'nullable|numeric|min:0',
            'member_status'  => 'required|in:ACTIVE,INACTIVE,RESIGNED',
        ];
    }
}
