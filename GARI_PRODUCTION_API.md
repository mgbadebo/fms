# Gari Production System - API Documentation

## Overview

Complete backend API for tracking Gari production from raw cassava to finished product, including inventory, sales, and waste management.

## Database Tables

1. **gari_production_batches** - Main production batch tracking
2. **cassava_inputs** - Cassava tracking (harvested or purchased)
3. **gari_inventory** - Finished goods inventory
4. **packaging_materials** - Packaging inventory
5. **gari_sales** - Sales tracking
6. **gari_waste_losses** - Waste and loss tracking

## API Endpoints

All endpoints require authentication: `Authorization: Bearer {token}`

### Production Batches

**List batches:**
```
GET /api/v1/gari-production-batches
Query params: farm_id, status, date_from, date_to
```

**Create batch:**
```
POST /api/v1/gari-production-batches
Body: {
  farm_id, processing_date, cassava_source, cassava_quantity_kg,
  cassava_cost_per_kg, gari_produced_kg, gari_type, gari_grade,
  labour_cost, fuel_cost, equipment_cost, water_cost, transport_cost,
  other_costs, waste_kg, notes
}
```
*Auto-calculates: yield %, total costs, cost per kg*

**Get batch:**
```
GET /api/v1/gari-production-batches/{id}
```

**Update batch:**
```
PUT /api/v1/gari-production-batches/{id}
```

**Delete batch:**
```
DELETE /api/v1/gari-production-batches/{id}
```

### Cassava Inputs

**List inputs:**
```
GET /api/v1/cassava-inputs
Query params: farm_id, gari_production_batch_id, source_type
```

**Create input:**
```
POST /api/v1/cassava-inputs
Body: {
  farm_id, gari_production_batch_id, source_type (HARVESTED/PURCHASED),
  harvest_lot_id (if harvested), supplier_name (if purchased),
  quantity_kg, cost_per_kg, variety, quality_grade
}
```

**Get/Update/Delete:**
```
GET /api/v1/cassava-inputs/{id}
PUT /api/v1/cassava-inputs/{id}
DELETE /api/v1/cassava-inputs/{id}
```

### Gari Inventory

**List inventory:**
```
GET /api/v1/gari-inventory
Query params: farm_id, gari_type, packaging_type, status
```

**Get inventory summary:**
```
GET /api/v1/gari-inventory/summary
Query params: farm_id
Returns: Summary by type, grade, and packaging
```

**Create inventory:**
```
POST /api/v1/gari-inventory
Body: {
  farm_id, gari_production_batch_id, gari_type, gari_grade,
  packaging_type, quantity_kg, quantity_units, location_id,
  cost_per_kg, status, production_date, expiry_date
}
```

**Get/Update/Delete:**
```
GET /api/v1/gari-inventory/{id}
PUT /api/v1/gari-inventory/{id}
DELETE /api/v1/gari-inventory/{id}
```

### Packaging Materials

**List materials:**
```
GET /api/v1/packaging-materials
Query params: farm_id, material_type
```

**Create material:**
```
POST /api/v1/packaging-materials
Body: {
  farm_id, name, material_type, size, unit,
  opening_balance, quantity_purchased, quantity_used,
  cost_per_unit, location_id
}
```
*Auto-calculates closing balance*

**Get/Update/Delete:**
```
GET /api/v1/packaging-materials/{id}
PUT /api/v1/packaging-materials/{id}
DELETE /api/v1/packaging-materials/{id}
```

### Gari Sales

**List sales:**
```
GET /api/v1/gari-sales
Query params: farm_id, customer_type, payment_status, date_from, date_to
```

**Get sales summary:**
```
GET /api/v1/gari-sales/summary
Query params: farm_id, date_from, date_to
Returns: Summary by customer type and packaging
```

**Create sale:**
```
POST /api/v1/gari-sales
Body: {
  farm_id, sale_date, customer_id, customer_name, customer_type,
  gari_type, gari_grade, packaging_type, quantity_kg, quantity_units,
  unit_price, discount, cost_per_kg, payment_method, amount_paid,
  sales_channel, sales_person
}
```
*Auto-calculates: margins, payment status*

**Get/Update/Delete:**
```
GET /api/v1/gari-sales/{id}
PUT /api/v1/gari-sales/{id}
DELETE /api/v1/gari-sales/{id}
```

### Waste & Losses

**List losses:**
```
GET /api/v1/gari-waste-losses
Query params: farm_id, loss_type, date_from, date_to
```

**Create loss:**
```
POST /api/v1/gari-waste-losses
Body: {
  farm_id, gari_production_batch_id, gari_inventory_id,
  loss_date, loss_type, gari_type, packaging_type,
  quantity_kg, quantity_units, cost_per_kg, description
}
```

**Get/Update/Delete:**
```
GET /api/v1/gari-waste-losses/{id}
PUT /api/v1/gari-waste-losses/{id}
DELETE /api/v1/gari-waste-losses/{id}
```

## Key Features

### Auto-Calculations

1. **Production Batches:**
   - Conversion yield % = (gari_produced / cassava_quantity) × 100
   - Total processing cost = sum of all cost components
   - Total cost = cassava cost + processing cost
   - Cost per kg gari = total_cost / gari_produced
   - Waste % = (waste_kg / cassava_quantity) × 100

2. **Sales:**
   - Total amount = quantity × unit_price
   - Final amount = total_amount - discount
   - Gross margin = final_amount - total_cost
   - Gross margin % = (margin / final_amount) × 100
   - Payment status (PAID/PARTIAL/OUTSTANDING)

3. **Packaging Materials:**
   - Closing balance = opening + purchased - used

## Enums

### Gari Types
- `WHITE`
- `YELLOW`

### Gari Grades
- `FINE`
- `COARSE`
- `MIXED`

### Packaging Types
- `1KG_POUCH`
- `2KG_POUCH`
- `5KG_PACK`
- `50KG_BAG`
- `BULK`

### Customer Types
- `RETAIL`
- `BULK_BUYER`
- `DISTRIBUTOR`
- `CATERING`
- `HOTEL`
- `OTHER`

### Payment Methods
- `CASH`
- `TRANSFER`
- `POS`
- `CHEQUE`
- `CREDIT`

### Loss Types
- `SPOILAGE`
- `MOISTURE_DAMAGE`
- `SPILLAGE`
- `REJECTED_BATCH`
- `CUSTOMER_RETURN`
- `THEFT`
- `OTHER`

## Next Steps

Frontend pages needed:
1. Production Batch Management
2. Cassava Input Tracking
3. Inventory Management
4. Sales Management
5. Packaging Materials
6. Waste/Loss Tracking
7. KPI Dashboard

