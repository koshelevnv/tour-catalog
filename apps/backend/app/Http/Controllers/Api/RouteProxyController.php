<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RouteProxyController extends Controller
{
    public function index(Request $request)
    {
        $waypoints = $request->input('waypoints', '');
        $mode      = $request->input('mode', 'pedestrian');

        $parts = array_filter(explode('|', $waypoints));
        if (count($parts) < 2) {
            return response()->json(['error' => 'need_at_least_2_points'], 422);
        }

        // OSRM expects lng,lat order
        $coords = collect($parts)->map(function ($p) {
            [$lat, $lng] = explode(',', $p);
            return trim($lng) . ',' . trim($lat);
        })->implode(';');

        $profile = match ($mode) {
            'driving'    => 'driving',
            'pedestrian' => 'foot',
            default      => 'driving',
        };

        $response = Http::timeout(10)
            ->get("https://router.project-osrm.org/route/v1/{$profile}/{$coords}", [
                'overview'   => 'full',
                'geometries' => 'geojson',
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'osrm_unavailable'], 422);
        }

        $routes = $response->json('routes');
        if (empty($routes[0]['geometry']['coordinates'])) {
            return response()->json(['error' => 'no_route'], 422);
        }

        // Convert [lng, lat] → [lat, lng] for Yandex Maps
        $yaCoords = array_map(
            fn($c) => [$c[1], $c[0]],
            $routes[0]['geometry']['coordinates']
        );

        return response()->json(['coords' => $yaCoords]);
    }
}
