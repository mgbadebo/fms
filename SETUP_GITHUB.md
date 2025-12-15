# Quick Setup: Push to GitHub

Follow these steps to push your FMS code to GitHub and set up deployment.

## Step 1: Create GitHub Repository

1. Go to https://github.com/new
2. Repository name: `farm-management-system` (or your preferred name)
3. Description: "Farm Management System - Laravel Backend API"
4. Choose **Private** (recommended) or Public
5. **DO NOT** initialize with README, .gitignore, or license (we already have these)
6. Click "Create repository"

## Step 2: Push Your Code

Run these commands in your terminal:

```bash
cd /Users/mosesgbadebo/FMS

# Make initial commit
git add .
git commit -m "Initial commit: Farm Management System backend API"

# Rename branch to main (if needed)
git branch -M main

# Add GitHub remote (replace with your repository URL)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git

# Push to GitHub
git push -u origin main
```

**Note:** You'll be prompted for your GitHub username and password (or use a Personal Access Token).

## Step 3: Verify Push

1. Go to your GitHub repository page
2. You should see all your files
3. Check that `.env` is **NOT** in the repository (it should be ignored)

## Step 4: Set Up Server Deployment

See `GITHUB_DEPLOYMENT.md` for complete server setup instructions.

### Quick Server Setup:

```bash
# On your server
ssh user@your-server-ip

# Clone repository
cd /var/www
sudo mkdir fms
sudo chown $USER:$USER fms
cd fms
git clone https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git .

# Follow the rest of GITHUB_DEPLOYMENT.md
```

## Step 5: Future Updates

After making changes locally:

```bash
git add .
git commit -m "Description of changes"
git push origin main
```

Then on server:
```bash
cd /var/www/fms
./deploy.sh
```

Or set up GitHub Actions for automatic deployment (see `GITHUB_DEPLOYMENT.md`).

---

**That's it! Your code is now on GitHub and ready for deployment.** 🎉

