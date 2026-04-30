<?php
// app/Http/Resources/CoopProfileResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoopProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'cda_reg_no'        => $this->cda_reg_no,
            'address'           => $this->address,
            'contact'           => $this->contact,
            'email'             => $this->email,
            'website'           => $this->website,
            'hr_signatory'      => $this->hr_signatory,
            'coop_signatory'    => $this->coop_signatory,
            'logo_url'          => $this->logo_url,
            'fiscal_year_start' => $this->fiscal_year_start,
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}
