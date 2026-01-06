# Multi-Farm User Management

## Overview

This system implements multi-farm user management where:
- Users are created **once** and can belong to **multiple farms**
- Farm-specific employment attributes are stored per farm membership
- Application access is managed via **permissions** (not roles)
- Worker job roles are farm-scoped operational roles
- Users can have profile photos (upload or camera capture)

## Key Concepts

### Users (Global Identity)
- Created once, can exist without any farm membership
- Global fields: name, email, phone, password, profile_photo_path

### Farm Membership (Per Farm)
- Stored in `farm_user` pivot table
- Employment details: membership_status, employment_category, pay_type, pay_rate, start_date, end_date, notes

### Worker Job Roles (Farm-Scoped)
- Operational roles specific to each farm
- Examples: "Field Supervisor", "Harvest Manager", "Equipment Operator"
- Stored in `worker_job_roles` table

### Permissions (Access Control)
- Application access is permission-based
- Permissions can be assigned during user creation or later
- Uses Spatie Permission package (roles/permissions tables remain intact)

## API Endpoints

### User Management

#### Create User (with photo, farms, permissions, job roles)
```bash
curl -X POST http://your-domain/api/v1/users \
  -H "Authorization: Bearer {token}" \
  -F "name=John Doe" \
  -F "email=john@example.com" \
  -F "phone=+2348012345678" \
  -F "password=securepassword123" \
  -F "photo=@/path/to/photo.jpg" \
  -F 'farms=[{"farm_id":1,"membership_status":"ACTIVE","employment_category":"PERMANENT","pay_type":"MONTHLY","pay_rate":250000,"start_date":"2026-01-01"}]' \
  -F 'permissions=["admin.farms.view","admin.farms.create"]' \
  -F 'job_roles=[{"farm_id":1,"worker_job_role_id":10}]'
```

#### Attach User to Farm (Later)
```bash
curl -X POST http://your-domain/api/v1/users/{user}/farms/attach \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 2,
    "membership_status": "ACTIVE",
    "employment_category": "CASUAL",
    "pay_type": "DAILY",
    "pay_rate": 8000,
    "start_date": "2026-01-15"
  }'
```

#### Update Farm Membership
```bash
curl -X PATCH http://your-domain/api/v1/users/{user}/farms/{farm}/membership \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "membership_status": "INACTIVE",
    "pay_rate": 300000
  }'
```

#### Detach User from Farm
```bash
curl -X POST http://your-domain/api/v1/users/{user}/farms/{farm}/detach \
  -H "Authorization: Bearer {token}"
```

### Worker Job Roles

#### Create Job Role
```bash
curl -X POST http://your-domain/api/v1/worker-job-roles \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "farm_id": 1,
    "code": "FLD_SUP",
    "name": "Field Supervisor",
    "description": "Supervises field operations",
    "is_active": true
  }'
```

#### Assign Job Role to User
```bash
curl -X POST http://your-domain/api/v1/users/{user}/farms/{farm}/job-roles/assign \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "worker_job_role_id": 10,
    "notes": "Assigned as primary supervisor"
  }'
```

#### End Job Role Assignment
```bash
curl -X POST http://your-domain/api/v1/users/{user}/farms/{farm}/job-roles/{assignment}/end \
  -H "Authorization: Bearer {token}"
```

### Permissions

#### Grant Permissions
```bash
curl -X POST http://your-domain/api/v1/users/{user}/permissions/grant \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permissions": ["admin.farms.view", "admin.farms.create", "admin.farms.update"]
  }'
```

#### Revoke Permissions
```bash
curl -X POST http://your-domain/api/v1/users/{user}/permissions/revoke \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "permissions": ["admin.farms.update"]
  }'
```

#### Get User Permissions
```bash
curl -X GET http://your-domain/api/v1/users/{user}/permissions \
  -H "Authorization: Bearer {token}"
```

### Photo Management

#### Upload User Photo
```bash
curl -X POST http://your-domain/api/v1/users/{user}/photo \
  -H "Authorization: Bearer {token}" \
  -F "photo=@/path/to/photo.jpg"
```

#### Delete User Photo
```bash
curl -X DELETE http://your-domain/api/v1/users/{user}/photo \
  -H "Authorization: Bearer {token}"
```

## Database Schema

### users
- id, name, email, phone, password, profile_photo_path, timestamps

### farm_user (pivot)
- id, farm_id, user_id, role, membership_status, employment_category, pay_type, pay_rate, start_date, end_date, notes, timestamps

### worker_job_roles
- id, farm_id, code, name, description, is_active, timestamps

### user_job_role_assignments
- id, farm_id, user_id, worker_job_role_id, assigned_at, ended_at, assigned_by_user_id, notes, timestamps

## Notes

- Admin users (`admin@fms.test` and `admin@owofarms.com.ng`) automatically have all permissions
- Users can belong to multiple farms with different employment details per farm
- Job roles are farm-scoped and cannot be assigned across farms
- Permissions are global (not farm-scoped)
- Photo storage: `storage/app/public/users/{user_id}/profile.{ext}`

