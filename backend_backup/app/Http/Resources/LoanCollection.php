<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LoanCollection extends ResourceCollection
{
    public $collects = LoanResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'pagination' => [
                    'total'        => $this->total(),
                    'per_page'     => $this->perPage(),
                    'current_page' => $this->currentPage(),
                    'last_page'    => $this->lastPage(),
                    'from'         => $this->firstItem(),
                    'to'           => $this->lastItem(),
                ],
            ],
        ];
    }
}
