# Bell Pepper Production Workflow Implementation

## Overview
This document describes the complete implementation of the structured Greenhouse Production Workflow for Bell Peppers, replacing the previous simple cycle tracking with a comprehensive system.

## Workflow: PLANT → DAILY ACTIVITY LOGS → HARVEST RECORDS

### 1. Production Cycle Creation
- **Endpoint**: `POST /api/v1/production-cycles`
- **Required Fields**: All fields from Sections 1-4 must be provided
- **Derivation**: `farm_id` and `site_id` are automatically derived from `greenhouse_id` (server-side)
- **Constraint**: Only one ACTIVE or HARVESTING cycle per greenhouse at a time

### 2. Daily Activity Logs
- **Structured Logging**: Users select activity types and fill structured fields per type
- **Submission Deadline**: Farm-defined cutoff time (default: 18:00)
- **Status**: DRAFT (editable) → SUBMITTED (locked)
- **Alerts**: Automatic alerts when logs are not submitted by cutoff time

### 3. Harvest Records
- **Integration**: Links to existing harvest record system
- **Endpoint**: `GET /api/v1/production-cycles/{id}/harvest-records`

## Database Schema

### Tables Created
1. **greenhouse_production_cycles** - Production cycle master data
2. **activity_types** - Farm-scoped selectable activity types
3. **production_cycle_daily_logs** - Daily log headers
4. **production_cycle_daily_log_items** - Structured activity entries
5. **production_cycle_daily_log_item_inputs** - Inputs used in activities
6. **production_cycle_daily_log_item_photos** - Photos attached to activities
7. **production_cycle_alerts** - Missing log alerts

### Fields Added
- **farms.daily_log_cutoff_time** - Farm-level cutoff time for daily log submission

## Models Created

1. `GreenhouseProductionCycle` - Production cycle model with boot method for auto-derivation
2. `ActivityType` - Activity type model (farm-scoped)
3. `ProductionCycleDailyLog` - Daily log header model
4. `ProductionCycleDailyLogItem` - Daily log item model
5. `ProductionCycleDailyLogItemInput` - Input usage model
6. `ProductionCycleDailyLogItemPhoto` - Photo attachment model
7. `ProductionCycleAlert` - Alert model

## Controllers

1. **ProductionCycleController** (`/api/v1/production-cycles`)
   - `index()` - List cycles with filtering
   - `store()` - Create cycle (requires all Sections 1-4 fields)
   - `show()` - Get cycle details
   - `update()` - Update cycle
   - `destroy()` - Delete cycle
   - `start()` - Start cycle (sets status to ACTIVE)
   - `complete()` - Complete cycle (sets status to COMPLETED)

2. **ActivityTypeController** (`/api/v1/activity-types`)
   - Full CRUD for farm-scoped activity types
   - Admin-only management

3. **DailyLogController** (`/api/v1/production-cycles/{id}/daily-logs`)
   - `index()` - List logs for a cycle
   - `store()` - Create/update draft log
   - `show()` - Get log details
   - `update()` - Update draft log
   - `submit()` - Submit log (enforces cutoff time)

## Form Requests & Validation

### StoreProductionCycleRequest
- Validates all required fields from Sections 1-4
- Rejects `farm_id` and `site_id` (auto-derived)
- Verifies user belongs to farm

### StoreDailyLogRequest / UpdateDailyLogRequest
- Type-specific validation based on `activity_type.code`:
  - **IRRIGATION**: Requires quantity+unit OR time range
  - **FERTIGATION**: Requires quantity+unit AND inputs
  - **SPRAYING**: Requires time range, inputs, and notes (target pest/disease)
  - **SCOUTING**: Requires severity when pests/disease observed
  - **CLEANING_SANITATION**: Requires notes or checklist
  - **OTHER**: Requires notes
- Farm scoping: Activity types must belong to same farm as cycle

## Policies

1. **GreenhouseProductionCyclePolicy** - Farm-scoped access control
2. **ActivityTypePolicy** - Farm-scoped, admin-only management
3. **ProductionCycleDailyLogPolicy** - Farm-scoped, DRAFT-only updates

## Resources (API Responses)

1. **ProductionCycleResource** - Full cycle data with relationships
2. **ActivityTypeResource** - Activity type data
3. **DailyLogResource** - Log with items, inputs, and photos
4. **ProductionCycleAlertResource** - Alert data

## Scheduled Command

**Command**: `production-cycles:check-missing-daily-logs`
- **Schedule**: Every 15 minutes between 18:00 and 20:00
- **Logic**:
  - For each farm (timezone-aware):
    - Get "today" in farm timezone
    - Find cycles with status ACTIVE or HARVESTING
    - Check if SUBMITTED log exists for today
    - Create alert if missing (with de-duplication)

## Seeders

1. **ActivityTypeSeeder** - Seeds common activity types for all farms:
   - IRRIGATION, FERTIGATION, PRUNING, TRELLISING, DELEAFING
   - SCOUTING, SPRAYING, POLLINATION_SUPPORT
   - CLEANING_SANITATION, EQUIPMENT_CHECK, OTHER

## Tests

1. **ProductionCycleTest**:
   - Required fields validation
   - Only one ACTIVE/HARVESTING cycle per greenhouse
   - Farm/site derivation

2. **DailyLogTest**:
   - Draft log creation
   - Type-specific validation (IRRIGATION, SPRAYING, SCOUTING)
   - Cutoff time enforcement
   - Farm scoping

## API Routes

```php
// Production Cycles
Route::apiResource('production-cycles', ProductionCycleController::class);
Route::post('production-cycles/{id}/start', [ProductionCycleController::class, 'start']);
Route::post('production-cycles/{id}/complete', [ProductionCycleController::class, 'complete']);

// Activity Types
Route::apiResource('activity-types', ActivityTypeController::class);

// Daily Logs
Route::get('production-cycles/{production_cycle_id}/daily-logs', [DailyLogController::class, 'index']);
Route::post('production-cycles/{production_cycle_id}/daily-logs', [DailyLogController::class, 'store']);
Route::get('daily-logs/{id}', [DailyLogController::class, 'show']);
Route::patch('daily-logs/{id}', [DailyLogController::class, 'update']);
Route::post('daily-logs/{id}/submit', [DailyLogController::class, 'submit']);
```

## Permissions Added

- `production_cycles.view`
- `production_cycles.create`
- `production_cycles.update`
- `production_cycles.delete`
- `daily_logs.view`
- `daily_logs.create`
- `daily_logs.update`
- `daily_logs.submit`
- `daily_logs.override_cutoff` (for late submissions)
- `activity_types.view`
- `activity_types.manage` (admin only)

## Next Steps (Frontend Implementation)

1. **Production Cycle Form** - Create comprehensive form with all Sections 1-4 fields
2. **Daily Log Form** - Dynamic form based on selected activity types
3. **Activity Type Selector** - Dropdown with type-specific field rendering
4. **Cutoff Time Display** - Show remaining time before cutoff
5. **Alert Dashboard** - Display missing log alerts
6. **Harvest Record Integration** - Link harvests to production cycles

## Migration & Seeding

```bash
# Run migrations
php artisan migrate

# Seed activity types
php artisan db:seed --class=ActivityTypeSeeder

# Or seed everything
php artisan db:seed
```

## Scheduler Setup

Ensure cron job is set up:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Notes

- All farm_id and site_id fields are derived server-side from greenhouse_id
- Activity types are farm-scoped (each farm has its own set)
- Daily logs can only be created for ACTIVE or HARVESTING cycles
- Only DRAFT logs can be updated
- Cutoff time is farm-specific and timezone-aware
- Alerts are de-duplicated using unique constraint on (production_cycle_id, log_date, alert_type)
