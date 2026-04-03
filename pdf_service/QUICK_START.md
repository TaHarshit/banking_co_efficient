# PDF Q&A Service - Quick Start Guide

## 🚀 Start the Service (3 Steps)

### Step 1: Set OpenAI API Key
```bash
export OPENAI_API_KEY="sk-your-api-key-here"
```

### Step 2: Start the Python Service
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/banking_co_efficient/pdf_service
./start_service.sh
```

### Step 3: Test It
```bash
curl -X POST http://localhost/banking_co_efficient/api/pdf/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "What is in the PDF?"}'
```

## ✅ Verify Service is Running

Open in browser: http://127.0.0.1:8000/docs

## 📝 API Endpoints

### Ask a Question
```bash
POST http://localhost/banking_co_efficient/api/pdf/ask

Body:
{
  "question": "Your question here"
}
```

### Check Service Status
```bash
GET http://localhost/banking_co_efficient/api/pdf/status
```

## 🛠️ Troubleshooting

**Service won't start?**
```bash
# Check if port 8000 is in use
lsof -i :8000

# Kill process if needed
kill -9 <PID>
```

**Connection errors?**
1. Make sure Python service is running (http://127.0.0.1:8000)
2. Check .env has: `PDF_SERVICE_URL=http://127.0.0.1:8000/ask`
3. Verify OPENAI_API_KEY is set: `echo $OPENAI_API_KEY`

## 📚 Full Documentation

See `PDF_SERVICE_INTEGRATION_GUIDE.md` in the project root for complete documentation.
