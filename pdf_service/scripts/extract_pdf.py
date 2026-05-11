import fitz
import json
import base64
import os
from pathlib import Path

DATA_DIR = Path("data")
OUTPUT_JSON = DATA_DIR / "extracted.json"

def render_vector_blocks(page):
    raw = page.get_text("rawdict")
    vector_rects = []

    for block in raw["blocks"]:
        if "bbox" in block:
            x0, y0, x1, y1 = block["bbox"]
            vector_rects.append(fitz.Rect(x0, y0, x1, y1))

    if not vector_rects:
        return None

    # Merge all vector areas into one bounding box
    area = vector_rects[0]
    for r in vector_rects[1:]:
        area |= r

    # Render the vector region into PNG
    pix = page.get_pixmap(clip=area, dpi=300)
    img_bytes = pix.tobytes("png")
    base64_img = base64.b64encode(img_bytes).decode("utf-8")

    return {
        "mime": "image/png",
        "data": base64_img
    }

def extract_pdfs():
    all_extracted = []
    
    # Find all PDFs in data directory
    pdf_files = list(DATA_DIR.glob("*.pdf"))
    print(f"Found {len(pdf_files)} PDF(s): {[f.name for f in pdf_files]}")

    for pdf_path in pdf_files:
        print(f"Extracting: {pdf_path.name}...")
        doc = fitz.open(pdf_path)
        
        for page_num, page in enumerate(doc):
            text = page.get_text("text")
            diagram = render_vector_blocks(page)

            all_extracted.append({
                "source": pdf_path.name,
                "page": page_num + 1,
                "text": text,
                "diagram": diagram  # may be None
            })

    with open(OUTPUT_JSON, "w") as f:
        json.dump(all_extracted, f, indent=2)

    print(f"Extraction complete. Saved {len(all_extracted)} total pages to {OUTPUT_JSON}")

if __name__ == "__main__":
    extract_pdfs()
