<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variantDurations = $this->variants->pluck('duration_days')->filter()->values();

        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'duration_min' => $variantDurations->min() ?? $this->duration_days,
            'duration_max' => $variantDurations->max() ?? $this->duration_days,
            'type'         => new TourTypeResource($this->whenLoaded('type')),
            'cover'        => optional($this->photos->first())->path,
            'price_from'   => $this->variants->min('price'),
        ];
    }
}
