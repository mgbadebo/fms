<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRecord;
use App\Models\FuelLog;
use App\Models\AssetInsurancePolicy;
use App\Models\AssetDepreciationProfile;
use App\Models\AssetAttachment;
use App\Services\Asset\AssetCodeGeneratorService;
use App\Services\Asset\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetController extends Controller
{
    protected AssetCodeGeneratorService $codeGenerator;
    protected DepreciationService $depreciationService;

    public function __construct(
        AssetCodeGeneratorService $codeGenerator,
        DepreciationService $depreciationService
    ) {
        $this->codeGenerator = $codeGenerator;
        $this->depreciationService = $depreciationService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['category', 'locationField', 'locationZone', 'activeAssignment']);

        if ($request->has('farm_id')) {
            $query->where('farm_id', $request->farm_id);
        }

        if ($request->has('asset_category_id')) {
            $query->where('asset_category_id', $request->asset_category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('location_field_id')) {
            $query->where('location_field_id', $request->location_field_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $assets = $query->paginate(20);
        return response()->json($assets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,id',
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'asset_code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,INACTIVE,UNDER_REPAIR,DISPOSED,SOLD,LOST',
            'acquisition_type' => 'nullable|in:PURCHASED,LEASED,RENTED,DONATED',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'supplier_name' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'year_of_make' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'warranty_expiry' => 'nullable|date',
            'location_text' => 'nullable|string',
            'location_field_id' => 'nullable|exists:fields,id',
            'location_zone_id' => 'nullable|exists:zones,id',
            'gps_lat' => 'nullable|numeric|between:-90,90',
            'gps_lng' => 'nullable|numeric|between:-180,180',
            'is_trackable' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Generate asset code if not provided
        if (empty($validated['asset_code'])) {
            $validated['asset_code'] = $this->codeGenerator->generate(
                $validated['farm_id'],
                $validated['asset_category_id'] ?? null
            );
        } else {
            // Validate uniqueness
            if (!$this->codeGenerator->isUnique($validated['asset_code'], $validated['farm_id'])) {
                return response()->json([
                    'message' => 'Asset code already exists for this farm'
                ], 422);
            }
        }

        $validated['currency'] = $validated['currency'] ?? 'NGN';
        $validated['status'] = $validated['status'] ?? 'ACTIVE';
        $validated['is_trackable'] = $validated['is_trackable'] ?? true;
        $validated['created_by'] = $request->user()?->id;

        $asset = Asset::create($validated);
        return response()->json(['data' => $asset->load(['category', 'locationField', 'locationZone'])], 201);
    }

    public function show(string $id): JsonResponse
    {
        $asset = Asset::with([
            'category',
            'locationField',
            'locationZone',
            'activeAssignment.assignedTo',
            'maintenancePlans',
            'maintenanceRecords',
            'fuelLogs',
            'insurancePolicies',
            'depreciationProfile',
            'attachments'
        ])->findOrFail($id);
        
        return response()->json(['data' => $asset]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'asset_category_id' => 'sometimes|exists:asset_categories,id',
            'asset_code' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,INACTIVE,UNDER_REPAIR,DISPOSED,SOLD,LOST',
            'acquisition_type' => 'nullable|in:PURCHASED,LEASED,RENTED,DONATED',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'supplier_name' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'year_of_make' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'warranty_expiry' => 'nullable|date',
            'location_text' => 'nullable|string',
            'location_field_id' => 'nullable|exists:fields,id',
            'location_zone_id' => 'nullable|exists:zones,id',
            'gps_lat' => 'nullable|numeric|between:-90,90',
            'gps_lng' => 'nullable|numeric|between:-180,180',
            'is_trackable' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Validate asset_code uniqueness if changed
        if (isset($validated['asset_code']) && $validated['asset_code'] !== $asset->asset_code) {
            if (!$this->codeGenerator->isUnique($validated['asset_code'], $asset->farm_id, $asset->id)) {
                return response()->json([
                    'message' => 'Asset code already exists for this farm'
                ], 422);
            }
        }

        $asset->update($validated);
        return response()->json(['data' => $asset->load(['category', 'locationField', 'locationZone'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        
        // Soft delete or set status to DISPOSED
        $asset->status = 'DISPOSED';
        $asset->save();
        $asset->delete();

        return response()->json(null, 204);
    }

    // ========== ASSIGNMENTS ==========

    public function assign(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'assigned_to_type' => 'required|string',
            'assigned_to_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        // Enforce single active assignment
        $activeAssignment = AssetAssignment::where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->first();

        if ($activeAssignment) {
            return response()->json([
                'message' => 'Asset is already assigned. Please return it first.'
            ], 422);
        }

        $assignment = AssetAssignment::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'assigned_to_type' => $validated['assigned_to_type'],
            'assigned_to_id' => $validated['assigned_to_id'],
            'assigned_by' => $request->user()?->id,
            'assigned_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $assignment->load('assignedTo', 'assignedBy')], 201);
    }

    public function returnAssignment(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $activeAssignment = AssetAssignment::where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->firstOrFail();

        $activeAssignment->update([
            'returned_at' => now(),
        ]);

        return response()->json(['data' => $activeAssignment->load('assignedTo', 'assignedBy')]);
    }

    public function assignments(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $assignments = AssetAssignment::where('asset_id', $asset->id)
            ->with(['assignedTo', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->paginate(20);

        return response()->json($assignments);
    }

    // ========== MAINTENANCE PLANS ==========

    public function storeMaintenancePlan(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'plan_type' => 'required|in:HOURS,DAYS,MONTHS,USAGE',
            'interval_value' => 'required|integer|min:1',
            'last_service_at' => 'nullable|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $lastServiceAt = $validated['last_service_at'] ? Carbon::parse($validated['last_service_at']) : now();
        $nextDueAt = $this->calculateNextDueDate(
            $lastServiceAt,
            $validated['plan_type'],
            $validated['interval_value']
        );

        $plan = MaintenancePlan::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'plan_type' => $validated['plan_type'],
            'interval_value' => $validated['interval_value'],
            'last_service_at' => $lastServiceAt,
            'next_due_at' => $nextDueAt,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $plan], 201);
    }

    public function updateMaintenancePlan(Request $request, string $assetId, string $planId): JsonResponse
    {
        $plan = MaintenancePlan::where('asset_id', $assetId)->findOrFail($planId);

        $validated = $request->validate([
            'plan_type' => 'sometimes|in:HOURS,DAYS,MONTHS,USAGE',
            'interval_value' => 'sometimes|integer|min:1',
            'last_service_at' => 'nullable|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Recalculate next_due_at if relevant fields changed
        if (isset($validated['last_service_at']) || isset($validated['plan_type']) || isset($validated['interval_value'])) {
            $lastServiceAt = $validated['last_service_at'] 
                ? Carbon::parse($validated['last_service_at']) 
                : ($plan->last_service_at ?? now());
            $planType = $validated['plan_type'] ?? $plan->plan_type;
            $intervalValue = $validated['interval_value'] ?? $plan->interval_value;
            
            $validated['next_due_at'] = $this->calculateNextDueDate($lastServiceAt, $planType, $intervalValue);
        }

        $plan->update($validated);
        return response()->json(['data' => $plan]);
    }

    public function maintenancePlans(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $plans = MaintenancePlan::where('asset_id', $asset->id)->paginate(20);
        return response()->json($plans);
    }

    // ========== MAINTENANCE RECORDS ==========

    public function storeMaintenanceRecord(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'performed_at' => 'required|date',
            'type' => 'required|in:SERVICE,REPAIR,INSPECTION',
            'vendor_name' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'odometer_or_hours' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'parts_used' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $record = MaintenanceRecord::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'performed_at' => $validated['performed_at'],
            'type' => $validated['type'],
            'vendor_name' => $validated['vendor_name'] ?? null,
            'cost' => $validated['cost'] ?? null,
            'currency' => $validated['currency'] ?? 'NGN',
            'odometer_or_hours' => $validated['odometer_or_hours'] ?? null,
            'description' => $validated['description'] ?? null,
            'parts_used' => $validated['parts_used'] ?? null,
            'created_by' => $request->user()?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update maintenance plans
        $this->updateMaintenancePlansAfterRecord($asset, Carbon::parse($validated['performed_at']));

        return response()->json(['data' => $record->load('createdBy')], 201);
    }

    public function maintenanceRecords(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $records = MaintenanceRecord::where('asset_id', $asset->id)
            ->with('createdBy')
            ->orderBy('performed_at', 'desc')
            ->paginate(20);

        return response()->json($records);
    }

    // ========== FUEL LOGS ==========

    public function storeFuelLog(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'filled_at' => 'required|date',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|in:LITRE,GALLON',
            'cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'supplier' => 'nullable|string|max:255',
            'operator_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $log = FuelLog::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'filled_at' => $validated['filled_at'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'cost' => $validated['cost'] ?? null,
            'currency' => $validated['currency'] ?? 'NGN',
            'supplier' => $validated['supplier'] ?? null,
            'operator_id' => $validated['operator_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $log->load('operator')], 201);
    }

    public function fuelLogs(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $logs = FuelLog::where('asset_id', $asset->id)
            ->with('operator')
            ->orderBy('filled_at', 'desc')
            ->paginate(20);

        return response()->json($logs);
    }

    // ========== INSURANCE POLICIES ==========

    public function storeInsurancePolicy(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'insurer_name' => 'required|string|max:255',
            'policy_number' => 'required|string|max:255',
            'coverage_start' => 'required|date',
            'coverage_end' => 'required|date|after:coverage_start',
            'insured_value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'premium' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $policy = AssetInsurancePolicy::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'insurer_name' => $validated['insurer_name'],
            'policy_number' => $validated['policy_number'],
            'coverage_start' => $validated['coverage_start'],
            'coverage_end' => $validated['coverage_end'],
            'insured_value' => $validated['insured_value'],
            'currency' => $validated['currency'] ?? 'NGN',
            'premium' => $validated['premium'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $policy], 201);
    }

    public function insurancePolicies(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $policies = AssetInsurancePolicy::where('asset_id', $asset->id)
            ->orderBy('coverage_end', 'desc')
            ->paginate(20);

        return response()->json($policies);
    }

    // ========== DEPRECIATION ==========

    public function storeDepreciationProfile(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        // Check if profile already exists
        if ($asset->depreciationProfile) {
            return response()->json([
                'message' => 'Depreciation profile already exists for this asset'
            ], 422);
        }

        $validated = $request->validate([
            'method' => 'required|in:STRAIGHT_LINE,REDUCING_BALANCE',
            'useful_life_months' => 'required|integer|min:1',
            'salvage_value' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
        ]);

        $profile = AssetDepreciationProfile::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'method' => $validated['method'],
            'useful_life_months' => $validated['useful_life_months'],
            'salvage_value' => $validated['salvage_value'] ?? null,
            'start_date' => $validated['start_date'],
        ]);

        return response()->json(['data' => $profile], 201);
    }

    public function depreciationProfile(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $profile = $asset->depreciationProfile;
        
        if (!$profile) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $profile]);
    }

    public function depreciationSchedule(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        
        $toDate = $request->has('to') 
            ? Carbon::parse($request->to) 
            : now();

        $schedule = $this->depreciationService->calculateSchedule($asset, $toDate);

        return response()->json([
            'data' => [
                'asset_id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'purchase_cost' => $asset->purchase_cost,
                'schedule' => $schedule,
            ]
        ]);
    }

    // ========== ATTACHMENTS ==========

    public function storeAttachment(Request $request, string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('assets/' . $asset->id, 'public');

        $attachment = AssetAttachment::create([
            'farm_id' => $asset->farm_id,
            'asset_id' => $asset->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $attachment->load('uploadedBy')], 201);
    }

    public function attachments(string $id): JsonResponse
    {
        $asset = Asset::findOrFail($id);
        $attachments = AssetAttachment::where('asset_id', $asset->id)
            ->with('uploadedBy')
            ->orderBy('uploaded_at', 'desc')
            ->paginate(20);

        return response()->json($attachments);
    }

    public function destroyAttachment(string $assetId, string $attachmentId): JsonResponse
    {
        $attachment = AssetAttachment::where('asset_id', $assetId)->findOrFail($attachmentId);
        
        // Delete file
        Storage::disk('public')->delete($attachment->file_path);
        
        $attachment->delete();
        return response()->json(null, 204);
    }

    // ========== HELPER METHODS ==========

    protected function calculateNextDueDate(Carbon $lastServiceAt, string $planType, int $intervalValue): Carbon
    {
        return match($planType) {
            'HOURS' => $lastServiceAt->copy()->addHours($intervalValue),
            'DAYS' => $lastServiceAt->copy()->addDays($intervalValue),
            'MONTHS' => $lastServiceAt->copy()->addMonths($intervalValue),
            'USAGE' => $lastServiceAt->copy()->addMonths($intervalValue), // Default to months for USAGE
            default => $lastServiceAt->copy()->addMonths($intervalValue),
        };
    }

    protected function updateMaintenancePlansAfterRecord(Asset $asset, Carbon $performedAt): void
    {
        $activePlans = MaintenancePlan::where('asset_id', $asset->id)
            ->where('is_active', true)
            ->get();

        foreach ($activePlans as $plan) {
            $plan->update([
                'last_service_at' => $performedAt,
                'next_due_at' => $this->calculateNextDueDate(
                    $performedAt,
                    $plan->plan_type,
                    $plan->interval_value
                ),
            ]);
        }
    }
}
