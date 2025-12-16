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
    });
});

