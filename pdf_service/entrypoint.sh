#!/bin/bash
set -e

# Run data pipeline if Qdrant chunks haven't been generated yet
# We check if chunks.json exists as a proxy to know if extraction has run.
# If they want to force a rebuild, they can just delete data/chunks.json.

if [ ! -f "/app/data/chunks.json" ]; then
    echo "========================================================"
    echo "First time setup detected: Running PDF data pipeline..."
    echo "========================================================"
    
    cd /app
    echo "Step 1: Extracting PDFs..."
    python3 scripts/extract_pdf.py
    
    echo "Step 1.5: Extracting Images..."
    python3 scripts/extract_images.py
    
    echo "Step 2: Chunking Text..."
    python3 scripts/chunk_pdf.py
    
    echo "Step 3: Embedding and Uploading to Qdrant..."
    python3 scripts/embed_chunks.py
    
    echo "========================================================"
    echo "Pipeline complete. Starting server..."
    echo "========================================================"
    cd /app
else
    echo "PDF data pipeline already run (chunks.json found). Skipping..."
fi

# Start the FastAPI server
exec uvicorn api.server:app --host 0.0.0.0 --port 8000
