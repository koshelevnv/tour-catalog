<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TourFactory extends Factory
{
    protected $model = Tour::class;

    private static array $titles = [
        'Алтайские горы: треккинг к Белухе',
        'Байкал зимой: лёд и тишина',
        'Карелия: озёра и водопады',
        'Камчатка: вулканы и гейзеры',
        'Сочи: горы и море',
        'Урал: уральские хребты',
        'Кавказ: через перевалы',
        'Байкал летом: обход острова Ольхон',
        'Крым: пещерные города и море',
        'Архангельск: Соловецкие острова',
    ];

    public function definition(): array
    {
        $title = $this->faker->randomElement(self::$titles);

        return [
            'type_id' => TourType::inRandomOrder()->value('id') ?? TourType::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->numerify('###'),
            'description' => $this->faker->paragraphs(3, true),
            'duration_days' => $this->faker->numberBetween(3, 21),
        ];
    }
}
