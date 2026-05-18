<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourWaypoint extends Model
{
    use HasFactory;
    protected $fillable = ['tour_id', 'lat', 'lng', 'order', 'label'];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
