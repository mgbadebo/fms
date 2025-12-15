# Farm Management System (FMS) - Backend API

A comprehensive Laravel-based backend API for managing farm operations, including crop management, livestock tracking, task management, inventory, IoT sensor integration, scale integration, and label printing.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **MySQL/PostgreSQL** (SQL portable)
- **Laravel Sanctum** for API authentication
- **Spatie Permission** for RBAC
- **PHP 8.2+**

## Features

### Core Modules

1. **Farm Mapping & GIS** - Fields, zones, geometry references
2. **Crop Management** - Crop plans, scouting logs, harvest lots
3. **Livestock Management** - Groups, animals, breeding, health records, feed tracking
4. **Task & Labour Management** - Tasks, assignments, time logging
5. **Input & Application Tracking** - Inventory, stock movements, input applications
6. **IoT & Analytics** - Sensor data, alerts
7. **Traceability** - Lot-level tracking with QR/barcode support
8. **Inventory Management** - Locations, stock movements
9. **Financial Management** - Transactions, budgets
10. **Equipment & Asset Management** - Assets, maintenance, fuel logs
11. **Harvest / Storage / Supply Chain** - Storage units, sales orders
12. **Scale Integration** - Digital scale integration (mock implementation)
13. **Label / Barcode Printer Integration** - Label templates and printing (mock implementation)
14. **Administration** - Users, roles, permissions, master data

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd FMS
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=fms
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Publish Spatie Permission migrations** (if not already published)
   ```bash
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   php artisan migrate
   ```

7. **Seed database** (optional)
   ```bash
   php artisan db:seed
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

## API Structure

All API endpoints are prefixed with `/api/v1` and require authentication via Laravel Sanctum.

### Authentication

Obtain a token by logging in:
```bash
POST /api/v1/login
{
  "email": "user@example.com",
  "password": "password"
}
```

Use the token in subsequent requests:
```bash
Authorization: Bearer {token}
```

## Example API Calls

### 1. Create a Farm

```bash
curl -X POST http://localhost:8000/api/v1/farms \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Green Valley Farm",
    "location": "123 Farm Road",
    "description": "Organic vegetable farm",
    "total_area": 50.5,
    "area_unit": "hectares",
    "is_active": true
  }'
```

### 2. Create a Harvest Lot

```bash
curl -X POST http://localhost:8000/api/v1/harvest-lots \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 1,
    "field_id": 1,
    "season_id": 1,
    "harvested_at": "2024-12-11 10:00:00",
    "quality_grade": "A"
  }'
```

### 3. Get Weight from Scale (Mock)

```bash
curl -X POST http://localhost:8000/api/v1/scale-readings \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "scale_device_id": 1,
    "context_type": "App\\Models\\HarvestLot",
    "context_id": 1,
    "unit": "kg"
  }'
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "farm_id": 1,
    "scale_device_id": 1,
    "context_type": "App\\Models\\HarvestLot",
    "context_id": 1,
    "gross_weight": "25.50",
    "tare_weight": "1.20",
    "net_weight": "24.30",
    "unit": "kg",
    "weighed_at": "2024-12-11T10:30:00.000000Z"
  }
}
```

### 4. Print a Label (Mock)

```bash
curl -X POST http://localhost:8000/api/v1/labels/print \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "label_template_id": 1,
    "target_type": "App\\Models\\HarvestLot",
    "target_id": 1,
    "printer_name": "Zebra Printer"
  }'
```

**Response:**
```json
{
  "data": {
    "printed_label": {
      "id": 1,
      "farm_id": 1,
      "label_template_id": 1,
      "target_type": "App\\Models\\HarvestLot",
      "target_id": 1,
      "printed_at": "2024-12-11T10:35:00.000000Z",
      "printer_name": "Zebra Printer"
    },
    "rendered_content": "Harvest Lot: HL-20241211-ABC123\nWeight: 24.30 kg\nTraceability ID: HL-XYZ789ABC123",
    "success": true
  }
}
```

### 5. Create a Scale Device

```bash
curl -X POST http://localhost:8000/api/v1/scale-devices \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 1,
    "name": "Main Weighing Scale",
    "connection_type": "MOCK",
    "connection_config": {
      "unit": "kg"
    },
    "is_active": true
  }'
```

### 6. Create a Label Template

```bash
curl -X POST http://localhost:8000/api/v1/label-templates \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 1,
    "name": "Harvest Lot Label",
    "code": "HARVEST_LOT_LABEL",
    "target_type": "HARVEST_LOT",
    "template_engine": "BLADE",
    "template_body": "Harvest Lot: {{code}}\nWeight: {{net_weight}} {{weight_unit}}\nTraceability ID: {{traceability_id}}\nField: {{field_name}}\nHarvested: {{harvested_at}}",
    "is_default": true
  }'
```

## Service Architecture

### Scale Integration

The system uses a service interface pattern for scale integration:

- **Interface**: `App\Services\Scale\ScaleServiceInterface`
- **Mock Implementation**: `App\Services\Scale\MockScaleService`
- **Future Implementations**: Can add `SerialScaleService`, `TcpScaleService`, etc.

To swap implementations, update the binding in `AppServiceProvider`.

### Label Printing

Similar service pattern for label printing:

- **Interface**: `App\Services\Label\LabelPrinterInterface`
- **Mock Implementation**: `App\Services\Label\MockLabelPrinterService`
- **Template Engines**: Supports ZPL, Blade, and RAW templates

## Database Structure

The system includes migrations for:

- Multi-tenant farms and user-farm relationships
- Farm mapping (fields, zones)
- Crop management (crops, crop plans, scouting logs, harvest lots)
- Livestock management (breeds, groups, animals, breeding, health, feed)
- Task & labour (workers, tasks, assignments, logs)
- Input & inventory (items, locations, stock movements, applications)
- IoT & sensors (sensors, readings, alert rules, events)
- Financial (transactions, budgets, budget lines)
- Equipment (assets, maintenance, fuel logs)
- Storage & supply chain (storage units, contents, customers, sales orders)
- Scale integration (scale devices, weighing records)
- Label printing (label templates, printed labels)

## Testing

Run tests:
```bash
php artisan test
```

Key test scenarios:
- Creating Farm → Field → CropPlan → HarvestLot flow
- Scale reading endpoint integration
- Label printing endpoint integration

## Roles & Permissions

The system uses Spatie Permission for RBAC. Default roles:

- **OWNER** - Full farm access
- **MANAGER** - Management operations
- **WORKER** - Field operations
- **FINANCE** - Financial operations
- **AUDITOR** - Read-only access
- **ADMIN** - System administration

## Development Notes

### Adding Real Scale Integration

1. Create a new service class implementing `ScaleServiceInterface`
2. Update `AppServiceProvider` to bind the new service
3. Configure connection settings in `scale_devices.connection_config`

### Adding Real Label Printer Integration

1. Create a new service class implementing `LabelPrinterInterface`
2. Update `AppServiceProvider` to bind the new service
3. Implement actual printer communication (ZPL/EPL commands)

## License

[Your License Here]

## Support

For issues and questions, please contact [Your Contact Information]
