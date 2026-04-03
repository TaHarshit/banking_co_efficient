import fitz
import os

PDF_PATH = "data/input_pdf.pdf"
OUTPUT_DIR = "data/images"

def extract_images():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    doc = fitz.open(PDF_PATH)

    image_records = []

    for page_num in range(len(doc)):
        page = doc[page_num]
        images = page.get_images(full=True)

        for idx, img in enumerate(images):
            xref = img[0]
            pix = fitz.Pixmap(doc, xref)

            img_name = f"page_{page_num+1}_img_{idx+1}.png"
            img_path = os.path.join(OUTPUT_DIR, img_name)

            if pix.n < 5:
                pix.save(img_path)
            else:
                pix = fitz.Pixmap(fitz.csRGB, pix)
                pix.save(img_path)

            image_records.append({
                "page": page_num+1,
                "image_file": img_name
            })

    import json
    with open("data/image_map.json", "w") as f:
        json.dump(image_records, f, indent=2)

    print("Images extracted → data/images + data/image_map.json")

if __name__ == "__main__":
    extract_images()

