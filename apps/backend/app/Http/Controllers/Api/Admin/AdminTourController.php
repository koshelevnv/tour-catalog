<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTourDetailResource;
use App\Models\Tour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminTourController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $tour = Tour::where('slug', $slug)->firstOrFail();

        return response()->json(new AdminTourDetailResource($tour->load(['type', 'photos', 'variants', 'waypoints'])));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type_id' => 'required|exists:tour_types,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tours,slug',
            'description' => 'nullable|string',
            'duration_days' => 'required|integer|min:1',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);

        $tour = Tour::create($data);

        return response()->json(new AdminTourDetailResource($tour->load(['type', 'photos', 'variants', 'waypoints'])), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tour = Tour::findOrFail($id);

        $data = $request->validate([
            'type_id' => 'sometimes|exists:tour_types,id',
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tours,slug,'.$tour->id,
            'description' => 'nullable|string',
            'duration_days' => 'sometimes|integer|min:1',
        ]);

        if (isset($data['title']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $tour->update($data);

        return response()->json(new AdminTourDetailResource($tour->load(['type', 'photos', 'variants', 'waypoints'])));
    }

    public function destroy(int $id): JsonResponse
    {
        Tour::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
