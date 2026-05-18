<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourVariant extends Model
{
    use HasFactory;
    protected $fillable = ['tour_id', 'date', 'duration_days', 'price'];

    protected $casts = ['date' => 'date'];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
