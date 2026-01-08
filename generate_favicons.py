#!/usr/bin/env python3
"""
Generate favicon files from the Ogenki Farms logo.

Requirements:
    pip install Pillow

Usage:
    python3 generate_favicons.py
"""

import os
from PIL import Image

def generate_favicons():
    """Generate favicon files from the logo."""
    
    # Paths
    logo_path = 'public/images/ogenki-logo.png'
    output_dir = 'public/images'
    favicon_ico_path = 'public/favicon.ico'
    
    # Check if logo exists
    if not os.path.exists(logo_path):
        print(f"Error: Logo file not found at {logo_path}")
        print("Please place your logo file at public/images/ogenki-logo.png")
        return False
    
    try:
        # Open the logo image
        logo = Image.open(logo_path)
        
        # Convert to RGB if necessary (for ICO format)
        if logo.mode != 'RGB':
            logo = logo.convert('RGB')
        
        # Create output directory if it doesn't exist
        os.makedirs(output_dir, exist_ok=True)
        
        # Generate favicon.ico (32x32)
        print("Generating favicon.ico (32x32)...")
        favicon_32 = logo.resize((32, 32), Image.Resampling.LANCZOS)
        favicon_32.save(favicon_ico_path, format='ICO', sizes=[(32, 32)])
        print(f"✓ Created {favicon_ico_path}")
        
        # Generate favicon-16x16.png
        print("Generating favicon-16x16.png...")
        favicon_16 = logo.resize((16, 16), Image.Resampling.LANCZOS)
        favicon_16.save(os.path.join(output_dir, 'favicon-16x16.png'), format='PNG')
        print(f"✓ Created {output_dir}/favicon-16x16.png")
        
        # Generate favicon-32x32.png
        print("Generating favicon-32x32.png...")
        favicon_32_png = logo.resize((32, 32), Image.Resampling.LANCZOS)
        favicon_32_png.save(os.path.join(output_dir, 'favicon-32x32.png'), format='PNG')
        print(f"✓ Created {output_dir}/favicon-32x32.png")
        
        # Generate apple-touch-icon.png (180x180)
        print("Generating apple-touch-icon.png (180x180)...")
        apple_icon = logo.resize((180, 180), Image.Resampling.LANCZOS)
        apple_icon.save(os.path.join(output_dir, 'apple-touch-icon.png'), format='PNG')
        print(f"✓ Created {output_dir}/apple-touch-icon.png")
        
        print("\n✅ All favicon files generated successfully!")
        return True
        
    except ImportError:
        print("Error: Pillow library not installed.")
        print("Install it with: pip install Pillow")
        return False
    except Exception as e:
        print(f"Error generating favicons: {e}")
        return False

if __name__ == '__main__':
    generate_favicons()
