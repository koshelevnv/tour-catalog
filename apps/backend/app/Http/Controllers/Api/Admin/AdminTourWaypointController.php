<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTourWaypointController extends Controller
{
    public function sync(Request $request, int $tourId): JsonResponse
    {
        $tour = Tour::findOrFail($tourId);

        $request->validate([
            'waypoints'         => 'array',
            'waypoints.*.lat'   => 'required|numeric',
            'waypoints.*.lng'   => 'required|numeric',
            'waypoints.*.order' => 'required|integer',
            'waypoints.*.label' => 'nullable|string|max:255',
        ]);

        $tour->waypoints()->delete();

        foreach ($request->waypoints ?? [] as $wp) {
            $tour->waypoints()->create([
                'lat'   => $wp['lat'],
                'lng'   => $wp['lng'],
                'order' => $wp['order'],
                'label' => $wp['label'] ?? null,
            ]);
        }

        return response()->json(['waypoints' => $tour->waypoints()->get()]);
    }
}
