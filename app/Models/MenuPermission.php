<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_key',
        'submenu_key',
        'permission_type',
        'name',
        'description',
        'sort_order',
    ];

    /**
     * Get the permission name in Spatie format
     * e.g., "bell-pepper.harvests.view"
     */
    public function getPermissionNameAttribute(): string
    {
        $parts = [$this->menu_key];
        if ($this->submenu_key) {
            $parts[] = $this->submenu_key;
        }
        $parts[] = $this->permission_type;
        return implode('.', $parts);
    }

    /**
     * Get all menu permissions grouped by menu and submenu
     */
    public static function getGroupedPermissions(): array
    {
        $permissions = self::orderBy('menu_key')
            ->orderBy('submenu_key')
            ->orderBy('sort_order')
            ->get();

        $grouped = [];
        foreach ($permissions as $permission) {
            $menuKey = $permission->menu_key;
            $submenuKey = $permission->submenu_key ?? 'main';

            if (!isset($grouped[$menuKey])) {
                $grouped[$menuKey] = [];
            }
            if (!isset($grouped[$menuKey][$submenuKey])) {
                $grouped[$menuKey][$submenuKey] = [];
            }

            $grouped[$menuKey][$submenuKey][] = $permission;
        }

        return $grouped;
    }
}
