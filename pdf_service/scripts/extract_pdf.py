import fitz
import json
import base64
import os
from pathlib import Path

import pymupdf4llm

from dotenv import load_dotenv

# Load .env
env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

DATA_DIR = Path("data")
OUTPUT_JSON = DATA_DIR / "extracted.json"





def extract_pdfs():
    all_extracted = []
    
    # Find all PDFs in data directory
    pdf_files = list(DATA_DIR.glob("*.pdf"))
    print(f"Found {len(pdf_files)} PDF(s): {[f.name for f in pdf_files]}")

    for pdf_path in pdf_files:
        print(f"Extracting: {pdf_path.name}...")
        doc = fitz.open(pdf_path)
        
        # Determine language hint for OCR based on filename
        lang_hint = 'fra' if 'fr' in pdf_path.name.lower() else 'eng'
        
        for page_num, page in enumerate(doc):
            # Using pymupdf4llm for markdown extraction (preserves tables!)
            try:
                text = pymupdf4llm.to_markdown(doc, pages=[page_num]).strip()
            except Exception as e:
                print(f"Markdown extraction failed on page {page_num + 1}, falling back to fitz... Error: {e}")
                text = page.get_text("text").strip()
            
            # If no text found, we simply skip the OCR process
            if not text:
                print(f"No text on page {page_num + 1} of {pdf_path.name}, skipping OCR...")
                

            


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
