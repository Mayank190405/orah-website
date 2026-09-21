from PIL import Image
import math

img = Image.open('/Users/kradsagency/.gemini/antigravity-ide/brain/84887bf4-7164-4845-9828-d6de4d45b9ee/hero_white_bg_1789128322189.jpg').convert("RGBA")
datas = img.getdata()

newData = []
for item in datas:
    # If the pixel is close to white, make it transparent
    if item[0] > 230 and item[1] > 230 and item[2] > 230:
        newData.append((255, 255, 255, 0))
    else:
        newData.append(item)

img.putdata(newData)
img.save('public/uploads/hero_transparent.png')
print("Done")
