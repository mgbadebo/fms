<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScaleDevice;
use App\Models\WeighingRecord;
use App\Services\Scale\MockScaleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScaleReadingController extends Controller
{
    public function __construct(
        protected MockScaleService $scaleService
    ) {}

    /**
     * Store a new scale reading.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scale_device_id' => 'required|exists:scale_devices,id',
            'context_type' => 'required|string|in:App\Models\HarvestLot,App\Models\StorageUnit,App\Models\SalesOrder',
            'context_id' => 'required|integer',
            'unit' => 'sometimes|string',
        ]);

        $scaleDevice = ScaleDevice::findOrFail($validated['scale_device_id']);

        // Verify context exists
        $contextClass = $validated['context_type'];
        $context = $contextClass::findOrFail($validated['context_id']);

        // Read weight from scale
        $config = array_merge(
            $scaleDevice->connection_config ?? [],
            ['unit' => $validated['unit'] ?? 'kg']
        );

        $weightData = $this->scaleService->readWeight($config);

        // Store weighing record
        $weighingRecord = WeighingRecord::create([
            'farm_id' => $scaleDevice->farm_id,
            'scale_device_id' => $scaleDevice->id,
            'context_type' => $validated['context_type'],
            'context_id' => $validated['context_id'],
            'gross_weight' => $weightData['gross'],
            'tare_weight' => $weightData['tare'],
            'net_weight' => $weightData['net'],
            'unit' => $weightData['unit'],
            'weighed_at' => now(),
            'operator_id' => $request->user()->id,
            'raw_payload' => $weightData,
        ]);

        // Optionally update context with net weight (e.g., HarvestLot)
        if ($context instanceof \App\Models\HarvestLot) {
            $context->update([
                'net_weight' => $weightData['net'],
                'gross_weight' => $weightData['gross'],
                'weight_unit' => $weightData['unit'],
            ]);
        }

        return response()->json([
            'data' => $weighingRecord->load('scaleDevice', 'context', 'operator'),
        ], 201);
    }

    /**
     * List scale readings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WeighingRecord::with(['scaleDevice', 'context', 'operator']);

        if ($request->has('context_type') && $request->has('context_id')) {
            $query->where('context_type', $request->context_type)
                  ->where('context_id', $request->context_id);
        }

        if ($request->has('scale_device_id')) {
            $query->where('scale_device_id', $request->scale_device_id);
        }

        $readings = $query->latest('weighed_at')->paginate(20);

        return response()->json($readings);
    }
}
