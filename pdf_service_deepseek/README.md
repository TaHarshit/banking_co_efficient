# PDF Q&A Service (DeepSeek via OpenRouter)

Uses **DeepSeek** (via OpenRouter) for chat and **sentence-transformers** (local, free) for embeddings.

## Setup

### 1. Set your OpenRouter API key

Edit `.env` and add your key:

```
OPENROUTER_API_KEY=sk-or-v1-your-key-here
```

Get a key at https://openrouter.ai/keys

### 2. Process a new PDF (first time only)

```bash
cd pdf_service_deepseek/scripts
..\venv\Scripts\python.exe process_pdf.py "..\data\YourFile.pdf"
```

### 3. Start the server

```bash
cd pdf_service_deepseek/api
..\venv\Scripts\python.exe -m uvicorn server:app --host 127.0.0.1 --port 8000 --reload
```

### 4. Test it

Open http://127.0.0.1:8000/docs in your browser
