# Installing Node.js and npm on Your Server

## Option 1: Install Node.js and npm on Server (Recommended)

### For Ubuntu/Debian:

```bash
# Update package list
sudo apt update

# Install Node.js 20.x (LTS)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Verify installation
node --version
npm --version
```

### For CentOS/RHEL:

```bash
# Install Node.js 20.x
curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo yum install -y nodejs

# Verify installation
node --version
npm --version
```

### For Shared Hosting (cPanel/Managed Hosting):

1. **Check if Node.js is available in your control panel:**
   - Look for "Node.js" or "Node.js Selector" in your hosting control panel
   - Some hosts provide Node.js version selection

2. **If not available, contact your hosting provider** to enable Node.js

3. **Or use Option 2 below** (build locally and upload)

## Option 2: Build Locally and Upload (Alternative)

If you can't install Node.js on your server, build the frontend on your local machine and upload the built files.

### Step 1: Build on Your Local Machine

On your local computer (where you have Node.js):

```bash
cd /Users/mosesgbadebo/FMS

# Install dependencies
npm install

# Build for production
npm run build
```

This creates the built files in `public/build/`

### Step 2: Upload Built Files to Server

Upload the `public/build/` directory to your server:

```bash
# Using SCP (from your local machine)
scp -r public/build/ user@your-server:/home/sites/25b/b/ba662d9635/fms/public/

# Or using SFTP/FTP client
# Upload the entire public/build/ directory
```

### Step 3: Verify on Server

On your server:

```bash
cd /home/sites/25b/b/ba662d9635/fms
ls -la public/build/
# Should see manifest.json and asset files
```

## Option 3: Use NVM (Node Version Manager)

If you have shell access but can't use apt/yum:

```bash
# Install NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Reload shell
source ~/.bashrc

# Install Node.js
nvm install 20
nvm use 20

# Verify
node --version
npm --version
```

## After Installing Node.js/npm

Once npm is available, run:

```bash
cd /home/sites/25b/b/ba662d9635/fms

# Install frontend dependencies
npm install

# Build for production
npm run build

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Troubleshooting

### Permission Issues

If you get permission errors:

```bash
# Fix npm permissions (if needed)
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
source ~/.bashrc
```

### Check Node.js Version

Make sure you have a recent version:

```bash
node --version  # Should be 18.x or higher
npm --version   # Should be 9.x or higher
```

## Quick Check

Run this to see what's available:

```bash
which node
which npm
node --version 2>/dev/null || echo "Node.js not found"
npm --version 2>/dev/null || echo "npm not found"
```

