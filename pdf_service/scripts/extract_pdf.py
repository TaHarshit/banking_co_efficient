import fitz
import json
import os
from pathlib import Path

import pymupdf4llm

from dotenv import load_dotenv

# Load .env
env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

DATA_DIR = Path("data")
OUTPUT_JSON = DATA_DIR / "extracted.json"

# Minimum characters for a page to be considered non-empty
MIN_PAGE_TEXT_LENGTH = 20


def extract_pdfs():
    all_extracted = []
    
    # Find all PDFs in data directory
    pdf_files = list(DATA_DIR.glob("*.pdf"))
    print(f"Found {len(pdf_files)} PDF(s): {[f.name for f in pdf_files]}")

    for pdf_path in pdf_files:
        print(f"Extracting: {pdf_path.name}...")
        doc = fitz.open(pdf_path)
        total_pages = len(doc)
        
        # Strategy A: page_chunks=True (Fastest & most direct)
        print(f"  Running single-pass chunked markdown extraction for {total_pages} pages...")
        extracted_pages = []
        try:
            # page_chunks=True returns list of dicts: [{"text": "...", "page": 1}, ...]
            chunks = pymupdf4llm.to_markdown(doc, page_chunks=True)
            for chunk in chunks:
                # Page numbers in pymupdf4llm page_chunks are 0-indexed or 1-indexed depending on version
                # To be safe, we retrieve page or look at chunk metadata
                pg = chunk.get("page", 0) + 1  # Often 0-indexed
                # Some versions might return 1-indexed, let's calibrate
                # We can also fall back to checking sequence
                text = chunk.get("text", "").strip()
                extracted_pages.append((pg, text))
            print(f"  Successfully extracted {len(extracted_pages)} page chunks.")
        except Exception as e:
            print(f"  page_chunks strategy failed: {e}. Trying full markdown splitting...")
            extracted_pages = []

        # Strategy B: If Strategy A failed, try full markdown extraction and splitting
        if not extracted_pages:
            try:
                full_md = pymupdf4llm.to_markdown(doc)
            except Exception as e:
                print(f"  Full markdown extraction failed: {e}")
                full_md = None

            if full_md:
                # Detect different page break indicators: form-feed or pagebreak comment
                if '\f' in full_md:
                    pages_md = full_md.split('\f')
                elif '----- pagebreak -----' in full_md:
                    pages_md = full_md.split('----- pagebreak -----')
                elif '---pagebreak---' in full_md:
                    pages_md = full_md.split('---pagebreak---')
                else:
                    pages_md = [full_md]

                if len(pages_md) >= total_pages:
                    print(f"  Successfully split full markdown into {len(pages_md)} pages.")
                    for page_num in range(total_pages):
                        text = pages_md[page_num].strip() if page_num < len(pages_md) else ""
                        extracted_pages.append((page_num + 1, text))
                else:
                    print(f"  Markdown split gave {len(pages_md)} parts (expected {total_pages}). Falling back to per-page Fitz...")
            
        # Strategy C: Fall back to Fitz per-page text extraction (reliable, fast local fallback)
        if not extracted_pages:
            print("  Using per-page PyMuPDF/Fitz text extraction...")
            for page_num in range(total_pages):
                text = doc[page_num].get_text("text").strip()
                extracted_pages.append((page_num + 1, text))

        # Add to all_extracted, filtering out empty pages
        for pg_num, text in extracted_pages:
            # We also ensure page number calibration does not exceed total pages
            calibrated_pg = pg_num
            if calibrated_pg > total_pages:
                calibrated_pg = total_pages
                
            if len(text) < MIN_PAGE_TEXT_LENGTH:
                print(f"  Skipping page {calibrated_pg} of {pdf_path.name} (too short: {len(text)} chars)")
                continue

            all_extracted.append({
                "source": pdf_path.name,
                "page": calibrated_pg,
                "text": text
            })

    with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
        json.dump(all_extracted, f, indent=2, ensure_ascii=False)

    print(f"Extraction complete. Saved {len(all_extracted)} total pages to {OUTPUT_JSON}")

if __name__ == "__main__":
    extract_pdfs()
