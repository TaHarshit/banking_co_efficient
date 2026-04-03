import fitz
import json
import base64

PDF_PATH = "data/input_pdf.pdf"
OUTPUT_JSON = "data/extracted.json"

def render_vector_blocks(page):
    raw = page.get_text("rawdict")
    vector_rects = []

    for block in raw["blocks"]:
        if block["type"] == 4:  # vector path (lines, shapes)
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

def extract_pdf():
    doc = fitz.open(PDF_PATH)
    extracted = []

    for page_num, page in enumerate(doc):
        text = page.get_text("text")
        diagram = render_vector_blocks(page)

        extracted.append({
            "page": page_num + 1,
            "text": text,
            "diagram": diagram  # may be None
        })

    with open(OUTPUT_JSON, "w") as f:
        json.dump(extracted, f, indent=2)

    print("Extraction with diagrams complete →", OUTPUT_JSON)

if __name__ == "__main__":
    extract_pdf()
