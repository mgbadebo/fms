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
use App\Http\Controllers\Api\V1\BoreholeController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\AdminZoneController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserManagementController;

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
        
        // Admin Settings - Locations and Zones
        Route::apiResource('locations', LocationController::class);
        Route::apiResource('admin-zones', AdminZoneController::class);
        
        // Role and User Management (Admin only)
        Route::get('roles/menu-permissions', [RoleController::class, 'menuPermissions']);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('users', UserManagementController::class);
    });
});

