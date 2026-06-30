import fitz
import os
import glob
import json

DATA_DIR = "data"
OUTPUT_DIR = "data/images"

def extract_images():
    if os.path.exists(OUTPUT_DIR):
        import shutil
        shutil.rmtree(OUTPUT_DIR)
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    
    # Find all PDFs in the data directory
    pdf_files = glob.glob(os.path.join(DATA_DIR, "*.pdf"))
    if not pdf_files:
        print(f"No PDFs found in {DATA_DIR}/")
        return

    image_records = []

    for pdf_path in pdf_files:
        source_name = os.path.basename(pdf_path)
        print(f"Generating page snapshots for: {source_name}...")
        
        doc = fitz.open(pdf_path)
        
        for page_num in range(len(doc)):
            page = doc[page_num]
            
            # Render the entire page as an image (150 DPI is a good balance of quality/size)
            pix = page.get_pixmap(dpi=150)
            
            img_name = f"{source_name}_page_{page_num+1}_snapshot.png"
            img_path = os.path.join(OUTPUT_DIR, img_name)
            
            # Save the snapshot
            pix.save(img_path)

            image_records.append({
                "source": source_name,
                "page": page_num+1,
                "image_file": img_name
            })

    with open(os.path.join(DATA_DIR, "image_map.json"), "w") as f:
        json.dump(image_records, f, indent=2)

    print("Page snapshots generated → data/images + data/image_map.json")

if __name__ == "__main__":
    extract_images()
