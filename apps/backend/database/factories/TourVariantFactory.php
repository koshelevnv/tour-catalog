<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TourVariantFactory extends Factory
{
    protected $model = TourVariant::class;

    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'date' => $this->faker->dateTimeBetween('+1 month', '+12 months')->format('Y-m-d'),
            'price' => $this->faker->numberBetween(15000, 150000),
        ];
    }
}
