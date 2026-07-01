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
        
        # Single-pass extraction — much faster than per-page calls
        print(f"  Running single-pass markdown extraction for {total_pages} pages...")
        try:
            full_md = pymupdf4llm.to_markdown(doc)
        except Exception as e:
            print(f"  Markdown extraction failed, falling back to fitz: {e}")
            full_md = None

        if full_md:
            # pymupdf4llm inserts form-feed characters (\f) or page-break markers between pages
            # Split by form-feed first; if that doesn't give the right count, fall back per-page
            pages_md = full_md.split('\f')
            
            if len(pages_md) >= total_pages:
                # Successfully split by form-feed
                for page_num in range(total_pages):
                    text = pages_md[page_num].strip() if page_num < len(pages_md) else ""
                    
                    if len(text) < MIN_PAGE_TEXT_LENGTH:
                        print(f"  Skipping page {page_num + 1} of {pdf_path.name} (too short: {len(text)} chars)")
                        continue
                    
                    all_extracted.append({
                        "source": pdf_path.name,
                        "page": page_num + 1,
                        "text": text
                    })
            else:
                # Form-feed split didn't work well, fall back to per-page extraction
                print(f"  Form-feed split gave {len(pages_md)} parts for {total_pages} pages, using per-page fallback...")
                for page_num in range(total_pages):
                    try:
                        text = pymupdf4llm.to_markdown(doc, pages=[page_num]).strip()
                    except Exception:
                        text = doc[page_num].get_text("text").strip()
                    
                    if len(text) < MIN_PAGE_TEXT_LENGTH:
                        print(f"  Skipping page {page_num + 1} of {pdf_path.name} (too short: {len(text)} chars)")
                        continue
                    
                    all_extracted.append({
                        "source": pdf_path.name,
                        "page": page_num + 1,
                        "text": text
                    })
        else:
            # Full markdown failed, extract per-page with fitz
            for page_num, page in enumerate(doc):
                text = page.get_text("text").strip()
                
                if len(text) < MIN_PAGE_TEXT_LENGTH:
                    print(f"  Skipping page {page_num + 1} of {pdf_path.name} (too short: {len(text)} chars)")
                    continue
                
                all_extracted.append({
                    "source": pdf_path.name,
                    "page": page_num + 1,
                    "text": text
                })

    with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
        json.dump(all_extracted, f, indent=2, ensure_ascii=False)

    print(f"Extraction complete. Saved {len(all_extracted)} total pages to {OUTPUT_JSON}")

if __name__ == "__main__":
    extract_pdfs()
