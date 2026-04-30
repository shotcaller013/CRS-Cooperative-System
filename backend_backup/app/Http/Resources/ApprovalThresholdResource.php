<?php
// app/Http/Resources/ApprovalThresholdResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalThresholdResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'level'         => $this->level,
            'approver_role' => $this->approver_role,
            'amount_from'   => (float) $this->amount_from,
            'amount_to'     => $this->amount_to !== null ? (float) $this->amount_to : null,
            'sequence'      => (int) $this->sequence,
        ];
    }
}
