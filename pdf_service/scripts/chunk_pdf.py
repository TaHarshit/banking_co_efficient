import json

INPUT = "data/extracted.json"
IMAGE_MAP = "data/image_map.json"
OUTPUT = "data/chunks.json"

CHUNK_SIZE = 1200
OVERLAP = 200


def chunk_text(text):
    chunks = []
    start = 0
    text_len = len(text)
    
    if text_len == 0:
        return []
        
    while start < text_len:
        end = start + CHUNK_SIZE
        if end >= text_len:
            chunks.append(text[start:text_len].strip())
            break
        
        # Find a clean breaking point to avoid splitting words/sentences
        break_point = end
        for separator in ['\n\n', '\n', '. ', ' ']:
            pos = text.rfind(separator, start, end)
            if pos != -1:
                break_point = pos + len(separator)
                break
                
        chunk = text[start:break_point].strip()
        if chunk:
            chunks.append(chunk)
            
        new_start = break_point - OVERLAP
        
        # Failsafe: we MUST always advance forward. 
        # If the overlap pulls us backward or keeps us in the same spot, ignore overlap.
        if new_start <= start:
            start = break_point
        else:
            start = new_start
            
    return chunks


def chunk_pdf():
    # Load extracted text
    with open(INPUT, "r", encoding="utf-8") as f:
        pages = json.load(f)

    # Load image map (page → images)
    try:
        with open(IMAGE_MAP, "r", encoding="utf-8") as f:
            image_map = json.load(f)
    except:
        image_map = []

    # Create a dict: (source, page) → list of images
    page_to_images = {}
    for img in image_map:
        key = (img["source"], img["page"])
        page_to_images.setdefault(key, []).append(img["image_file"])

    all_chunks = []
    chunk_id = 1

    for p in pages:
        source = p.get("source", "unknown")
        page_num = p["page"]
        page_text = p["text"]

        # Get images for this page/source
        images_for_page = page_to_images.get((source, page_num), [])

        # Chunk text intelligently
        chunks = chunk_text(page_text)

        # Add metadata
        for c in chunks:
            all_chunks.append({
                "id": f"chunk_{chunk_id}",
                "source": source,
                "page": page_num,
                "images": images_for_page,
                "text": c
            })
            chunk_id += 1

    # Save output
    with open(OUTPUT, "w", encoding="utf-8") as f:
        json.dump(all_chunks, f, indent=2, ensure_ascii=False)

    print(f"Chunking complete. Total chunks created: {len(all_chunks)} → {OUTPUT}")


if __name__ == "__main__":
    chunk_pdf()
