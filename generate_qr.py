#!/usr/bin/env python3
"""
Orah House - WhatsApp QR Code Generator
Generates high-resolution PNG and JPG QR codes for WhatsApp ordering & table service.

Default WhatsApp Link:
https://wa.me/919429693199?text=Hello%2C%20I%20Am%20at%20Orah%20House..!
"""

import os
import sys
import argparse

DEFAULT_URL = "https://wa.me/919429693199?text=Hello%2C%20I%20Am%20at%20Orah%20House..!"

def generate_local_qr(url, base_filename="whatsapp_orah_qr", output_dir="."):
    """Generate high-res PNG and JPG QR codes using python qrcode & pillow."""
    try:
        import qrcode
        from PIL import Image
    except ImportError:
        return False

    os.makedirs(output_dir, exist_ok=True)
    png_path = os.path.join(output_dir, f"{base_filename}.png")
    jpg_path = os.path.join(output_dir, f"{base_filename}.jpg")
    branded_png_path = os.path.join(output_dir, f"{base_filename}_branded.png")

    # High error correction (Q = ~25% or H = ~30%) ensures reliable scanning in any restaurant lighting
    qr = qrcode.QRCode(
        version=None,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=20,  # ~1000px high-resolution print ready
        border=4,
    )
    qr.add_data(url)
    qr.make(fit=True)

    # 1. Standard Crisp Black & White (PNG)
    img_standard = qr.make_image(fill_color="black", back_color="white").convert("RGB")
    img_standard.save(png_path, "PNG", dpi=(300, 300))
    print(f"✅ Generated PNG: {png_path} ({img_standard.width}x{img_standard.height}px, 300 DPI)")

    # 2. Standard JPEG (JPG)
    img_standard.save(jpg_path, "JPEG", quality=95, dpi=(300, 300))
    print(f"✅ Generated JPG: {jpg_path} (High Quality JPEG)")

    # 3. Orah House Signature Branded Edition (Burgundy #681418 on Cream #FBF6EE)
    try:
        img_branded = qr.make_image(
            fill_color="#681418",  # Orah House Signature Burgundy
            back_color="#FBF6EE"   # Orah House Warm Cream
        ).convert("RGB")
        img_branded.save(branded_png_path, "PNG", dpi=(300, 300))
        print(f"✅ Generated Branded Edition: {branded_png_path} (Burgundy on Warm Cream)")
    except Exception as e:
        pass

    return True

def generate_fallback_qr(url, base_filename="whatsapp_orah_qr", output_dir="."):
    """Fallback generator using standard library urllib without external pip dependencies."""
    import urllib.request
    import urllib.parse

    os.makedirs(output_dir, exist_ok=True)
    png_path = os.path.join(output_dir, f"{base_filename}.png")
    jpg_path = os.path.join(output_dir, f"{base_filename}.jpg")

    encoded_url = urllib.parse.quote(url)
    api_url = f"https://api.qrserver.com/v1/create-qr-code/?data={encoded_url}&size=1000x1000&ecc=H&margin=4&format=png"

    print("Fetching high-res QR code via fallback service...")
    req = urllib.request.Request(api_url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req) as response:
        png_data = response.read()

    with open(png_path, "wb") as f:
        f.write(png_data)
    print(f"✅ Generated PNG: {png_path}")

    # Write as JPG as well
    with open(jpg_path, "wb") as f:
        f.write(png_data)
    print(f"✅ Generated JPG: {jpg_path}")

    return True

def main():
    parser = argparse.ArgumentParser(description="Generate WhatsApp QR code (PNG & JPG) for Orah House")
    parser.add_argument(
        "--url",
        type=str,
        default=DEFAULT_URL,
        help="WhatsApp link to encode"
    )
    parser.add_argument(
        "--output",
        type=str,
        default="whatsapp_orah_qr",
        help="Base output filename (without extension)"
    )
    parser.add_argument(
        "--dir",
        type=str,
        default=".",
        help="Directory to save the generated QR files"
    )

    args = parser.parse_args()

    print("=" * 60)
    print("  ORAH HOUSE &bull; WHATSAPP QR CODE GENERATOR")
    print("=" * 60)
    print(f"Target URL: {args.url}\n")

    success = generate_local_qr(args.url, args.output, args.dir)
    if not success:
        generate_fallback_qr(args.url, args.output, args.dir)

    print("\n🎉 Complete! The QR code is ready to scan, print, or use on social media & table stands.")

if __name__ == "__main__":
    main()
