<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTourDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'duration_days' => $this->duration_days,
            'type_id'       => $this->type_id,
            'type'          => new TourTypeResource($this->whenLoaded('type')),
            'photos'        => $this->photos->map(fn ($p) => ['id' => $p->id, 'path' => $p->path]),
            'variants'      => TourVariantResource::collection($this->whenLoaded('variants')),
            'waypoints'     => TourWaypointResource::collection($this->whenLoaded('waypoints')),
        ];
    }
}
