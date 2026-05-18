<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourWaypointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'lat'   => $this->lat,
            'lng'   => $this->lng,
            'order' => $this->order,
            'label' => $this->label,
        ];
    }
}
