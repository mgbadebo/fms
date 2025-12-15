<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LabelTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LabelTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LabelTemplate::with('farm');

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        $templates = $query->paginate(20);
        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:label_templates,code',
            'target_type' => 'required|in:HARVEST_LOT,STORAGE_UNIT,SALES_ORDER',
            'template_engine' => 'required|in:ZPL,BLADE,RAW',
            'template_body' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $template = LabelTemplate::create($validated);

        return response()->json(['data' => $template], 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = LabelTemplate::with('farm', 'printedLabels')->findOrFail($id);
        return response()->json(['data' => $template]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $template = LabelTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:label_templates,code,' . $id,
            'target_type' => 'sometimes|in:HARVEST_LOT,STORAGE_UNIT,SALES_ORDER',
            'template_engine' => 'sometimes|in:ZPL,BLADE,RAW',
            'template_body' => 'sometimes|string',
            'is_default' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json(['data' => $template]);
    }

    public function destroy(string $id): JsonResponse
    {
        $template = LabelTemplate::findOrFail($id);
        $template->delete();

        return response()->json(null, 204);
    }
}
