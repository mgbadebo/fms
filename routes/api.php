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
    });
});

