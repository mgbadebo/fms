<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FarmController;
use App\Http\Controllers\Api\V1\HarvestLotController;
use App\Http\Controllers\Api\V1\ScaleReadingController;
use App\Http\Controllers\Api\V1\LabelController;
use App\Http\Controllers\Api\V1\ScaleDeviceController;
use App\Http\Controllers\Api\V1\LabelTemplateController;
use App\Http\Controllers\Api\V1\FieldController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Http\Controllers\Api\V1\CropController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\CropPlanController;
use App\Http\Controllers\Api\V1\GariProductionBatchController;
use App\Http\Controllers\Api\V1\CassavaInputController;
use App\Http\Controllers\Api\V1\GariInventoryController;
use App\Http\Controllers\Api\V1\PackagingMaterialController;
use App\Http\Controllers\Api\V1\GariSaleController;
use App\Http\Controllers\Api\V1\GariWasteLossController;
use App\Http\Controllers\Api\V1\GreenhouseController;
use App\Http\Controllers\Api\V1\BellPepperCycleController;
use App\Http\Controllers\Api\V1\BellPepperCycleCostController;
use App\Http\Controllers\Api\V1\BellPepperHarvestController;
use App\Http\Controllers\Api\V1\BellPepperSaleController;
use App\Http\Controllers\Api\V1\ProductionCycleController;
use App\Http\Controllers\Api\V1\ActivityTypeController;
use App\Http\Controllers\Api\V1\DailyLogController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\KpiController;
use App\Http\Controllers\Api\V1\HarvestRecordController;
use App\Http\Controllers\Api\V1\HarvestCrateController;
use App\Http\Controllers\Api\V1\HarvestTotalsController;
use App\Http\Controllers\Api\V1\BoreholeController;
use App\Http\Controllers\Api\V1\AdminZoneController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserManagementController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\FarmZoneController;
use App\Http\Controllers\Api\V1\FactoryController;
use App\Http\Controllers\Api\V1\StaffAssignmentController;
use App\Http\Controllers\Api\V1\WorkerController;
use App\Http\Controllers\Api\V1\AssetCategoryController;
use App\Http\Controllers\Api\V1\SiteTypeController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AssetAssignmentController;
use App\Http\Controllers\Api\V1\WorkerJobRoleController;
use App\Http\Controllers\Api\V1\PermissionController;

Route::prefix('v1')->group(function () {
    // Public authentication routes
    Route::post('/login', function (Illuminate\Http\Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        $user->load('roles.permissions', 'permissions');

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    });

    Route::post('/register', function (Illuminate\Http\Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    });
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Get current user with roles and permissions
        Route::get('/me', function (Illuminate\Http\Request $request) {
            $user = $request->user();
            $user->load('roles.permissions', 'permissions');
            return response()->json(['data' => $user]);
        });
        // Farms
        Route::apiResource('farms', FarmController::class);
        
        // Farm Mapping & GIS
        Route::apiResource('fields', FieldController::class);
        Route::apiResource('zones', ZoneController::class);
        
        // Seasons
        Route::apiResource('seasons', SeasonController::class);
        
        // Crop Management
        Route::apiResource('crops', CropController::class);
        Route::apiResource('crop-plans', CropPlanController::class);
        
        // Harvest Lots
        Route::apiResource('harvest-lots', HarvestLotController::class);
        
        // Scale Devices
        Route::apiResource('scale-devices', ScaleDeviceController::class);
        
        // Scale Readings
        Route::post('scale-readings', [ScaleReadingController::class, 'store']);
        Route::get('scale-readings', [ScaleReadingController::class, 'index']);
        
        // Label Templates
        Route::apiResource('label-templates', LabelTemplateController::class);
        
        // Label Printing
        Route::post('labels/print', [LabelController::class, 'print']);
        
        // Gari Production System
        Route::apiResource('gari-production-batches', GariProductionBatchController::class);
        Route::apiResource('cassava-inputs', CassavaInputController::class);
        // Gari Inventory - custom routes must come before apiResource to avoid route conflicts
        Route::get('gari-inventory/summary', [GariInventoryController::class, 'summary']);
        Route::apiResource('gari-inventory', GariInventoryController::class);
        Route::apiResource('packaging-materials', PackagingMaterialController::class);
        // Gari Sales - custom routes must come before apiResource to avoid route conflicts
        Route::get('gari-sales/summary', [GariSaleController::class, 'summary']);
        Route::get('gari-sales/available-batches', [GariSaleController::class, 'getAvailableBatches']);
        Route::apiResource('gari-sales', GariSaleController::class);
        Route::apiResource('gari-waste-losses', GariWasteLossController::class);
        
        // Bell Pepper Greenhouse Management
        Route::apiResource('greenhouses', GreenhouseController::class);
    Route::apiResource('boreholes', \App\Http\Controllers\Api\V1\BoreholeController::class);
        
        // Boreholes
        Route::apiResource('boreholes', BoreholeController::class);
        
        // Bell Pepper Cycles
        Route::apiResource('bell-pepper-cycles', BellPepperCycleController::class);
        
        // Bell Pepper Cycle Costs
        Route::apiResource('bell-pepper-cycle-costs', BellPepperCycleCostController::class);
        
        // Bell Pepper Harvests
        Route::apiResource('bell-pepper-harvests', BellPepperHarvestController::class);
        
        // Bell Pepper Sales
        Route::apiResource('bell-pepper-sales', BellPepperSaleController::class);
        
        // Greenhouse Production Cycles (New Structured Workflow)
        Route::apiResource('production-cycles', ProductionCycleController::class);
        Route::post('production-cycles/{id}/start', [ProductionCycleController::class, 'start']);
        Route::post('production-cycles/{id}/complete', [ProductionCycleController::class, 'complete']);
        
        // Activity Types (farm-scoped)
        Route::apiResource('activity-types', ActivityTypeController::class);
        
        // Daily Logs
        Route::get('production-cycles/{production_cycle_id}/daily-logs', [DailyLogController::class, 'index']);
        Route::post('production-cycles/{production_cycle_id}/daily-logs', [DailyLogController::class, 'store']);
        Route::get('greenhouses/{greenhouse_id}/daily-logs', [DailyLogController::class, 'indexByGreenhouse']);
        Route::get('daily-logs/{id}', [DailyLogController::class, 'show']);
        Route::patch('daily-logs/{id}', [DailyLogController::class, 'update']);
        Route::post('daily-logs/{id}/submit', [DailyLogController::class, 'submit']);
        
        // Sales Module
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::post('sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);
        Route::post('sales-orders/{id}/dispatch', [SalesOrderController::class, 'dispatch']);
        Route::post('sales-orders/{id}/invoice', [SalesOrderController::class, 'invoice']);
        Route::post('sales-orders/{id}/cancel', [SalesOrderController::class, 'cancel']);
        Route::get('sales-orders/{sales_order_id}/payments', [PaymentController::class, 'index']);
        Route::post('sales-orders/{sales_order_id}/payments', [PaymentController::class, 'store']);
        
        // KPI Reporting
        Route::get('kpis/sales-summary', [KpiController::class, 'salesSummary']);
        Route::get('kpis/production-profitability', [KpiController::class, 'productionProfitability']);
        Route::get('kpis/operations-compliance', [KpiController::class, 'operationsCompliance']);
        
        // Harvest Records
        Route::apiResource('harvest-records', HarvestRecordController::class);
        Route::post('harvest-records/{id}/submit', [HarvestRecordController::class, 'submit']);
        Route::post('harvest-records/{id}/approve', [HarvestRecordController::class, 'approve']);
        
        // Harvest Crates
        Route::get('harvest-records/{harvest_record_id}/crates', [HarvestCrateController::class, 'index']);
        Route::post('harvest-records/{harvest_record_id}/crates', [HarvestCrateController::class, 'store']);
        Route::patch('harvest-crates/{id}', [HarvestCrateController::class, 'update']);
        Route::delete('harvest-crates/{id}', [HarvestCrateController::class, 'destroy']);
        
        // Harvest Totals
        Route::get('harvest-totals/daily', [HarvestTotalsController::class, 'daily']);
        
        // Admin Settings - Zones
        Route::apiResource('admin-zones', AdminZoneController::class);
        
        // Role and User Management (Admin only)
        Route::get('roles/menu-permissions', [RoleController::class, 'menuPermissions']);
        Route::apiResource('roles', RoleController::class);
        
        // User Management with multi-farm support
        Route::apiResource('users', UserManagementController::class);
        Route::post('users/{user}/farms/attach', [UserManagementController::class, 'attachToFarm']);
        Route::patch('users/{user}/farms/{farm}/membership', [UserManagementController::class, 'updateFarmMembership']);
        Route::post('users/{user}/farms/{farm}/detach', [UserManagementController::class, 'detachFromFarm']);
        Route::post('users/{user}/farms/{farm}/job-roles/assign', [UserManagementController::class, 'assignJobRole']);
        Route::post('users/{user}/farms/{farm}/job-roles/{assignment}/end', [UserManagementController::class, 'endJobRole']);
        Route::get('users/{user}/farms/{farm}/job-roles', [UserManagementController::class, 'getJobRoles']);
        Route::post('users/{user}/photo', [UserManagementController::class, 'uploadPhoto']);
        Route::delete('users/{user}/photo', [UserManagementController::class, 'deletePhoto']);
        
        // Worker Job Roles (farm-scoped)
        Route::apiResource('worker-job-roles', WorkerJobRoleController::class);
        
        // Permissions Management
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::get('users/{user}/permissions', [PermissionController::class, 'getUserPermissions']);
        Route::post('users/{user}/permissions/grant', [PermissionController::class, 'grantPermissions']);
        Route::post('users/{user}/permissions/revoke', [PermissionController::class, 'revokePermissions']);
        
        // Site Management (Admin only)
        Route::apiResource('sites', SiteController::class);
        Route::apiResource('site-types', SiteTypeController::class);
        
        // Farm Zone Management (Admin only)
        Route::apiResource('farm-zones', FarmZoneController::class);
        
        // Factory Management (Admin only)
        Route::apiResource('factories', FactoryController::class);
        
        // Staff Assignment Management (Admin only)
        Route::post('staff-assignments/{id}/end', [StaffAssignmentController::class, 'endAssignment']);
        Route::apiResource('staff-assignments', StaffAssignmentController::class);
        
        // Worker Management
        Route::apiResource('workers', WorkerController::class);
        
        // Asset Tracker routes
        Route::apiResource('asset-categories', AssetCategoryController::class);
        Route::apiResource('assets', AssetController::class);
        Route::get('assets/{asset}/assignments', [AssetController::class, 'assignments']);
        Route::post('assets/{asset}/assign', [AssetController::class, 'assign']);
        Route::post('assets/{asset}/return', [AssetController::class, 'returnAssignment']);
        Route::get('assets/{asset}/maintenance-plans', [AssetController::class, 'maintenancePlans']);
        Route::post('assets/{asset}/maintenance-plans', [AssetController::class, 'storeMaintenancePlan']);
        Route::patch('assets/{asset}/maintenance-plans/{plan}', [AssetController::class, 'updateMaintenancePlan']);
        Route::get('assets/{asset}/maintenance-records', [AssetController::class, 'maintenanceRecords']);
        Route::post('assets/{asset}/maintenance-records', [AssetController::class, 'storeMaintenanceRecord']);
        Route::get('assets/{asset}/fuel-logs', [AssetController::class, 'fuelLogs']);
        Route::post('assets/{asset}/fuel-logs', [AssetController::class, 'storeFuelLog']);
        Route::get('assets/{asset}/insurance-policies', [AssetController::class, 'insurancePolicies']);
        Route::post('assets/{asset}/insurance-policies', [AssetController::class, 'storeInsurancePolicy']);
        Route::get('assets/{asset}/depreciation-profile', [AssetController::class, 'depreciationProfile']);
        Route::post('assets/{asset}/depreciation-profile', [AssetController::class, 'storeDepreciationProfile']);
        Route::get('assets/{asset}/depreciation-schedule', [AssetController::class, 'depreciationSchedule']);
        Route::get('assets/{asset}/attachments', [AssetController::class, 'attachments']);
        Route::post('assets/{asset}/attachments', [AssetController::class, 'storeAttachment']);
        Route::delete('assets/{asset}/attachments/{attachment}', [AssetController::class, 'destroyAttachment']);
        Route::get('asset-assignments', [AssetAssignmentController::class, 'index']);
    });
});

