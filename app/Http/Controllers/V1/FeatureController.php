<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * Display a listing of the category feature.
     */
    public function index()
    {
        $features = Feature::all();
        return ResponseFormatter::success(200, 'OK', $features);
    }

    /**
     * Store a newly created category features in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'icon' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        $feature = Feature::create($request->all());
        return ResponseFormatter::success(200, 'OK', $feature);
    }

    /**
     * Display the specified category features.
     */
    public function show(string $id)
    {
        $feature = Feature::find($id);

        if (!$feature) {
            return ResponseFormatter::error(404, 'Not Found');
        }

        return ResponseFormatter::success(200, 'OK', $feature);
    }

    /**
     * Update the specified category features in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'icon' => 'required',
            'name' => 'required',
            'description' => 'required',
        ]);

        $feature = Feature::find($id);

        if (!$feature) {
            return ResponseFormatter::error(404, 'Not Found');
        }

        $feature->update($request->all());

        return ResponseFormatter::success(200, 'OK', $feature);
    }

    /**
     * Remove the specified category features from storage.
     */
    public function destroy(string $id)
    {
        $feature = Feature::find($id);
        $feature->delete();

        return ResponseFormatter::success(200, 'OK', $feature);
    }
}
