<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourDetailResource;
use App\Http\Resources\TourResource;
use App\Models\Tour;
use App\Models\TourVariant;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function meta()
    {
        return response()->json([
            'min_duration' => max(1, (int) (Tour::min('duration_days') ?? 1)),
            'max_duration' => max(1, (int) (Tour::max('duration_days') ?? 30)),
            'min_price'    => max(0, (int) (TourVariant::min('price') ?? 0)),
            'max_price'    => max(0, (int) (TourVariant::max('price') ?? 999999)),
        ]);
    }

    public function index(Request $request)
    {
        $query = Tour::with(['type', 'photos', 'variants']);

        if ($request->filled('type')) {
            $query->whereHas('type', fn ($q) => $q->where('slug', $request->type));
        }

        if ($request->filled('duration_min') || $request->filled('duration_max')) {
            $min = $request->filled('duration_min') ? (int) $request->duration_min : null;
            $max = $request->filled('duration_max') ? (int) $request->duration_max : null;

            $query->where(function ($q) use ($min, $max) {
                // Tours with variant duration_days set
                $q->whereHas('variants', function ($vq) use ($min, $max) {
                    $vq->whereNotNull('duration_days');
                    if ($min) $vq->where('duration_days', '>=', $min);
                    if ($max) $vq->where('duration_days', '<=', $max);
                });
                // Fallback: tours without variant duration (use tours.duration_days)
                $q->orWhere(function ($fb) use ($min, $max) {
                    $fb->whereDoesntHave('variants', fn($vq) => $vq->whereNotNull('duration_days'));
                    if ($min) $fb->where('duration_days', '>=', $min);
                    if ($max) $fb->where('duration_days', '<=', $max);
                });
            });
        }

        if ($request->filled('price_min') || $request->filled('price_max')) {
            $query->whereHas('variants', function ($q) use ($request) {
                if ($request->filled('price_min')) {
                    $q->where('price', '>=', (float) $request->price_min);
                }
                if ($request->filled('price_max')) {
                    $q->where('price', '<=', (float) $request->price_max);
                }
            });
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->whereHas('variants', function ($q) use ($request) {
                if ($request->filled('date_from')) {
                    $q->where('date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->where('date', '<=', $request->date_to);
                }
            });
        }

        $sort = $request->input('sort', '');
        match ($sort) {
            'price_asc'     => $query->orderBy(
                TourVariant::selectRaw('MIN(price)')->whereColumn('tour_id', 'tours.id'), 'asc'
            ),
            'price_desc'    => $query->orderBy(
                TourVariant::selectRaw('MIN(price)')->whereColumn('tour_id', 'tours.id'), 'desc'
            ),
            'duration_asc'  => $query->orderBy('duration_days', 'asc'),
            'duration_desc' => $query->orderBy('duration_days', 'desc'),
            'date_asc'      => $query->orderBy('created_at', 'asc'),
            'date_desc'     => $query->orderBy('created_at', 'desc'),
            'title_asc'     => $query->orderBy('title', 'asc'),
            'title_desc'    => $query->orderBy('title', 'desc'),
            default         => $query->orderBy('created_at', 'desc'),
        };

        $perPage = max(1, min(100, (int) $request->input('per_page', 12)));
        $tours = $query->paginate($perPage);

        return TourResource::collection($tours);
    }

    public function show(string $slug)
    {
        $tour = Tour::with(['type', 'photos', 'variants', 'waypoints'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new TourDetailResource($tour);
    }

    public function suggest(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $tours = Tour::with(['photos'])
            ->where('title', 'ilike', "%{$q}%")
            ->orWhere('description', 'ilike', "%{$q}%")
            ->limit(6)
            ->get(['id', 'title', 'slug', 'duration_days']);

        return response()->json([
            'data' => $tours->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'slug' => $t->slug,
                'duration_days' => $t->duration_days,
                'photo' => $t->photos->first()?->url,
            ]),
        ]);
    }
}
