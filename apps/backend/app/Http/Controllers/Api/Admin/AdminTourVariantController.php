<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourVariantResource;
use App\Models\TourVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTourVariantController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tour_id'       => 'required|exists:tours,id',
            'date'          => 'required|date',
            'duration_days' => 'nullable|integer|min:1',
            'price'         => 'required|numeric|min:0',
        ]);

        $variant = TourVariant::create($data);

        return response()->json(new TourVariantResource($variant), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $variant = TourVariant::findOrFail($id);

        $data = $request->validate([
            'date'          => 'sometimes|date',
            'duration_days' => 'nullable|integer|min:1',
            'price'         => 'sometimes|numeric|min:0',
        ]);

        $variant->update($data);

        return response()->json(new TourVariantResource($variant));
    }

    public function destroy(int $id): JsonResponse
    {
        TourVariant::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
