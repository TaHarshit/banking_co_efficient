# PDF Q&A Service Integration

This microservice provides PDF-based question answering capabilities for the Banking Co-efficient application.

## Architecture

```
Laravel API (banking_co_efficient)
    ↓ HTTP POST Request
Python FastAPI Service (Port 8000)
    ↓ Processes Question
    ↓ Searches PDF Data (FAISS)
    ↓ Generates Answer (OpenAI)
    ↑ Returns Answer + Images + References
Laravel API Response
```

## Setup Instructions

### 1. Prerequisites

- Python 3.8 or higher
- OpenAI API Key
- All PDF data files (already included in this folder)

### 2. Set OpenAI API Key

```bash
export OPENAI_API_KEY="your-openai-api-key-here"
```

Or add it to your shell profile (~/.zshrc or ~/.bashrc):
```bash
echo 'export OPENAI_API_KEY="your-api-key"' >> ~/.zshrc
source ~/.zshrc
```

### 3. Start the Python Service

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/banking_co_efficient/pdf_service
chmod +x start_service.sh
./start_service.sh
```

The service will start on http://127.0.0.1:8000

### 4. Verify Service is Running

Visit http://127.0.0.1:8000/docs in your browser to see the FastAPI documentation.

## API Endpoints

### Laravel API Endpoints

#### POST /api/pdf/ask
Ask a question and get an answer from the PDF.

**Request:**
```json
{
  "question": "What is the account balance?"
}
```

**Response:**
```json
{
  "success": true,
  "answer": "The account balance is $1,234.56",
  "images": ["http://127.0.0.1:8000/images/page_1_img_1.png"],
  "reference_pages": [1, 2],
  "message": "Answer retrieved successfully"
}
```

#### GET /api/pdf/status
Check if the PDF service is running.

**Response:**
```json
{
  "success": true,
  "message": "PDF service is running",
  "service_url": "http://127.0.0.1:8000"
}
```

### Python Service Endpoints

#### POST /ask
Direct endpoint for the Python service.

#### GET /ask?query=your+question
Alternative GET endpoint for testing.

## Environment Variables

Add to your `.env` file:

```env
# PDF Q&A Service Configuration
PDF_SERVICE_URL=http://127.0.0.1:8000/ask
```

## Testing

### Using cURL

```bash
# Test Laravel API
curl -X POST http://localhost/banking_co_efficient/api/pdf/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "What is the account number?"}'

# Check service status
curl http://localhost/banking_co_efficient/api/pdf/status
```

### Using Postman

1. Import the API endpoint
2. Set method to POST
3. URL: `http://localhost/banking_co_efficient/api/pdf/ask`
4. Body (JSON):
```json
{
  "question": "Your question here"
}
```

## File Structure

```
pdf_service/
├── api/
│   └── server.py              # FastAPI application
├── data/
│   ├── chunks.json            # PDF chunks
│   ├── extracted.json         # Extracted text
│   ├── image_map.json         # Image mapping
│   ├── input_pdf.pdf          # Source PDF
│   └── images/                # Extracted images
├── embeddings/
│   ├── vectors.npy            # Vector embeddings
│   └── meta.json              # Metadata
├── vectorstore/
│   └── index.faiss            # FAISS index
├── scripts/
│   ├── chunk_pdf.py           # Chunking script
│   ├── embed_chunks.py        # Embedding script
│   ├── extract_images.py      # Image extraction
│   └── extract_pdf.py         # PDF extraction
├── start_service.sh           # Startup script
└── README.md                  # This file
```

## Troubleshooting

### Service won't start
- Check if port 8000 is already in use: `lsof -i :8000`
- Ensure Python 3.8+ is installed: `python3 --version`
- Check if all data files exist

### Connection errors from Laravel
- Verify Python service is running
- Check the PDF_SERVICE_URL in .env
- Ensure no firewall is blocking port 8000

### No answer returned
- Verify OpenAI API key is set
- Check Python service logs for errors
- Ensure FAISS index and embeddings exist

## Production Deployment

For production deployment:

1. **Use a process manager** (e.g., systemd, supervisor)
2. **Update CORS settings** in server.py to allow only your domain
3. **Use environment variables** for all configuration
4. **Add proper logging** and monitoring
5. **Consider using Gunicorn** with multiple workers
6. **Deploy separately** (Docker, cloud service)

## Updating PDF Content

To update the PDF content:

1. Replace `data/input_pdf.pdf` with your new PDF
2. Run the extraction and embedding scripts:
```bash
cd scripts
python3 extract_pdf.py
python3 chunk_pdf.py
python3 embed_chunks.py
```
3. Restart the service

## Support

For issues, check:
- Laravel logs: `storage/logs/laravel.log`
- Python service console output
- FastAPI docs: http://127.0.0.1:8000/docs
