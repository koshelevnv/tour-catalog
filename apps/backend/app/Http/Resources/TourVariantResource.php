<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'date'          => $this->date->format('Y-m-d'),
            'duration_days' => $this->duration_days,
            'price'         => $this->price,
        ];
    }
}
