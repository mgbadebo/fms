# Grant Greenhouse Management Permissions

This script grants Greenhouse Management permissions to all users with the ADMIN role.

## Usage

On the server, after pulling the latest changes:

```bash
php grant-greenhouse-permissions.php
```

## What it does

1. Ensures the following permissions exist:
   - `admin.greenhouses.view`
   - `admin.greenhouses.create`
   - `admin.greenhouses.update`

2. Grants all permissions to the ADMIN role

3. Grants all permissions directly to all users with ADMIN role

## Requirements

- Laravel application must be set up
- Database must be accessible
- Spatie Permission package must be installed

## Notes

- This script is safe to run multiple times
- It will not duplicate permissions
- It ensures ADMIN role and ADMIN users have all permissions, including the new greenhouse management permissions

