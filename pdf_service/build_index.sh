#!/bin/bash
set -e

echo "========================================================"
echo "Manually starting PDF data pipeline..."
echo "========================================================"

cd /app

# Clear old data if any
rm -rf data/extracted.json data/chunks.json data/images data/image_map.json

echo "Step 1: Extracting PDFs..."
python3 scripts/extract_pdf.py

echo "Step 1.5: Extracting Images..."
python3 scripts/extract_images.py

echo "Step 2: Chunking Text..."
python3 scripts/chunk_pdf.py

echo "Step 3: Embedding and Uploading to Qdrant..."
python3 scripts/embed_chunks.py

echo "========================================================"
echo "Pipeline complete! Your new data is now in Qdrant."
echo "========================================================"
