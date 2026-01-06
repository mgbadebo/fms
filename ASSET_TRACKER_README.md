# Asset Tracker Module

Complete asset tracking and management system integrated into the Farm Management System.

## Features

- **Asset Categories**: Hierarchical categorization of assets
- **Asset Management**: Full CRUD with tracking codes, status, location, and metadata
- **Asset Assignments**: Track who has which asset (polymorphic: User, Worker, etc.)
- **Maintenance Plans**: Scheduled maintenance with automatic due date calculation
- **Maintenance Records**: Track service, repair, and inspection history
- **Fuel Logs**: Track fuel consumption for vehicles and equipment
- **Insurance Policies**: Manage asset insurance coverage
- **Depreciation**: Straight-line and reducing balance depreciation schedules
- **Attachments**: File attachments for documents, photos, etc.

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `asset_categories`
- `assets` (enhanced from existing)
- `asset_assignments`
- `maintenance_plans`
- `maintenance_records` (enhanced from existing)
- `fuel_logs` (enhanced from existing)
- `asset_insurance_policies`
- `asset_depreciation_profiles`
- `asset_attachments`

### 2. Seed Asset Categories (Optional)

```bash
php artisan db:seed --class=AssetCategorySeeder
```

This creates default categories for all existing farms:
- LAND, BUILDING, TRACTOR, FIELD_MACHINERY, TRANSPORT_VEHICLE
- IRRIGATION_EQUIPMENT, LIVESTOCK, POST_HARVEST_EQUIPMENT
- STORAGE_EQUIPMENT, TOOL, IT_EQUIPMENT, IOT_DEVICE
- ENERGY_SYSTEM, SAFETY_EQUIPMENT, OTHER

## API Endpoints

All endpoints require authentication via Sanctum (`auth:sanctum` middleware).

### Asset Categories

- `GET /api/v1/asset-categories` - List categories (filterable by `farm_id`, `is_active`)
- `POST /api/v1/asset-categories` - Create category
- `GET /api/v1/asset-categories/{id}` - Get category
- `PUT /api/v1/asset-categories/{id}` - Update category
- `DELETE /api/v1/asset-categories/{id}` - Delete category

### Assets

- `GET /api/v1/assets` - List assets (filterable by `farm_id`, `asset_category_id`, `status`, `location_field_id`, `search`)
- `POST /api/v1/assets` - Create asset (auto-generates `asset_code` if not provided)
- `GET /api/v1/assets/{id}` - Get asset with all relationships
- `PUT /api/v1/assets/{id}` - Update asset
- `DELETE /api/v1/assets/{id}` - Soft delete asset (sets status to DISPOSED)

### Asset Assignments

- `GET /api/v1/assets/{asset}/assignments` - Get assignment history
- `POST /api/v1/assets/{asset}/assign` - Assign asset to user/worker
- `POST /api/v1/assets/{asset}/return` - Return assigned asset
- `GET /api/v1/asset-assignments` - List all assignments (filterable by `farm_id`, `asset_id`, `active`)

### Maintenance

- `GET /api/v1/assets/{asset}/maintenance-plans` - List maintenance plans
- `POST /api/v1/assets/{asset}/maintenance-plans` - Create maintenance plan
- `PATCH /api/v1/assets/{asset}/maintenance-plans/{plan}` - Update maintenance plan
- `GET /api/v1/assets/{asset}/maintenance-records` - List maintenance records
- `POST /api/v1/assets/{asset}/maintenance-records` - Create maintenance record (auto-updates plans)

### Fuel Logs

- `GET /api/v1/assets/{asset}/fuel-logs` - List fuel logs
- `POST /api/v1/assets/{asset}/fuel-logs` - Create fuel log

### Insurance

- `GET /api/v1/assets/{asset}/insurance-policies` - List insurance policies
- `POST /api/v1/assets/{asset}/insurance-policies` - Create insurance policy

### Depreciation

- `GET /api/v1/assets/{asset}/depreciation-profile` - Get depreciation profile
- `POST /api/v1/assets/{asset}/depreciation-profile` - Create depreciation profile
- `GET /api/v1/assets/{asset}/depreciation-schedule?to=YYYY-MM-DD` - Get depreciation schedule

### Attachments

- `GET /api/v1/assets/{asset}/attachments` - List attachments
- `POST /api/v1/assets/{asset}/attachments` - Upload attachment (multipart/form-data)
- `DELETE /api/v1/assets/{asset}/attachments/{attachment}` - Delete attachment

## Example API Calls

### Create Asset Category

```bash
curl -X POST http://localhost:8000/api/v1/asset-categories \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 1,
    "code": "TR",
    "name": "Tractor",
    "is_active": true
  }'
```

### Create Asset

```bash
curl -X POST http://localhost:8000/api/v1/assets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 1,
    "asset_category_id": 1,
    "name": "John Deere 5055E",
    "status": "ACTIVE",
    "acquisition_type": "PURCHASED",
    "purchase_date": "2024-01-15",
    "purchase_cost": 2500000,
    "currency": "NGN",
    "serial_number": "JD5055E-2024-001"
  }'
```

### Assign Asset

```bash
curl -X POST http://localhost:8000/api/v1/assets/1/assign \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assigned_to_type": "App\\Models\\Worker",
    "assigned_to_id": 5,
    "notes": "Assigned for field work"
  }'
```

### Create Maintenance Record

```bash
curl -X POST http://localhost:8000/api/v1/assets/1/maintenance-records \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "performed_at": "2024-12-01 10:00:00",
    "type": "SERVICE",
    "vendor_name": "John Deere Service Center",
    "cost": 50000,
    "currency": "NGN",
    "description": "Regular service - oil change, filter replacement"
  }'
```

### Get Depreciation Schedule

```bash
curl -X GET "http://localhost:8000/api/v1/assets/1/depreciation-schedule?to=2025-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Business Rules

1. **Single Active Assignment**: Only one asset can be assigned to a person at a time. Must return before reassigning.

2. **Maintenance Plan Updates**: When a maintenance record is created, all active maintenance plans for that asset are updated:
   - `last_service_at` = record's `performed_at`
   - `next_due_at` = calculated based on plan type and interval

3. **Asset Code Generation**: Auto-generated if not provided:
   - Uses category code prefix (first 2-3 chars)
   - Format: `PREFIX-00001`, `PREFIX-00002`, etc.
   - Unique per farm

4. **Depreciation**: 
   - One profile per asset
   - Straight-line: (cost - salvage) / useful_life_months
   - Reducing balance: Monthly rate calculated from useful life

5. **Status Management**: 
   - Prefer status updates over hard deletes
   - DELETE sets status to DISPOSED and soft deletes

## Permissions

- **View**: Any authenticated user (scoped by farm)
- **Create/Update**: MANAGER or ADMIN role
- **Delete/Dispose**: ADMIN role only
- **Assign**: MANAGER or ADMIN
- **Maintain**: MANAGER, ADMIN, or WORKER

## Testing

Run the test suite:

```bash
php artisan test --filter AssetTracker
```

Tests cover:
- Farm scoping (users cannot access other farms' assets)
- Single active assignment enforcement
- Maintenance plan updates
- Depreciation schedule calculations
- File upload handling

## Notes

- All records are scoped by `farm_id` for multi-tenant isolation
- Uses existing authentication (Sanctum) and authorization (Spatie Permission)
- Follows project conventions: no FormRequests, validation in controllers
- File uploads use Laravel Storage (public disk by default)

