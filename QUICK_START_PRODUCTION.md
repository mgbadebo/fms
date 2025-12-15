# Quick Start: Deploying FMS for Farm Use

This is a simplified guide for getting FMS running in production quickly.

## Prerequisites

- A server (VPS, cloud instance, or dedicated server)
- Domain name (optional but recommended)
- SSH access to server
- Basic Linux knowledge

## Step-by-Step Deployment

### 1. Server Preparation (5-10 minutes)

```bash
# Connect to your server via SSH
ssh user@your-server-ip

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP, MySQL, Nginx
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    mysql-server nginx composer git
```

### 2. Database Setup (2 minutes)

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE fms_production;
CREATE USER 'fms_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fms_production.* TO 'fms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Deploy Application (5 minutes)

```bash
# Create application directory
sudo mkdir -p /var/www/fms
sudo chown $USER:$USER /var/www/fms

# Clone or upload your code
cd /var/www/fms
# If using git:
git clone <your-repo> .

# Install dependencies
composer install --optimize-autoloader --no-dev

# Setup environment
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment (3 minutes)

Edit `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_DATABASE=fms_production
DB_USERNAME=fms_user
DB_PASSWORD=your_secure_password

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 5. Run Migrations (1 minute)

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

### 6. Configure Nginx (5 minutes)

```bash
sudo nano /etc/nginx/sites-available/fms
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/fms/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/fms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 7. Set Permissions (1 minute)

```bash
sudo chown -R www-data:www-data /var/www/fms
sudo chmod -R 755 /var/www/fms
sudo chmod -R 775 /var/www/fms/storage
sudo chmod -R 775 /var/www/fms/bootstrap/cache
```

### 8. SSL Certificate (5 minutes)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 9. Test the Application

Visit `https://yourdomain.com` - you should see the landing page!

## Post-Deployment Tasks

### 1. Create Production Admin User

```bash
php artisan tinker
```

```php
$admin = App\Models\User::create([
    'name' => 'Farm Administrator',
    'email' => 'admin@yourfarm.com',
    'password' => Hash::make('secure_password'),
]);
$admin->assignRole('ADMIN');
```

### 2. Set Up Backups

```bash
# Create backup script
cat > /home/backup-fms.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u fms_user -p'password' fms_production > /home/backups/db_$DATE.sql
find /home/backups -name "db_*.sql" -mtime +7 -delete
EOF

chmod +x /home/backup-fms.sh
mkdir -p /home/backups

# Add to crontab (daily at 2 AM)
(crontab -l 2>/dev/null; echo "0 2 * * * /home/backup-fms.sh") | crontab -
```

### 3. Configure Monitoring

Consider setting up:
- **Uptime monitoring**: UptimeRobot, Pingdom
- **Error tracking**: Sentry (free tier available)
- **Log aggregation**: Papertrail, Loggly

## Frontend Options

### Option 1: Use Existing Landing Page
The current landing page can be enhanced to include:
- Farm dashboard
- Data visualization
- API testing interface

### Option 2: Build SPA
- Use Vue.js, React, or Angular
- Deploy to Vercel/Netlify
- Connect to your API

### Option 3: Mobile App
- React Native or Flutter
- Connect to same API
- Works offline with sync

## Integration Checklist

- [ ] Connect real scale devices (update ScaleService implementations)
- [ ] Set up label printers (update LabelPrinterService)
- [ ] Configure IoT sensors (if applicable)
- [ ] Set up email notifications
- [ ] Configure SMS alerts (optional)
- [ ] Integrate payment gateways (if needed)
- [ ] Set up reporting and analytics

## Support Resources

- **API Documentation**: Available at `/api/v1` endpoints
- **Test Scripts**: Use `test-api-simple.php` for testing
- **Laravel Docs**: https://laravel.com/docs

## Estimated Timeline

- **Basic Deployment**: 30-60 minutes
- **Full Production Setup**: 2-4 hours
- **Frontend Development**: 1-4 weeks (depending on complexity)
- **Hardware Integration**: 1-2 weeks (depending on devices)

## Cost Estimates

### Minimum Setup (Small Farm)
- VPS: $5-10/month (DigitalOcean, Linode)
- Domain: $10-15/year
- SSL: Free (Let's Encrypt)
- **Total**: ~$10/month

### Recommended Setup (Medium Farm)
- VPS: $20-40/month
- Domain: $10-15/year
- Monitoring: $0-10/month
- Backup Storage: $5/month
- **Total**: ~$30-50/month

### Enterprise Setup (Large Farm/Multiple Farms)
- Dedicated Server: $100-500/month
- CDN: $20-100/month
- Monitoring: $50-200/month
- Support: Variable
- **Total**: $200-1000+/month

---

**Your FMS backend is now ready for production use!** 🎉

Next steps: Build or integrate a frontend, connect hardware devices, and start managing farms!

