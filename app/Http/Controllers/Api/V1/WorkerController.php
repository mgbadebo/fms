<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Worker::with(['farm', 'user']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        $workers = $query->orderBy('name')->paginate(20);
        return response()->json($workers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string',
            'type' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $worker = Worker::create($validated);

        return response()->json(['data' => $worker->load('farm', 'user')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $worker = Worker::with(['farm', 'user', 'staffAssignments.assignable'])->findOrFail($id);
        return response()->json(['data' => $worker]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $worker = Worker::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact' => 'nullable|string',
            'type' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $worker->update($validated);

        return response()->json(['data' => $worker->load('farm', 'user')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $worker = Worker::findOrFail($id);
        $worker->delete();

        return response()->json(null, 204);
    }
}
