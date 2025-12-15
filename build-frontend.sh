#!/bin/bash
# Script to build frontend locally and prepare for upload

echo "=== Building Farm Management System Frontend ==="
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "Error: Node.js is not installed"
    echo "Please install Node.js from https://nodejs.org/"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "Error: npm is not installed"
    echo "Please install npm (comes with Node.js)"
    exit 1
fi

echo "Node.js version: $(node --version)"
echo "npm version: $(npm --version)"
echo ""

# Install dependencies
echo "Installing dependencies..."
npm install

if [ $? -ne 0 ]; then
    echo "Error: Failed to install dependencies"
    exit 1
fi

# Build for production
echo ""
echo "Building for production..."
npm run build

if [ $? -ne 0 ]; then
    echo "Error: Build failed"
    exit 1
fi

echo ""
echo "✓ Build complete!"
echo ""
echo "Built files are in: public/build/"
echo ""
echo "To upload to server:"
echo "  scp -r public/build/ user@server:/path/to/fms/public/"
echo ""
echo "Or use an SFTP/FTP client to upload the public/build/ directory"

