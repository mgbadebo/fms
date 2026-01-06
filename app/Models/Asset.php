<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_id',
        'asset_category_id',
        'asset_code',
        'name',
        'description',
        'status',
        'acquisition_type',
        'purchase_date',
        'purchase_cost',
        'currency',
        'supplier_name',
        'serial_number',
        'model',
        'manufacturer',
        'year_of_make',
        'warranty_expiry',
        'location_text',
        'location_field_id',
        'location_zone_id',
        'gps_lat',
        'gps_lng',
        'is_trackable',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
            'warranty_expiry' => 'date',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'is_trackable' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function locationField()
    {
        return $this->belongsTo(Field::class, 'location_field_id');
    }

    public function locationZone()
    {
        return $this->belongsTo(Zone::class, 'location_zone_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_at');
    }

    public function maintenancePlans()
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }

    public function insurancePolicies()
    {
        return $this->hasMany(AssetInsurancePolicy::class);
    }

    public function depreciationProfile()
    {
        return $this->hasOne(AssetDepreciationProfile::class);
    }

    public function attachments()
    {
        return $this->hasMany(AssetAttachment::class);
    }
}
