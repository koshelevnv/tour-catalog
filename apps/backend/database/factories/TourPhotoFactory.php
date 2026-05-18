<?php

namespace Database\Factories;

use App\Models\Tour;
use App\Models\TourPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TourPhotoFactory extends Factory
{
    protected $model = TourPhoto::class;

    public function definition(): array
    {
        return [
            'tour_id' => Tour::factory(),
            'path'    => '',
            'order'   => 1,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (TourPhoto $photo) {
            $localPath = "tours/{$photo->tour_id}/{$photo->id}.jpg";

            try {
                $response = Http::timeout(15)->get(
                    "https://picsum.photos/seed/{$photo->id}/1200/800"
                );
                if ($response->successful()) {
                    Storage::disk('public')->put($localPath, $response->body());
                    $photo->updateQuietly(['path' => $localPath]);
                    return;
                }
            } catch (\Throwable) {
            }

            // fallback: store external URL so photo is still visible
            $photo->updateQuietly([
                'path' => "https://picsum.photos/seed/{$photo->id}/1200/800",
            ]);
        });
    }
}
