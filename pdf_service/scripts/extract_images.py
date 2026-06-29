import fitz
import os
import glob
import json

DATA_DIR = "data"
OUTPUT_DIR = "data/images"

def extract_images():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Find all PDFs in the data directory
    pdf_files = glob.glob(os.path.join(DATA_DIR, "*.pdf"))
    if not pdf_files:
        print(f"No PDFs found in {DATA_DIR}/")
        return

    image_records = []

    for pdf_path in pdf_files:
        source_name = os.path.basename(pdf_path)
        print(f"Extracting images from: {source_name}...")
        
        doc = fitz.open(pdf_path)
        
        for page_num in range(len(doc)):
            page = doc[page_num]
            images = page.get_images(full=True)

            for idx, img in enumerate(images):
                xref = img[0]
                pix = fitz.Pixmap(doc, xref)

                # Filter out small decorative images, icons, or lines
                MIN_WIDTH = 150
                MIN_HEIGHT = 150

                if pix.width < MIN_WIDTH or pix.height < MIN_HEIGHT:
                    continue  # Skip this image as it's likely decorative

                img_name = f"{source_name}_page_{page_num+1}_img_{idx+1}.png"
                img_path = os.path.join(OUTPUT_DIR, img_name)

                if pix.n < 5:
                    pix.save(img_path)
                else:
                    pix = fitz.Pixmap(fitz.csRGB, pix)
                    pix.save(img_path)

                image_records.append({
                    "source": source_name,
                    "page": page_num+1,
                    "image_file": img_name
                })

    with open(os.path.join(DATA_DIR, "image_map.json"), "w") as f:
        json.dump(image_records, f, indent=2)

    print("Images extracted → data/images + data/image_map.json")

if __name__ == "__main__":
    extract_images()
