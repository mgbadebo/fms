<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'location_id',
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
            'is_active' => 'boolean',
            'total_area' => 'decimal:2',
        ];
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
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the admin zone for this farm.
     */
    public function adminZone()
    {
        return $this->belongsTo(AdminZone::class, 'admin_zone_id');
    }
}
