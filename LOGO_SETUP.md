# Logo and Favicon Setup Instructions

## Step 1: Add the Logo Image

1. Place your Ogenki Farms logo file in the `public/images/` directory
2. Name it `ogenki-logo.png` (or update the path in `Layout.jsx` if using a different name)
3. Recommended size: 200-300px width, transparent background preferred

## Step 2: Generate Favicon Files

You can generate favicon files from your logo using one of these methods:

### Option A: Using Online Tools (Easiest)

1. Visit https://realfavicongenerator.net/ or https://favicon.io/
2. Upload your logo image
3. Download the generated favicon package
4. Extract and place the following files in `public/images/`:
   - `favicon-16x16.png`
   - `favicon-32x32.png`
   - `apple-touch-icon.png`
5. Replace `public/favicon.ico` with the generated `favicon.ico`

### Option B: Using ImageMagick (Command Line)

If you have ImageMagick installed, run these commands from the project root:

```bash
# Generate favicon.ico (32x32)
convert public/images/ogenki-logo.png -resize 32x32 public/favicon.ico

# Generate PNG favicons
convert public/images/ogenki-logo.png -resize 16x16 public/images/favicon-16x16.png
convert public/images/ogenki-logo.png -resize 32x32 public/images/favicon-32x32.png
convert public/images/ogenki-logo.png -resize 180x180 public/images/apple-touch-icon.png
```

### Option C: Using Python with PIL/Pillow

If you have Python with Pillow installed, you can use the provided script:

```bash
python3 generate_favicons.py
```

## Step 3: Verify

1. Clear your browser cache
2. Hard refresh the page (Ctrl+Shift+R or Cmd+Shift+R)
3. Check that:
   - Logo appears in the sidebar
   - Favicon appears in the browser tab
   - Mobile view shows the logo correctly

## File Structure

After setup, your `public/` directory should have:

```
public/
├── favicon.ico
└── images/
    ├── ogenki-logo.png
    ├── favicon-16x16.png
    ├── favicon-32x32.png
    └── apple-touch-icon.png
```

## Notes

- The application will fallback to the Tractor icon if the logo image is not found
- Logo should be in PNG format with transparent background for best results
- For best quality, use a high-resolution source image (at least 512x512px)
