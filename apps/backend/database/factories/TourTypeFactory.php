<?php

namespace Database\Factories;

use App\Models\TourType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TourTypeFactory extends Factory
{
    protected $model = TourType::class;

    private static array $types = [
        'Горный' => 'gornyy',
        'Морской' => 'morskoy',
        'Культурный' => 'kulturnyy',
        'Экстремальный' => 'ekstremalnyy',
        'Семейный' => 'semeynyy',
        'Пляжный' => 'plyazhnyy',
        'Экологический' => 'ekologicheskiy',
        'Городской' => 'gorodskoy',
    ];

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(array_keys(self::$types));

        return [
            'name' => $name,
            'slug' => self::$types[$name],
        ];
    }
}
