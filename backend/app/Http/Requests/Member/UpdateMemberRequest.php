<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('member'));
    }

    public function rules(): array
    {
        $memberId = $this->route('member')?->id;

        return [
            'member_no'      => "sometimes|required|string|max:20|unique:members,member_no,{$memberId}",
            'last_name'      => 'sometimes|required|string|max:100',
            'first_name'     => 'sometimes|required|string|max:100',
            'middle_name'    => 'nullable|string|max:100',
            'address'        => 'nullable|string',
            'contact'        => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:150',
            'company'        => 'nullable|string|max:200',
            'branch'         => 'nullable|string|max:100',
            'department'     => 'nullable|string|max:100',
            'status'         => 'sometimes|required|in:REGULAR,PROBI,SUSPENDED,INACTIVE',
            'position'       => 'nullable|string|max:150',
            'supervisor'     => 'nullable|string|max:150',
            'date_hired'     => 'nullable|date',
            'monthly_salary' => 'nullable|numeric|min:0',
            'share_capital'  => 'nullable|numeric|min:0',
            'member_status'  => 'sometimes|required|in:ACTIVE,INACTIVE,RESIGNED',
        ];
    }
}
