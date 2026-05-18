<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variantDurations = $this->variants->pluck('duration_days')->filter()->values();

        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'duration_min' => $variantDurations->min() ?? $this->duration_days,
            'duration_max' => $variantDurations->max() ?? $this->duration_days,
            'duration_days'=> $this->duration_days,
            'type'         => new TourTypeResource($this->whenLoaded('type')),
            'photos'       => $this->photos->map(fn($p) => ['id' => $p->id, 'path' => $p->path]),
            'variants'     => TourVariantResource::collection($this->whenLoaded('variants')),
            'waypoints'    => TourWaypointResource::collection($this->whenLoaded('waypoints')),
        ];
    }
}
