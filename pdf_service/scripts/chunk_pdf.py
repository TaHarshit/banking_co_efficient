import json
import re

INPUT = "data/extracted.json"
IMAGE_MAP = "data/image_map.json"
OUTPUT = "data/chunks.json"

CHUNK_SIZE = 1500
OVERLAP = 200

# Regex to detect markdown headings: # Chapter, ## Section, ### Subsection
HEADING_PATTERN = re.compile(r'^(#{1,4})\s+(.+)$', re.MULTILINE)


def extract_headings_from_text(text):
    """
    Parse markdown headings from text and return a list of (level, title, position).
    Level 1 = #, Level 2 = ##, etc.
    """
    headings = []
    for match in HEADING_PATTERN.finditer(text):
        level = len(match.group(1))  # Number of # signs
        title = match.group(2).strip()
        position = match.start()
        headings.append((level, title, position))
    return headings


def get_heading_at_position(headings, position):
    """
    Given a list of headings and a character position, return the current
    chapter (level 1-2) and section (level 3-4) at that position.
    """
    current_chapter = ""
    current_section = ""
    
    for level, title, head_pos in headings:
        if head_pos > position:
            break
        if level <= 2:
            current_chapter = title
            current_section = ""  # Reset section when chapter changes
        elif level <= 4:
            current_section = title
    
    return current_chapter, current_section


def chunk_text_with_metadata(text, headings):
    """
    Chunk text and attach chapter/section metadata to each chunk
    based on which heading region the chunk falls in.
    """
    chunks = []
    start = 0
    text_len = len(text)
    
    if text_len == 0:
        return []
        
    while start < text_len:
        end = start + CHUNK_SIZE
        if end >= text_len:
            chunk_text = text[start:text_len].strip()
            if chunk_text:
                chapter, section = get_heading_at_position(headings, start)
                chunks.append({
                    "text": chunk_text,
                    "chapter": chapter,
                    "section": section
                })
            break
        
        # Find a clean breaking point to avoid splitting words/sentences
        break_point = end
        for separator in ['\n\n', '\n', '. ', ' ']:
            pos = text.rfind(separator, start, end)
            if pos != -1:
                break_point = pos + len(separator)
                break
                
        chunk_text = text[start:break_point].strip()
        if chunk_text:
            chapter, section = get_heading_at_position(headings, start)
            chunks.append({
                "text": chunk_text,
                "chapter": chapter,
                "section": section
            })
            
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

    # First pass: build a heading index per source across all pages
    # This helps track chapters that span multiple pages
    source_texts = {}
    source_page_offsets = {}
    
    for p in pages:
        source = p.get("source", "unknown")
        page_num = p["page"]
        page_text = p["text"]
        
        if source not in source_texts:
            source_texts[source] = ""
            source_page_offsets[source] = []
        
        # Track where each page starts in the concatenated text
        source_page_offsets[source].append({
            "page": page_num,
            "offset": len(source_texts[source])
        })
        source_texts[source] += page_text + "\n\n"
    
    # Extract headings from the full text of each source
    source_headings = {}
    for source, full_text in source_texts.items():
        source_headings[source] = extract_headings_from_text(full_text)
        heading_count = len(source_headings[source])
        if heading_count > 0:
            print(f"Found {heading_count} headings in {source}")
            # Print first few headings for debugging
            for level, title, _ in source_headings[source][:5]:
                print(f"  {'#' * level} {title}")
            if heading_count > 5:
                print(f"  ... and {heading_count - 5} more")

    # Second pass: chunk each page with heading context from the full source
    for p in pages:
        source = p.get("source", "unknown")
        page_num = p["page"]
        page_text = p["text"]

        # Get images for this page/source
        images_for_page = page_to_images.get((source, page_num), [])

        # Find the offset of this page in the concatenated source text
        page_offset = 0
        for po in source_page_offsets.get(source, []):
            if po["page"] == page_num:
                page_offset = po["offset"]
                break

        # Extract headings for just this page's text
        page_headings = extract_headings_from_text(page_text)
        
        # Also get the inherited chapter/section from previous pages
        inherited_chapter, inherited_section = get_heading_at_position(
            source_headings[source], page_offset
        )

        # Chunk text with heading awareness
        chunks = chunk_text_with_metadata(page_text, page_headings)

        # Add metadata
        for c in chunks:
            # Use page-level heading if found, otherwise inherit from previous pages
            chapter = c["chapter"] if c["chapter"] else inherited_chapter
            section = c["section"] if c["section"] else inherited_section
            
            all_chunks.append({
                "id": f"chunk_{chunk_id}",
                "source": source,
                "page": page_num,
                "chapter": chapter,
                "section": section,
                "images": images_for_page,
                "text": c["text"]
            })
            chunk_id += 1

    # Save output
    with open(OUTPUT, "w", encoding="utf-8") as f:
        json.dump(all_chunks, f, indent=2, ensure_ascii=False)

    # --- Generate Table of Contents (TOC) per source PDF ---
    # This gives the AI full structural awareness of the book regardless of vector search results
    toc = {}
    for chunk in all_chunks:
        source = chunk["source"]
        chapter = chunk.get("chapter", "")
        section = chunk.get("section", "")
        page = chunk["page"]
        
        if source not in toc:
            toc[source] = {"chapters": {}}
        
        if chapter and chapter not in toc[source]["chapters"]:
            toc[source]["chapters"][chapter] = {
                "start_page": page,
                "sections": {}
            }
        
        if chapter and section and section not in toc[source]["chapters"].get(chapter, {}).get("sections", {}):
            if chapter in toc[source]["chapters"]:
                toc[source]["chapters"][chapter]["sections"][section] = page
    
    toc_path = "data/toc.json"
    with open(toc_path, "w", encoding="utf-8") as f:
        json.dump(toc, f, indent=2, ensure_ascii=False)
    
    # Print TOC summary
    for source, data in toc.items():
        print(f"\nTOC for {source}:")
        for ch_name, ch_data in data["chapters"].items():
            print(f"  📖 {ch_name} (p.{ch_data['start_page']})")
            for sec_name, sec_page in ch_data["sections"].items():
                print(f"      📄 {sec_name} (p.{sec_page})")

    # Stats
    chapters_found = len(set(c["chapter"] for c in all_chunks if c["chapter"]))
    sections_found = len(set(c["section"] for c in all_chunks if c["section"]))
    print(f"\nChunking complete. Total chunks: {len(all_chunks)} | Chapters: {chapters_found} | Sections: {sections_found} → {OUTPUT}")
    print(f"TOC saved → {toc_path}")


if __name__ == "__main__":
    chunk_pdf()
