#!/bin/bash

# Start the Python PDF Q&A Microservice
# This script starts the FastAPI server for the PDF question-answering service

echo "Starting PDF Q&A Microservice..."
echo "================================"

# Navigate to the pdf_service directory
cd "$(dirname "$0")"

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "Virtual environment not found. Creating one..."
    python3 -m venv venv
fi

# Activate virtual environment
source venv/bin/activate

# Install dependencies if needed
if [ ! -f "venv/.dependencies_installed" ]; then
    echo "Installing dependencies..."
    pip install fastapi uvicorn faiss-cpu numpy openai python-multipart pymupdf4llm pytesseract pymupdf
    touch venv/.dependencies_installed
fi

# Check if OPENAI_API_KEY is set
if [ -z "$OPENAI_API_KEY" ]; then
    echo "WARNING: OPENAI_API_KEY environment variable is not set!"
    echo "If you are using OpenAI or DeepSeek, please set it in your .env file."
    echo ""
fi

# Start the FastAPI server
echo "Starting server on http://127.0.0.1:8000"
echo "API endpoint: http://127.0.0.1:8000/ask"
echo "Press CTRL+C to stop the server"
echo ""

cd api
uvicorn server:app --host 127.0.0.1 --port 8000 --reload
