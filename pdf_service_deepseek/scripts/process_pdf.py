"""
Full PDF Processing Pipeline
Extracts text + images from a PDF, chunks the text, and creates embeddings.

Usage:
    python process_pdf.py                           # Uses default: ../data/input_pdf.pdf
    python process_pdf.py ../data/MyFile.pdf        # Uses a specific PDF
"""
import sys
import os
import json
import fitz
import base64
import numpy as np
import faiss
from sentence_transformers import SentenceTransformer

# --- Configuration ---
DATA_DIR = os.path.join(os.path.dirname(__file__), "..", "data")
IMAGES_DIR = os.path.join(DATA_DIR, "images")
EXTRACTED_JSON = os.path.join(DATA_DIR, "extracted.json")
IMAGE_MAP_JSON = os.path.join(DATA_DIR, "image_map.json")
CHUNKS_JSON = os.path.join(DATA_DIR, "chunks.json")
FAISS_INDEX = os.path.join(os.path.dirname(__file__), "..", "vectorstore", "index.faiss")
EMBED_VECTORS = os.path.join(os.path.dirname(__file__), "..", "embeddings", "vectors.npy")
EMBED_META = os.path.join(os.path.dirname(__file__), "..", "embeddings", "meta.json")

CHUNK_SIZE = 1200
OVERLAP = 200
EMBED_MODEL = "all-MiniLM-L6-v2"


def step1_extract_text(pdf_path):
    """Extract text and diagrams from PDF."""
    print(f"\n{'='*50}")
    print(f"STEP 1: Extracting text from PDF")
    print(f"{'='*50}")
    doc = fitz.open(pdf_path)
    extracted = []

    for page_num, page in enumerate(doc):
        text = page.get_text("text")
        extracted.append({
            "page": page_num + 1,
            "text": text,
        })
        print(f"  Page {page_num + 1}: {len(text)} chars")

    with open(EXTRACTED_JSON, "w") as f:
        json.dump(extracted, f, indent=2)

    print(f"  ✅ Saved to {EXTRACTED_JSON}")
    return extracted


def step2_extract_images(pdf_path):
    """Extract images from PDF."""
    print(f"\n{'='*50}")
    print(f"STEP 2: Extracting images from PDF")
    print(f"{'='*50}")
    os.makedirs(IMAGES_DIR, exist_ok=True)
    doc = fitz.open(pdf_path)
    image_records = []

    for page_num in range(len(doc)):
        page = doc[page_num]
        images = page.get_images(full=True)

        for idx, img in enumerate(images):
            xref = img[0]
            pix = fitz.Pixmap(doc, xref)
            img_name = f"page_{page_num+1}_img_{idx+1}.png"
            img_path = os.path.join(IMAGES_DIR, img_name)

            if pix.n < 5:
                pix.save(img_path)
            else:
                pix = fitz.Pixmap(fitz.csRGB, pix)
                pix.save(img_path)

            image_records.append({
                "page": page_num + 1,
                "image_file": img_name
            })

    with open(IMAGE_MAP_JSON, "w") as f:
        json.dump(image_records, f, indent=2)

    print(f"  ✅ Extracted {len(image_records)} images")
    return image_records


def step3_chunk_text():
    """Chunk extracted text into smaller pieces."""
    print(f"\n{'='*50}")
    print(f"STEP 3: Chunking text")
    print(f"{'='*50}")

    with open(EXTRACTED_JSON, "r") as f:
        pages = json.load(f)

    try:
        with open(IMAGE_MAP_JSON, "r") as f:
            image_map = json.load(f)
    except:
        image_map = []

    page_to_images = {}
    for img in image_map:
        page_to_images.setdefault(img["page"], []).append(img["image_file"])

    all_chunks = []
    chunk_id = 1

    for p in pages:
        page_num = p["page"]
        page_text = p["text"]
        images_for_page = page_to_images.get(page_num, [])

        # Chunk the text
        start = 0
        while start < len(page_text):
            end = start + CHUNK_SIZE
            chunk = page_text[start:end]
            all_chunks.append({
                "id": f"chunk_{chunk_id}",
                "page": page_num,
                "images": images_for_page,
                "text": chunk
            })
            chunk_id += 1
            start += CHUNK_SIZE - OVERLAP

    with open(CHUNKS_JSON, "w") as f:
        json.dump(all_chunks, f, indent=2)

    print(f"  ✅ Created {len(all_chunks)} chunks")
    return all_chunks


def step4_embed_chunks():
    """Create embeddings and FAISS index."""
    print(f"\n{'='*50}")
    print(f"STEP 4: Creating embeddings ({EMBED_MODEL})")
    print(f"{'='*50}")

    model = SentenceTransformer(EMBED_MODEL)

    with open(CHUNKS_JSON, "r") as f:
        chunks = json.load(f)

    texts = [c["text"] for c in chunks]

    print(f"  Embedding {len(texts)} chunks...")
    vectors = model.encode(texts, show_progress_bar=True)
    vectors = np.array(vectors).astype("float32")

    dimension = vectors.shape[1]
    index = faiss.IndexFlatL2(dimension)
    index.add(vectors)

    # Ensure output dirs exist
    os.makedirs(os.path.dirname(FAISS_INDEX), exist_ok=True)
    os.makedirs(os.path.dirname(EMBED_VECTORS), exist_ok=True)

    faiss.write_index(index, FAISS_INDEX)
    np.save(EMBED_VECTORS, vectors)

    with open(EMBED_META, "w") as f:
        json.dump(chunks, f, indent=2)

    print(f"  ✅ FAISS index created ({len(texts)} vectors, dim={dimension})")


def main():
    # Determine PDF path
    if len(sys.argv) > 1:
        pdf_path = sys.argv[1]
    else:
        pdf_path = os.path.join(DATA_DIR, "input_pdf.pdf")

    if not os.path.exists(pdf_path):
        print(f"❌ PDF not found: {pdf_path}")
        sys.exit(1)

    print(f"🚀 Processing PDF: {pdf_path}")
    print(f"   Data dir: {DATA_DIR}")

    step1_extract_text(pdf_path)
    step2_extract_images(pdf_path)
    step3_chunk_text()
    step4_embed_chunks()

    print(f"\n{'='*50}")
    print(f"✅ ALL DONE! Your PDF is ready for Q&A.")
    print(f"{'='*50}")
    print(f"\nStart the server with:")
    print(f"  cd ../api")
    print(f"  python -m uvicorn server:app --host 127.0.0.1 --port 8000 --reload")


if __name__ == "__main__":
    main()
