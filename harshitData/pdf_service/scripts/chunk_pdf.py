import json

INPUT = "data/extracted.json"
IMAGE_MAP = "data/image_map.json"
OUTPUT = "data/chunks.json"

CHUNK_SIZE = 1200
OVERLAP = 200


def chunk_text(text):
    chunks = []
    start = 0
    while start < len(text):
        end = start + CHUNK_SIZE
        chunk = text[start:end]
        chunks.append(chunk)
        start += CHUNK_SIZE - OVERLAP
    return chunks


def chunk_pdf():
    # Load extracted text
    with open(INPUT, "r") as f:
        pages = json.load(f)

    # Load image map (page → images)
    try:
        with open(IMAGE_MAP, "r") as f:
            image_map = json.load(f)
    except:
        image_map = []

    # Create a dict: page_number → list of images
    page_to_images = {}
    for img in image_map:
        page_to_images.setdefault(img["page"], []).append(img["image_file"])

    all_chunks = []
    chunk_id = 1

    for p in pages:
        page_num = p["page"]
        page_text = p["text"]

        # Get images for this page
        images_for_page = page_to_images.get(page_num, [])

        # Chunk text
        chunks = chunk_text(page_text)

        # Add metadata
        for c in chunks:
            all_chunks.append({
                "id": f"chunk_{chunk_id}",
                "page": page_num,
                "images": images_for_page,
                "text": c
            })
            chunk_id += 1

    # Save output
    with open(OUTPUT, "w") as f:
        json.dump(all_chunks, f, indent=2)

    print("Chunking + image mapping complete → data/chunks.json")


if __name__ == "__main__":
    chunk_pdf()

