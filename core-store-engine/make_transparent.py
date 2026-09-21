from PIL import Image, ImageDraw

img = Image.open('public/uploads/hero_image.jpg').convert("RGBA")
width, height = img.size

# Determine a square crop
min_dim = min(width, height)
left = (width - min_dim) / 2
top = (height - min_dim) / 2
right = (width + min_dim) / 2
bottom = (height + min_dim) / 2

img = img.crop((left, top, right, bottom))
width, height = img.size

# Create a circular mask
mask = Image.new('L', (width, height), 0)
draw = ImageDraw.Draw(mask)
draw.ellipse((0, 0, width, height), fill=255)

# Apply mask
img.putalpha(mask)
img.save('public/uploads/hero_transparent.png')
print("Done")
