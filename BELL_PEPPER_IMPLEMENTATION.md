# Bell Pepper Greenhouse Management System - Implementation Summary

## Overview
This document summarizes the implementation of the Bell Pepper greenhouse management system, including database structure, API endpoints, and frontend pages.

## Database Structure

### 1. Greenhouses Table (`greenhouses`)
Tracks individual greenhouse structures:
- **Fields**: code, name, size_sqm, built_date, construction_cost, amortization_cycles, borehole_cost, borehole_amortization_cycles, location, notes, is_active
- **Key Features**:
  - Tracks construction cost and amortization over cycles (default 6 cycles)
  - Tracks borehole cost separately with its own amortization
  - Methods: `getAmortizedCostPerCycle()`, `getBoreholeAmortizedCostPerCycle()`

### 2. Bell Pepper Cycles Table (`bell_pepper_cycles`)
Tracks production cycles (2 per year, 6 months each):
- **Fields**: cycle_code, start_date, expected_end_date, actual_end_date, status, expected_yield_kg, expected_yield_per_sqm, actual_yield_kg, actual_yield_per_sqm, yield_variance_percent
- **Key Features**:
  - Tracks expected vs actual yield
  - Calculates yield per sqm automatically
  - Calculates yield variance percentage
  - Methods: `getTotalCosts()`, `calculateYieldVariance()`, `calculateActualYieldPerSqm()`

### 3. Bell Pepper Cycle Costs Table (`bell_pepper_cycle_costs`)
Tracks all costs per cycle:
- **Cost Types**:
  - **Per Cycle**: SEEDS, FERTILIZER_CHEMICALS, FUEL_WATER_PUMPING, LABOUR_DEDICATED, LABOUR_SHARED
  - **Multi-Cycle (Amortized)**: SPRAY_GUNS, IRRIGATION_EQUIPMENT, PROTECTIVE_CLOTHING, GREENHOUSE_AMORTIZATION, BOREHOLE_AMORTIZATION
  - **Other**: LOGISTICS, OTHER
- **Fields**: cost_type, description, quantity, unit, unit_cost, total_cost, cost_date, staff_id, hours_allocated
- **Key Features**:
  - Supports dedicated staff (100% allocation) and shared staff (hours allocated)
  - Auto-calculates greenhouse and borehole amortization
  - Tracks quantity and unit for materials

### 4. Bell Pepper Harvests Table (`bell_pepper_harvests`)
Tracks individual harvests:
- **Fields**: harvest_code, harvest_date, weight_kg, crates_count, grade (A/B/C/MIXED), status
- **Key Features**:
  - Auto-calculates crates count (9-10kg per crate, uses 9.5kg average)
  - Tracks harvest status (HARVESTED, PACKED, IN_TRANSIT, DELIVERED, SOLD)
  - Method: `getRemainingWeight()` - calculates unsold weight

### 5. Bell Pepper Sales Table (`bell_pepper_sales`)
Tracks sales transactions:
- **Fields**: sale_code, sale_date, quantity_kg, crates_count, grade, unit_price, total_amount, discount, final_amount, logistics_cost, payment_method, payment_status, amount_paid, amount_outstanding
- **Key Features**:
  - Links to harvest for traceability
  - Tracks logistics/transport costs separately
  - Auto-calculates crates count
  - Payment tracking with status calculation
  - Method: `calculatePayment()`

## API Endpoints

All endpoints are under `/api/v1/` and require authentication:

### Greenhouses
- `GET /api/v1/greenhouses` - List all greenhouses (supports farm_id, is_active filters)
- `POST /api/v1/greenhouses` - Create new greenhouse
- `GET /api/v1/greenhouses/{id}` - Get greenhouse details
- `PUT /api/v1/greenhouses/{id}` - Update greenhouse
- `DELETE /api/v1/greenhouses/{id}` - Delete greenhouse

### Bell Pepper Cycles
- `GET /api/v1/bell-pepper-cycles` - List cycles (supports farm_id, greenhouse_id, status filters)
- `POST /api/v1/bell-pepper-cycles` - Create new cycle
- `GET /api/v1/bell-pepper-cycles/{id}` - Get cycle details with costs and harvests
- `PUT /api/v1/bell-pepper-cycles/{id}` - Update cycle (auto-recalculates yield metrics)
- `DELETE /api/v1/bell-pepper-cycles/{id}` - Delete cycle

### Cycle Costs
- `GET /api/v1/bell-pepper-cycle-costs` - List costs (supports cycle_id, farm_id, cost_type filters)
- `POST /api/v1/bell-pepper-cycle-costs` - Add cost (auto-calculates amortization for greenhouse/borehole)
- `GET /api/v1/bell-pepper-cycle-costs/{id}` - Get cost details
- `PUT /api/v1/bell-pepper-cycle-costs/{id}` - Update cost
- `DELETE /api/v1/bell-pepper-cycle-costs/{id}` - Delete cost

### Harvests
- `GET /api/v1/bell-pepper-harvests` - List harvests (supports farm_id, cycle_id, greenhouse_id filters)
- `POST /api/v1/bell-pepper-harvests` - Record harvest (auto-updates cycle yield)
- `GET /api/v1/bell-pepper-harvests/{id}` - Get harvest details
- `PUT /api/v1/bell-pepper-harvests/{id}` - Update harvest (recalculates cycle yield if weight changed)
- `DELETE /api/v1/bell-pepper-harvests/{id}` - Delete harvest (recalculates cycle yield)

### Sales
- `GET /api/v1/bell-pepper-sales` - List sales (supports farm_id, customer_type, payment_status, date filters)
- `POST /api/v1/bell-pepper-sales` - Create sale
- `GET /api/v1/bell-pepper-sales/{id}` - Get sale details
- `PUT /api/v1/bell-pepper-sales/{id}` - Update sale (supports payment status updates)
- `DELETE /api/v1/bell-pepper-sales/{id}` - Delete sale

## Frontend Pages

### 1. Greenhouses (`/greenhouses`)
- **Purpose**: Settings page for managing greenhouse configurations
- **Features**:
  - List all greenhouses with key details
  - Create/edit greenhouses
  - Set construction cost and amortization cycles
  - Set borehole cost and amortization
  - Track size in sqm
  - Mark as active/inactive

### 2. Bell Pepper Production (`/bell-pepper-production`)
- **Purpose**: Manage production cycles
- **Features**:
  - List all cycles with yield metrics
  - Create new cycles (auto-sets 6-month end date)
  - View expected vs actual yield
  - View yield per sqm
  - View yield variance
  - Link to cycle detail page

### 3. Bell Pepper Cycle Detail (`/bell-pepper-cycles/:id`)
- **Purpose**: Detailed view of a production cycle
- **Features**:
  - View cycle summary with yield metrics
  - **Cost Tracking**:
    - Add/edit/delete costs
    - Support all cost types (seeds, fertilizer, fuel, labour, amortization, etc.)
    - Track dedicated vs shared labour
    - Auto-calculate amortization costs
  - **Harvest Tracking**:
    - Record harvests
    - Auto-calculate crates (9-10kg per crate)
    - Track grade and status
    - Auto-update cycle yield metrics

### 4. Bell Pepper Inventory (Placeholder)
- **Status**: Coming soon
- **Planned Features**: Track available inventory from harvests

### 5. Bell Pepper Sales (Placeholder)
- **Status**: Coming soon
- **Planned Features**: Sales management with logistics cost tracking

### 6. Bell Pepper KPIs (Placeholder)
- **Status**: Coming soon
- **Planned Features**: Performance metrics and yield analysis

## Key Features Implemented

### ✅ Yield Tracking
- Expected yield per cycle (kg and kg/sqm)
- Actual yield tracking from harvests
- Automatic yield variance calculation
- Yield per sqm calculation

### ✅ Cost Tracking
- **Per Cycle Costs**: Seeds, fertilizer/chemicals, fuel, labour
- **Amortized Costs**: Equipment, greenhouse structure, borehole
- **Labour Allocation**: Dedicated (100%) and shared (hours-based)
- Automatic amortization calculation

### ✅ Harvest Management
- Record harvests with weight and grade
- Auto-calculate crates (9-10kg per crate)
- Track harvest status
- Link to sales

### ✅ Greenhouse Settings
- Define greenhouse parameters
- Set construction and borehole costs
- Configure amortization periods
- Track size in sqm

## Next Steps

1. **Complete Frontend Pages**:
   - Bell Pepper Inventory page
   - Bell Pepper Sales page
   - Bell Pepper KPIs dashboard

2. **Additional Features**:
   - Cycle editing functionality
   - Cost category summaries
   - Yield trend analysis
   - Cost per kg calculations
   - Profitability analysis

3. **Integration**:
   - Link to staff/labor management
   - Integration with consolidated dashboard
   - Export/reporting functionality

## Database Migration

Run the following to create all tables:

```bash
php artisan migrate
```

This will create:
- `greenhouses`
- `bell_pepper_cycles`
- `bell_pepper_cycle_costs`
- `bell_pepper_harvests`
- `bell_pepper_sales`

## Usage Flow

1. **Setup**: Create greenhouses in Settings (`/greenhouses`)
2. **Planning**: Create production cycles (`/bell-pepper-production`)
3. **Production**: Track costs during cycle (`/bell-pepper-cycles/:id`)
4. **Harvest**: Record harvests as they occur
5. **Sales**: Record sales (when sales page is implemented)
6. **Analysis**: View KPIs and yield performance

