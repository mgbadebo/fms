# GitHub Deployment Guide

This guide will help you deploy the Farm Management System to a server using GitHub.

## Step 1: Initialize Git and Push to GitHub

### 1.1 Initialize Git Repository

```bash
cd /Users/mosesgbadebo/FMS
git init
git add .
git commit -m "Initial commit: Farm Management System backend"
```

### 1.2 Create GitHub Repository

1. Go to https://github.com and create a new repository
2. Name it something like `farm-management-system` or `fms-backend`
3. **Don't** initialize with README, .gitignore, or license (we already have these)

### 1.3 Push to GitHub

```bash
# Add your GitHub repository as remote
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git

# Or if using SSH:
# git remote add origin git@github.com:YOUR_USERNAME/YOUR_REPO_NAME.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## Step 2: Server Setup

### 2.1 Connect to Your Server

```bash
ssh user@your-server-ip
```

### 2.2 Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP, MySQL, Nginx, Git
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath \
    mysql-server nginx composer git

# Install Composer (if not installed)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2.3 Set Up Application Directory

```bash
# Create application directory
sudo mkdir -p /var/www/fms
sudo chown $USER:$USER /var/www/fms

# Clone from GitHub
cd /var/www/fms
git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git .

# Or if using SSH key:
# git clone git@github.com:YOUR_USERNAME/YOUR_REPO_NAME.git .
```

### 2.4 Install Dependencies

```bash
cd /var/www/fms
composer install --optimize-autoloader --no-dev
```

### 2.5 Configure Environment

```bash
cp .env.example .env
php artisan key:generate
nano .env  # Edit with your production settings
```

**Important `.env` settings:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=fms_production
DB_USERNAME=fms_user
DB_PASSWORD=your_secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 2.6 Set Up Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE fms_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'fms_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fms_production.* TO 'fms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2.7 Run Migrations

```bash
cd /var/www/fms
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder
php artisan storage:link
```

### 2.8 Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/fms
sudo chmod -R 755 /var/www/fms
sudo chmod -R 775 /var/www/fms/storage
sudo chmod -R 775 /var/www/fms/bootstrap/cache
```

### 2.9 Configure Nginx

Create `/etc/nginx/sites-available/fms`:

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

### 2.10 Set Up SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## Step 3: Automated Deployment

### Option A: Manual Deployment Script

The `deploy.sh` script is included in the repository. Use it for manual deployments:

```bash
# Make it executable
chmod +x /var/www/fms/deploy.sh

# Run deployment
/var/www/fms/deploy.sh
```

### Option B: GitHub Actions (Automated)

1. **Set up GitHub Secrets:**
   - Go to your GitHub repository
   - Settings → Secrets and variables → Actions
   - Add these secrets:
     - `HOST`: Your server IP address
     - `USERNAME`: SSH username
     - `SSH_KEY`: Your private SSH key

2. **Push to GitHub:**
   ```bash
   git add .
   git commit -m "Add deployment workflow"
   git push origin main
   ```

3. **Deployments will run automatically** when you push to main/master branch

### Option C: Webhook Deployment

Create a webhook endpoint on your server:

```bash
# Install webhook handler (example using PHP)
sudo nano /var/www/fms/public/webhook.php
```

```php
<?php
// Simple webhook handler (add authentication!)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true);
    
    if ($payload['ref'] === 'refs/heads/main') {
        exec('cd /var/www/fms && /var/www/fms/deploy.sh 2>&1', $output);
        echo json_encode(['status' => 'success', 'output' => $output]);
    }
}
```

Then configure GitHub webhook:
- Repository → Settings → Webhooks → Add webhook
- Payload URL: `https://yourdomain.com/webhook.php`
- Content type: `application/json`
- Secret: (add a secret for security)
- Events: Just the `push` event

## Step 4: Future Deployments

### Manual Deployment

```bash
ssh user@your-server
cd /var/www/fms
./deploy.sh
```

### Automated Deployment (GitHub Actions)

Just push to main branch:
```bash
git add .
git commit -m "Your changes"
git push origin main
```

GitHub Actions will automatically deploy!

## Step 5: Post-Deployment Checklist

- [ ] Test API endpoints
- [ ] Verify authentication works
- [ ] Check database connections
- [ ] Test scale reading endpoint
- [ ] Test label printing endpoint
- [ ] Set up monitoring
- [ ] Configure backups
- [ ] Update admin password
- [ ] Set up SSL certificate renewal

## Troubleshooting

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/fms
sudo chmod -R 775 /var/www/fms/storage
```

### Git Pull Issues
```bash
# If you have local changes
git stash
git pull
git stash pop
```

### Composer Issues
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### Cache Issues
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Security Notes

1. **Never commit `.env` file** - It's already in `.gitignore`
2. **Use strong passwords** for database and admin users
3. **Set up firewall** (UFW)
4. **Keep dependencies updated**: `composer update`
5. **Use SSH keys** instead of passwords for server access
6. **Enable 2FA** on GitHub account
7. **Review GitHub Actions** permissions

## Next Steps

After deployment:
1. Build or integrate frontend application
2. Connect hardware devices (scales, printers)
3. Set up monitoring and alerts
4. Create user accounts and configure farms
5. Train farm staff on using the system

---

**Your FMS is now ready for GitHub-based deployments!** 🚀

