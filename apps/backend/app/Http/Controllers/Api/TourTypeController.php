<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourTypeResource;
use App\Models\TourType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TourTypeController extends Controller
{
    public function index()
    {
        return TourTypeResource::collection(TourType::orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:tour_types,name',
            'slug' => 'nullable|string|max:255|unique:tour_types,slug',
            'icon' => 'nullable|string',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $type = TourType::create($data);

        return response()->json(new TourTypeResource($type), 201);
    }

    public function update(Request $request, int $id)
    {
        $type = TourType::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:tour_types,name,' . $id,
            'slug' => 'nullable|string|max:255|unique:tour_types,slug,' . $id,
            'icon' => 'nullable|string',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $type->update($data);

        return response()->json(new TourTypeResource($type));
    }

    public function destroy(int $id)
    {
        TourType::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
