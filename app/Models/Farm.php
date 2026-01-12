<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farm_code',
        'name',
        'legal_name',
        'farm_type',
        'country',
        'state',
        'town',
        'default_currency',
        'default_unit_system',
        'default_timezone',
        'daily_log_cutoff_time',
        'accounting_method',
        'status',
        'created_by',
        'meta',
        'site_id',
        'admin_zone_id',
        'description',
        'total_area',
        'area_unit',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'meta' => 'array',
            'is_active' => 'boolean',
            'total_area' => 'decimal:2',
            // daily_log_cutoff_time is stored as time, no cast needed
        ];
    }
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($farm) {
            // Auto-generate farm_code if not provided
            if (empty($farm->farm_code)) {
                $farm->farm_code = app(\App\Services\Farm\FarmCodeGeneratorService::class)->generate();
            }
            
            // Set created_by if not provided and user is authenticated
            if (empty($farm->created_by) && auth()->check()) {
                $farm->created_by = auth()->id();
            }
        });
        
        static::updating(function ($farm) {
            // Auto-generate farm_code if missing (for existing farms created before this field was added)
            if (empty($farm->farm_code)) {
                $farm->farm_code = app(\App\Services\Farm\FarmCodeGeneratorService::class)->generate();
            }
        });
    }

    /**
     * Get the users that belong to this farm.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Get the seasons for this farm.
     */
    public function seasons()
    {
        return $this->hasMany(Season::class);
    }

    /**
     * Get the fields for this farm.
     */
    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    /**
     * Get the crop plans for this farm.
     */
    public function cropPlans()
    {
        return $this->hasMany(CropPlan::class);
    }

    /**
     * Get the harvest lots for this farm.
     */
    public function harvestLots()
    {
        return $this->hasMany(HarvestLot::class);
    }

    /**
     * Get the livestock groups for this farm.
     */
    public function livestockGroups()
    {
        return $this->hasMany(LivestockGroup::class);
    }

    /**
     * Get the animals for this farm.
     */
    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    /**
     * Get the tasks for this farm.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the workers for this farm.
     */
    public function workers()
    {
        return $this->hasMany(Worker::class);
    }

    /**
     * Get the sites for this farm.
     */
    public function sites()
    {
        return $this->hasMany(Site::class);
    }


    /**
     * Get active worker job roles for this farm.
     */
    public function activeWorkerJobRoles()
    {
        return $this->hasMany(WorkerJobRole::class)->where('is_active', true);
    }

    /**
     * Get the input items for this farm.
     */
    public function inputItems()
    {
        return $this->hasMany(InputItem::class);
    }

    /**
     * Get the inventory locations for this farm.
     */
    public function inventoryLocations()
    {
        return $this->hasMany(InventoryLocation::class);
    }

    /**
     * Get the location for this farm.
     */
    public function location()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the site for this farm (alias for location).
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Get the admin zone for this farm.
     */
    public function adminZone()
    {
        return $this->belongsTo(AdminZone::class, 'admin_zone_id');
    }
    
    /**
     * Get the user who created this farm.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get the assets for this farm.
     */
    public function assets()
    {
        return $this->hasMany(\App\Models\Asset::class);
    }
    
    /**
     * Get a formatted location label.
     *
     * @return string
     */
    public function locationLabel(): string
    {
        $parts = array_filter([$this->town, $this->state, $this->country]);
        return implode(', ', $parts) ?: 'Location not specified';
    }
    
    /**
     * Check if the farm is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' || ($this->status === null && $this->is_active === true);
    }
}
