<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScaleDevice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScaleDeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ScaleDevice::with('farm');

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $devices = $query->paginate(20);
        return response()->json($devices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'connection_type' => 'required|in:SERIAL,USB,BLUETOOTH,TCP_IP,MOCK',
            'connection_config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $device = ScaleDevice::create($validated);

        return response()->json(['data' => $device], 201);
    }

    public function show(string $id): JsonResponse
    {
        $device = ScaleDevice::with('farm', 'weighingRecords')->findOrFail($id);
        return response()->json(['data' => $device]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $device = ScaleDevice::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'connection_type' => 'sometimes|in:SERIAL,USB,BLUETOOTH,TCP_IP,MOCK',
            'connection_config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $device->update($validated);

        return response()->json(['data' => $device]);
    }

    public function destroy(string $id): JsonResponse
    {
        $device = ScaleDevice::findOrFail($id);
        $device->delete();

        return response()->json(null, 204);
    }
}
