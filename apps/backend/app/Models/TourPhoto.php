<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourPhoto extends Model
{
    use HasFactory;
    protected $fillable = ['tour_id', 'path', 'order'];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
