<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourResource;
use App\Models\Tour;
use App\Models\TourVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q       = trim($request->input('q', ''));
        $perPage = max(1, min(100, (int) $request->input('per_page', 12)));
        $sort    = $request->input('sort', '');

        if ($q === '') {
            return TourResource::collection(
                Tour::with(['type', 'photos', 'variants'])->paginate($perPage)
            );
        }

        $url      = config('services.embeddings.url');
        $response = Http::timeout(15)->post("{$url}/embed", ['text' => $q]);

        if ($response->successful()) {
            $vectorStr = '[' . implode(',', $response->json('embedding')) . ']';

            // Semantically relevant: cosine distance below threshold, top-30
            $vectorIds = Tour::whereNotNull('embedding')
                ->whereRaw('(embedding <=> ?::vector) < 0.75', [$vectorStr])
                ->orderByRaw('(embedding <=> ?::vector)', [$vectorStr])
                ->limit(30)
                ->pluck('id');

            // Text matches not already in vector results
            $textIds = Tour::where(function ($q2) use ($q) {
                    $q2->where('title', 'ilike', "%{$q}%")
                       ->orWhere('description', 'ilike', "%{$q}%");
                })
                ->whereNotIn('id', $vectorIds)
                ->pluck('id');

            $allIds = $vectorIds->merge($textIds)->unique()->values();

            if ($allIds->isEmpty()) {
                return TourResource::collection(
                    Tour::with(['type', 'photos', 'variants'])->whereIn('id', [])->paginate($perPage)
                );
            }

            $query = Tour::with(['type', 'photos', 'variants'])->whereIn('id', $allIds);
            $this->applyFilters($query, $request);

            if ($sort) {
                $this->applySort($query, $sort);
            } else {
                $query->orderByRaw(
                    'CASE WHEN embedding IS NOT NULL THEN (embedding <=> ?::vector) ELSE 1 END',
                    [$vectorStr]
                );
            }

            return TourResource::collection($query->paginate($perPage));
        }

        // Fallback: full-text ILIKE search
        $query = Tour::with(['type', 'photos', 'variants'])
            ->where(function ($q2) use ($q) {
                $q2->where('title', 'ilike', "%{$q}%")
                   ->orWhere('description', 'ilike', "%{$q}%");
            });

        $this->applyFilters($query, $request);
        $this->applySort($query, $sort ?: 'date_desc');

        return TourResource::collection($query->paginate($perPage));
    }

    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->filled('type')) {
            $query->whereHas('type', fn ($q) => $q->where('slug', $request->type));
        }

        if ($request->filled('duration_min') || $request->filled('duration_max')) {
            $min = $request->filled('duration_min') ? (int) $request->duration_min : null;
            $max = $request->filled('duration_max') ? (int) $request->duration_max : null;
            if ($min) $query->where('duration_days', '>=', $min);
            if ($max) $query->where('duration_days', '<=', $max);
        }

        if ($request->filled('price_min') || $request->filled('price_max')) {
            $query->whereHas('variants', function ($q) use ($request) {
                if ($request->filled('price_min')) $q->where('price', '>=', (float) $request->price_min);
                if ($request->filled('price_max')) $q->where('price', '<=', (float) $request->price_max);
            });
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->whereHas('variants', function ($q) use ($request) {
                if ($request->filled('date_from')) $q->where('date', '>=', $request->date_from);
                if ($request->filled('date_to'))   $q->where('date', '<=', $request->date_to);
            });
        }
    }

    private function applySort(\Illuminate\Database\Eloquent\Builder $query, string $sort): void
    {
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
            default         => null,
        };
    }
}
