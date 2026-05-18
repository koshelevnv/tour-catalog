<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourWaypoint;
use Illuminate\Database\Eloquent\Factories\Factory;

class TourWaypointFactory extends Factory
{
    protected $model = TourWaypoint::class;

    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'lat' => $this->faker->latitude(50, 70),
            'lng' => $this->faker->longitude(30, 140),
            'order' => 0,
            'label' => $this->faker->city(),
        ];
    }
}
