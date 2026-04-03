# Use official Python image
FROM python:3.10-slim

# Set working directory
WORKDIR /app

# Install system dependencies (needed for FAISS and other libs)
RUN apt-get update && apt-get install -y \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
RUN pip install --no-cache-dir fastapi uvicorn faiss-cpu numpy openai python-multipart

# Copy the service code
COPY . /app

# Expose the port FastAPI runs on
EXPOSE 8000

# Command to run the service
CMD ["uvicorn", "api.server:app", "--host", "0.0.0.0", "--port", "8000"]
