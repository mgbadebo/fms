<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuPermission;
use Spatie\Permission\Models\Permission;

class MenuPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menuPermissions = [
            // General Menu
            ['menu_key' => 'general', 'submenu_key' => null, 'permission_type' => 'view', 'name' => 'View Dashboard', 'description' => 'Access to dashboard', 'sort_order' => 1],
            ['menu_key' => 'general', 'submenu_key' => 'harvest-lots', 'permission_type' => 'view', 'name' => 'View Harvest Lots', 'description' => 'View harvest lots', 'sort_order' => 20],
            ['menu_key' => 'general', 'submenu_key' => 'harvest-lots', 'permission_type' => 'create', 'name' => 'Create Harvest Lots', 'description' => 'Create new harvest lots', 'sort_order' => 21],
            ['menu_key' => 'general', 'submenu_key' => 'harvest-lots', 'permission_type' => 'update', 'name' => 'Update Harvest Lots', 'description' => 'Update existing harvest lots', 'sort_order' => 22],
            ['menu_key' => 'general', 'submenu_key' => 'staff-labor', 'permission_type' => 'view', 'name' => 'View Staff & Labor', 'description' => 'View staff and labor information', 'sort_order' => 50],
            ['menu_key' => 'general', 'submenu_key' => 'staff-labor', 'permission_type' => 'create', 'name' => 'Create Staff & Labor', 'description' => 'Create new staff and labor records', 'sort_order' => 51],
            ['menu_key' => 'general', 'submenu_key' => 'staff-labor', 'permission_type' => 'update', 'name' => 'Update Staff & Labor', 'description' => 'Update existing staff and labor records', 'sort_order' => 52],

            // Gari Menu
            ['menu_key' => 'gari', 'submenu_key' => 'production-batches', 'permission_type' => 'view', 'name' => 'View Gari Production Batches', 'description' => 'View gari production batches', 'sort_order' => 10],
            ['menu_key' => 'gari', 'submenu_key' => 'production-batches', 'permission_type' => 'create', 'name' => 'Create Gari Production Batches', 'description' => 'Create new gari production batches', 'sort_order' => 11],
            ['menu_key' => 'gari', 'submenu_key' => 'production-batches', 'permission_type' => 'update', 'name' => 'Update Gari Production Batches', 'description' => 'Update existing gari production batches', 'sort_order' => 12],
            ['menu_key' => 'gari', 'submenu_key' => 'inventory', 'permission_type' => 'view', 'name' => 'View Gari Inventory', 'description' => 'View gari inventory', 'sort_order' => 20],
            ['menu_key' => 'gari', 'submenu_key' => 'inventory', 'permission_type' => 'create', 'name' => 'Create Gari Inventory', 'description' => 'Create new gari inventory records', 'sort_order' => 21],
            ['menu_key' => 'gari', 'submenu_key' => 'inventory', 'permission_type' => 'update', 'name' => 'Update Gari Inventory', 'description' => 'Update existing gari inventory records', 'sort_order' => 22],
            ['menu_key' => 'gari', 'submenu_key' => 'sales', 'permission_type' => 'view', 'name' => 'View Gari Sales', 'description' => 'View gari sales', 'sort_order' => 30],
            ['menu_key' => 'gari', 'submenu_key' => 'sales', 'permission_type' => 'create', 'name' => 'Create Gari Sales', 'description' => 'Create new gari sales', 'sort_order' => 31],
            ['menu_key' => 'gari', 'submenu_key' => 'sales', 'permission_type' => 'update', 'name' => 'Update Gari Sales', 'description' => 'Update existing gari sales', 'sort_order' => 32],
            ['menu_key' => 'gari', 'submenu_key' => 'kpis', 'permission_type' => 'view', 'name' => 'View Gari KPIs', 'description' => 'View gari KPIs', 'sort_order' => 40],
            ['menu_key' => 'gari', 'submenu_key' => 'waste-losses', 'permission_type' => 'view', 'name' => 'View Gari Waste & Losses', 'description' => 'View gari waste and losses', 'sort_order' => 50],
            ['menu_key' => 'gari', 'submenu_key' => 'waste-losses', 'permission_type' => 'create', 'name' => 'Create Gari Waste & Losses', 'description' => 'Create new waste and loss records', 'sort_order' => 51],
            ['menu_key' => 'gari', 'submenu_key' => 'waste-losses', 'permission_type' => 'update', 'name' => 'Update Gari Waste & Losses', 'description' => 'Update existing waste and loss records', 'sort_order' => 52],
            ['menu_key' => 'gari', 'submenu_key' => 'packaging-materials', 'permission_type' => 'view', 'name' => 'View Packaging Materials', 'description' => 'View packaging materials', 'sort_order' => 60],
            ['menu_key' => 'gari', 'submenu_key' => 'packaging-materials', 'permission_type' => 'create', 'name' => 'Create Packaging Materials', 'description' => 'Create new packaging materials', 'sort_order' => 61],
            ['menu_key' => 'gari', 'submenu_key' => 'packaging-materials', 'permission_type' => 'update', 'name' => 'Update Packaging Materials', 'description' => 'Update existing packaging materials', 'sort_order' => 62],

            // Bell Pepper Menu
            // Greenhouse Management moved to Admin Settings
            ['menu_key' => 'admin', 'submenu_key' => 'greenhouses', 'permission_type' => 'view', 'name' => 'View Greenhouse Management', 'description' => 'View greenhouse management', 'sort_order' => 50],
            ['menu_key' => 'admin', 'submenu_key' => 'greenhouses', 'permission_type' => 'create', 'name' => 'Create Greenhouses', 'description' => 'Create new greenhouses', 'sort_order' => 51],
            ['menu_key' => 'admin', 'submenu_key' => 'greenhouses', 'permission_type' => 'update', 'name' => 'Update Greenhouses', 'description' => 'Update existing greenhouses', 'sort_order' => 52],
            // Borehole Management under Admin
            ['menu_key' => 'admin', 'submenu_key' => 'boreholes', 'permission_type' => 'view', 'name' => 'View Borehole Management', 'description' => 'View boreholes', 'sort_order' => 55],
            ['menu_key' => 'admin', 'submenu_key' => 'boreholes', 'permission_type' => 'create', 'name' => 'Create Boreholes', 'description' => 'Create new boreholes', 'sort_order' => 56],
            ['menu_key' => 'admin', 'submenu_key' => 'boreholes', 'permission_type' => 'update', 'name' => 'Update Boreholes', 'description' => 'Update existing boreholes', 'sort_order' => 57],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'production', 'permission_type' => 'view', 'name' => 'View Bell Pepper Production', 'description' => 'View bell pepper production cycles', 'sort_order' => 30],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'production', 'permission_type' => 'create', 'name' => 'Create Bell Pepper Production', 'description' => 'Create new production cycles', 'sort_order' => 31],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'production', 'permission_type' => 'update', 'name' => 'Update Bell Pepper Production', 'description' => 'Update existing production cycles', 'sort_order' => 32],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'harvests', 'permission_type' => 'view', 'name' => 'View Bell Pepper Harvests', 'description' => 'View bell pepper harvests', 'sort_order' => 40],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'harvests', 'permission_type' => 'create', 'name' => 'Create Bell Pepper Harvests', 'description' => 'Create new bell pepper harvests', 'sort_order' => 41],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'harvests', 'permission_type' => 'update', 'name' => 'Update Bell Pepper Harvests', 'description' => 'Update existing bell pepper harvests', 'sort_order' => 42],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'inventory', 'permission_type' => 'view', 'name' => 'View Bell Pepper Inventory', 'description' => 'View bell pepper inventory', 'sort_order' => 50],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'inventory', 'permission_type' => 'create', 'name' => 'Create Bell Pepper Inventory', 'description' => 'Create new inventory records', 'sort_order' => 51],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'inventory', 'permission_type' => 'update', 'name' => 'Update Bell Pepper Inventory', 'description' => 'Update existing inventory records', 'sort_order' => 52],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'sales', 'permission_type' => 'view', 'name' => 'View Bell Pepper Sales', 'description' => 'View bell pepper sales', 'sort_order' => 60],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'sales', 'permission_type' => 'create', 'name' => 'Create Bell Pepper Sales', 'description' => 'Create new bell pepper sales', 'sort_order' => 61],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'sales', 'permission_type' => 'update', 'name' => 'Update Bell Pepper Sales', 'description' => 'Update existing bell pepper sales', 'sort_order' => 62],
            ['menu_key' => 'bell-pepper', 'submenu_key' => 'kpis', 'permission_type' => 'view', 'name' => 'View Bell Pepper KPIs', 'description' => 'View bell pepper KPIs', 'sort_order' => 70],

            // Reports Menu
            ['menu_key' => 'reports', 'submenu_key' => 'dashboard', 'permission_type' => 'view', 'name' => 'View Dashboard', 'description' => 'View consolidated dashboard', 'sort_order' => 5],
            ['menu_key' => 'reports', 'submenu_key' => 'consolidated-sales', 'permission_type' => 'view', 'name' => 'View Consolidated Sales', 'description' => 'View consolidated sales reports', 'sort_order' => 10],
            ['menu_key' => 'reports', 'submenu_key' => 'consolidated-expenses', 'permission_type' => 'view', 'name' => 'View Consolidated Expenses', 'description' => 'View consolidated expenses reports', 'sort_order' => 20],
            ['menu_key' => 'reports', 'submenu_key' => 'staff-allocation', 'permission_type' => 'view', 'name' => 'View Staff Allocation', 'description' => 'View staff allocation reports', 'sort_order' => 30],

            // Admin Settings Menu
            ['menu_key' => 'admin', 'submenu_key' => 'farms', 'permission_type' => 'view', 'name' => 'View Farms', 'description' => 'View farms list', 'sort_order' => 10],
            ['menu_key' => 'admin', 'submenu_key' => 'farms', 'permission_type' => 'create', 'name' => 'Create Farms', 'description' => 'Create new farms', 'sort_order' => 11],
            ['menu_key' => 'admin', 'submenu_key' => 'farms', 'permission_type' => 'update', 'name' => 'Update Farms', 'description' => 'Update existing farms', 'sort_order' => 12],
            ['menu_key' => 'admin', 'submenu_key' => 'scale-devices', 'permission_type' => 'view', 'name' => 'View Scale Devices', 'description' => 'View scale devices', 'sort_order' => 20],
            ['menu_key' => 'admin', 'submenu_key' => 'scale-devices', 'permission_type' => 'create', 'name' => 'Create Scale Devices', 'description' => 'Create new scale devices', 'sort_order' => 21],
            ['menu_key' => 'admin', 'submenu_key' => 'scale-devices', 'permission_type' => 'update', 'name' => 'Update Scale Devices', 'description' => 'Update existing scale devices', 'sort_order' => 22],
            ['menu_key' => 'admin', 'submenu_key' => 'label-templates', 'permission_type' => 'view', 'name' => 'View Label Templates', 'description' => 'View label templates', 'sort_order' => 30],
            ['menu_key' => 'admin', 'submenu_key' => 'label-templates', 'permission_type' => 'create', 'name' => 'Create Label Templates', 'description' => 'Create new label templates', 'sort_order' => 31],
            ['menu_key' => 'admin', 'submenu_key' => 'label-templates', 'permission_type' => 'update', 'name' => 'Update Label Templates', 'description' => 'Update existing label templates', 'sort_order' => 32],
            ['menu_key' => 'admin', 'submenu_key' => 'admin-zones', 'permission_type' => 'view', 'name' => 'View Admin Zones', 'description' => 'View admin zones', 'sort_order' => 50],
            ['menu_key' => 'admin', 'submenu_key' => 'admin-zones', 'permission_type' => 'create', 'name' => 'Create Admin Zones', 'description' => 'Create new admin zones', 'sort_order' => 51],
            ['menu_key' => 'admin', 'submenu_key' => 'admin-zones', 'permission_type' => 'update', 'name' => 'Update Admin Zones', 'description' => 'Update existing admin zones', 'sort_order' => 52],
            ['menu_key' => 'admin', 'submenu_key' => 'roles', 'permission_type' => 'view', 'name' => 'View Roles', 'description' => 'View roles and permissions', 'sort_order' => 60],
            ['menu_key' => 'admin', 'submenu_key' => 'roles', 'permission_type' => 'create', 'name' => 'Create Roles', 'description' => 'Create new roles', 'sort_order' => 61],
            ['menu_key' => 'admin', 'submenu_key' => 'roles', 'permission_type' => 'update', 'name' => 'Update Roles', 'description' => 'Update existing roles', 'sort_order' => 62],
            ['menu_key' => 'admin', 'submenu_key' => 'users', 'permission_type' => 'view', 'name' => 'View Users', 'description' => 'View users', 'sort_order' => 70],
            ['menu_key' => 'admin', 'submenu_key' => 'users', 'permission_type' => 'create', 'name' => 'Create Users', 'description' => 'Create new users', 'sort_order' => 71],
            ['menu_key' => 'admin', 'submenu_key' => 'users', 'permission_type' => 'update', 'name' => 'Update Users', 'description' => 'Update existing users', 'sort_order' => 72],
            ['menu_key' => 'admin', 'submenu_key' => 'sites', 'permission_type' => 'view', 'name' => 'View Sites', 'description' => 'View sites', 'sort_order' => 80],
            ['menu_key' => 'admin', 'submenu_key' => 'sites', 'permission_type' => 'create', 'name' => 'Create Sites', 'description' => 'Create new sites', 'sort_order' => 81],
            ['menu_key' => 'admin', 'submenu_key' => 'sites', 'permission_type' => 'update', 'name' => 'Update Sites', 'description' => 'Update existing sites', 'sort_order' => 82],
            ['menu_key' => 'admin', 'submenu_key' => 'farm-zones', 'permission_type' => 'view', 'name' => 'View Farm Zones', 'description' => 'View farm zones', 'sort_order' => 90],
            ['menu_key' => 'admin', 'submenu_key' => 'farm-zones', 'permission_type' => 'create', 'name' => 'Create Farm Zones', 'description' => 'Create new farm zones', 'sort_order' => 91],
            ['menu_key' => 'admin', 'submenu_key' => 'farm-zones', 'permission_type' => 'update', 'name' => 'Update Farm Zones', 'description' => 'Update existing farm zones', 'sort_order' => 92],
            ['menu_key' => 'admin', 'submenu_key' => 'factories', 'permission_type' => 'view', 'name' => 'View Factories', 'description' => 'View factories', 'sort_order' => 100],
            ['menu_key' => 'admin', 'submenu_key' => 'factories', 'permission_type' => 'create', 'name' => 'Create Factories', 'description' => 'Create new factories', 'sort_order' => 101],
            ['menu_key' => 'admin', 'submenu_key' => 'factories', 'permission_type' => 'update', 'name' => 'Update Factories', 'description' => 'Update existing factories', 'sort_order' => 102],
            ['menu_key' => 'admin', 'submenu_key' => 'staff-assignments', 'permission_type' => 'view', 'name' => 'View Staff Assignments', 'description' => 'View staff assignments', 'sort_order' => 110],
            ['menu_key' => 'admin', 'submenu_key' => 'staff-assignments', 'permission_type' => 'create', 'name' => 'Create Staff Assignments', 'description' => 'Create new staff assignments', 'sort_order' => 111],
            ['menu_key' => 'admin', 'submenu_key' => 'staff-assignments', 'permission_type' => 'update', 'name' => 'Update Staff Assignments', 'description' => 'Update existing staff assignments', 'sort_order' => 112],
            ['menu_key' => 'admin', 'submenu_key' => 'crops', 'permission_type' => 'view', 'name' => 'View Crops', 'description' => 'View crops', 'sort_order' => 120],
            ['menu_key' => 'admin', 'submenu_key' => 'crops', 'permission_type' => 'create', 'name' => 'Create Crops', 'description' => 'Create new crops', 'sort_order' => 121],
            ['menu_key' => 'admin', 'submenu_key' => 'crops', 'permission_type' => 'update', 'name' => 'Update Crops', 'description' => 'Update existing crops', 'sort_order' => 122],
            ['menu_key' => 'admin', 'submenu_key' => 'assets', 'permission_type' => 'view', 'name' => 'View Assets', 'description' => 'View assets', 'sort_order' => 130],
            ['menu_key' => 'admin', 'submenu_key' => 'assets', 'permission_type' => 'create', 'name' => 'Create Assets', 'description' => 'Create new assets', 'sort_order' => 131],
            ['menu_key' => 'admin', 'submenu_key' => 'assets', 'permission_type' => 'update', 'name' => 'Update Assets', 'description' => 'Update existing assets', 'sort_order' => 132],
            ['menu_key' => 'admin', 'submenu_key' => 'asset-categories', 'permission_type' => 'view', 'name' => 'View Asset Categories', 'description' => 'View asset categories', 'sort_order' => 140],
            ['menu_key' => 'admin', 'submenu_key' => 'asset-categories', 'permission_type' => 'create', 'name' => 'Create Asset Categories', 'description' => 'Create new asset categories', 'sort_order' => 141],
            ['menu_key' => 'admin', 'submenu_key' => 'asset-categories', 'permission_type' => 'update', 'name' => 'Update Asset Categories', 'description' => 'Update existing asset categories', 'sort_order' => 142],
            ['menu_key' => 'admin', 'submenu_key' => 'worker-job-roles', 'permission_type' => 'view', 'name' => 'View Worker Job Roles', 'description' => 'View worker job roles', 'sort_order' => 150],
            ['menu_key' => 'admin', 'submenu_key' => 'worker-job-roles', 'permission_type' => 'create', 'name' => 'Create Worker Job Roles', 'description' => 'Create new worker job roles', 'sort_order' => 151],
            ['menu_key' => 'admin', 'submenu_key' => 'worker-job-roles', 'permission_type' => 'update', 'name' => 'Update Worker Job Roles', 'description' => 'Update existing worker job roles', 'sort_order' => 152],
        ];

        foreach ($menuPermissions as $menuPermission) {
            MenuPermission::firstOrCreate(
                [
                    'menu_key' => $menuPermission['menu_key'],
                    'submenu_key' => $menuPermission['submenu_key'],
                    'permission_type' => $menuPermission['permission_type'],
                ],
                $menuPermission
            );

            // Create Spatie permission
            $permissionName = implode('.', array_filter([
                $menuPermission['menu_key'],
                $menuPermission['submenu_key'],
                $menuPermission['permission_type']
            ]));
            
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Assign all permissions to ADMIN role
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'ADMIN')->first();
        if ($adminRole) {
            $allPermissions = Permission::all();
            $adminRole->syncPermissions($allPermissions);
        }
    }
}
