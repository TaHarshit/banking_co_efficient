"""Quick script to peek at how headings appear in the PDF text extraction."""
import fitz
import pymupdf4llm
from pathlib import Path

DATA_DIR = Path("data")
pdf_path = DATA_DIR / "Sales_and_negociation_OK-2.pdf"

doc = fitz.open(str(pdf_path))
print(f"Total pages: {len(doc)}")

# Extract a few key pages where chapters likely start
# Check pages around known chapter starts: p39 (Ch1), and look for Ch2-4 patterns
# Also check the table of contents pages (usually first few pages)
pages_to_check = [0, 1, 2, 3, 4, 5, 38, 39, 40, 70, 71, 72, 100, 101, 120, 121, 150, 151, 182, 183]

for page_num in pages_to_check:
    if page_num >= len(doc):
        continue
    try:
        md = pymupdf4llm.to_markdown(doc, pages=[page_num])
        # Show first 800 chars of each page
        preview = md[:800] if md else "(empty)"
        print(f"\n{'='*60}")
        print(f"PAGE {page_num + 1}")
        print(f"{'='*60}")
        print(preview)
        if len(md) > 800:
            print(f"... ({len(md)} total chars)")
    except Exception as e:
        print(f"PAGE {page_num + 1}: Error: {e}")

doc.close()
