<?php
// app/Http/Requests/Setting/UpdateCoopProfileRequest.php
namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoopProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-setting');
    }

    public function rules(): array
    {
        return [
            'name'              => 'sometimes|string|max:200',
            'cda_reg_no'        => 'nullable|string|max:50',
            'address'           => 'nullable|string',
            'contact'           => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:150',
            'website'           => 'nullable|url|max:150',
            'hr_signatory'      => 'nullable|string|max:150',
            'coop_signatory'    => 'nullable|string|max:150',
            'logo_url'          => 'nullable|url|max:255',
            'fiscal_year_start' => 'nullable|string|max:20',
        ];
    }
}
