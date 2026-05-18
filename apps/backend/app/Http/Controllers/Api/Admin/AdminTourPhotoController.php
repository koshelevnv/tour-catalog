<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTourPhotoController extends Controller
{
    public function store(Request $request, int $tourId): JsonResponse
    {
        $tour = Tour::findOrFail($tourId);

        $request->validate([
            'photo' => 'required|image|max:10240',
        ]);

        $path = $request->file('photo')->store('tours/'.$tourId, 'public');

        $maxOrder = $tour->photos()->max('order') ?? 0;

        $photo = $tour->photos()->create([
            'path' => $path,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'id' => $photo->id,
            'path' => $photo->path,
            'url' => asset('storage/'.$photo->path),
            'order' => $photo->order,
        ], 201);
    }

    public function reorder(Request $request, int $tourId): JsonResponse
    {
        Tour::findOrFail($tourId);

        $request->validate([
            'photos' => 'required|array',
            'photos.*.id' => 'required|integer',
            'photos.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->input('photos') as $item) {
            TourPhoto::where('tour_id', $tourId)
                ->where('id', $item['id'])
                ->update(['order' => $item['order']]);
        }

        return response()->json(null, 204);
    }

    public function destroy(int $tourId, int $photoId): JsonResponse
    {
        $photo = TourPhoto::where('tour_id', $tourId)->findOrFail($photoId);
        \Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(null, 204);
    }
}
