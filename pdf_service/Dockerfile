# Use official Python image
FROM python:3.10-slim

# Set working directory
WORKDIR /app

# Install system dependencies (needed for FAISS and other libs)
RUN apt-get update && apt-get install -y \
    libgomp1 \
    tesseract-ocr \
    tesseract-ocr-fra \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
# Install Python dependencies (CPU-only to save space)
RUN pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu
RUN pip install --no-cache-dir sentence-transformers qdrant-client==1.17.1
RUN pip install --no-cache-dir fastapi uvicorn numpy openai python-multipart python-dotenv pymupdf pytesseract pymupdf4llm

# Copy the service code
COPY . /app

# Ensure entrypoint is executable (useful for non-mounted environments)
RUN chmod +x /app/entrypoint.sh

# Expose the port FastAPI runs on
EXPOSE 8000

# Command to run the service
CMD ["/bin/bash", "/app/entrypoint.sh"]
