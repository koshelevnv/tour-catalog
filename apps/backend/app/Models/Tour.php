<?php

namespace App\Models;

use App\Jobs\GenerateTourEmbedding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Tour extends Model
{
    use HasFactory;
    protected $fillable = [
        'type_id', 'title', 'slug', 'description', 'duration_days', 'embedding',
    ];

    protected static function booted(): void
    {
        static::saved(function (Tour $tour) {
            if ($tour->wasRecentlyCreated || $tour->wasChanged(['title', 'description'])) {
                GenerateTourEmbedding::dispatch($tour)->afterCommit();
            }
        });

        static::deleting(function (Tour $tour) {
            $tour->photos->each(fn($photo) => Storage::disk('public')->delete($photo->path));
        });
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TourType::class, 'type_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TourPhoto::class)->orderBy('order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(TourVariant::class)->orderBy('date');
    }

    public function waypoints(): HasMany
    {
        return $this->hasMany(TourWaypoint::class)->orderBy('order');
    }
}
