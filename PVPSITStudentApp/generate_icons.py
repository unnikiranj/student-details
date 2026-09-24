import os
from PIL import Image

def generate_icons():
    # Source image path
    source_image = r"C:\Users\Dell\.gemini\antigravity-ide\brain\49416434-a168-4d8c-828e-d299f71f03aa\pvpsit_app_logo_1788327905215.jpg"
    
    # Target res directory
    res_dir = r"c:\xampp\htdocs\studentdetails\PVPSITStudentApp\app\src\main\res"
    
    # Android icon sizes
    sizes = {
        "mipmap-mdpi": 48,
        "mipmap-hdpi": 72,
        "mipmap-xhdpi": 96,
        "mipmap-xxhdpi": 144,
        "mipmap-xxxhdpi": 192
    }
    
    try:
        img = Image.open(source_image)
        # Convert to RGBA for PNG
        img = img.convert("RGBA")
        
        for folder, size in sizes.items():
            folder_path = os.path.join(res_dir, folder)
            os.makedirs(folder_path, exist_ok=True)
            
            # Resize image
            resized_img = img.resize((size, size), Image.Resampling.LANCZOS)
            
            # Save square icon
            resized_img.save(os.path.join(folder_path, "ic_launcher.png"), "PNG")
            
            # Create round icon (simple circle crop)
            mask = Image.new('L', (size, size), 0)
            from PIL import ImageDraw
            draw = ImageDraw.Draw(mask)
            draw.ellipse((0, 0, size, size), fill=255)
            
            round_img = Image.new('RGBA', (size, size))
            round_img.paste(resized_img, (0, 0), mask=mask)
            
            round_img.save(os.path.join(folder_path, "ic_launcher_round.png"), "PNG")
            
        print("Successfully generated all mipmap icons!")
    except Exception as e:
        print(f"Error generating icons: {e}")

if __name__ == "__main__":
    generate_icons()
