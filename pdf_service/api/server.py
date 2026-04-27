import faiss
import numpy as np
import json
import os
from pathlib import Path
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from openai import OpenAI

# Try to load .env file if python-dotenv is available
try:
    from dotenv import load_dotenv
    # Load from the project root .env or pdf_service .env
    env_path = Path(__file__).resolve().parent.parent / ".env"
    if env_path.exists():
        load_dotenv(env_path)
    else:
        # Try the Laravel project root .env
        laravel_env = Path(__file__).resolve().parent.parent.parent / ".env"
        if laravel_env.exists():
            load_dotenv(laravel_env)
except ImportError:
    pass

# FastAPI App Initialization
app = FastAPI()

# Enable CORS for Laravel integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify your Laravel domain
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Get the project root directory (parent of api folder)
BASE_DIR = Path(__file__).resolve().parent.parent

# Serve images from /data/images
images_dir = BASE_DIR / "data" / "images"
if images_dir.exists():
    app.mount("/images", StaticFiles(directory=str(images_dir)), name="images")

# Load FAISS & metadata
index = faiss.read_index(str(BASE_DIR / "vectorstore" / "index.faiss"))
vectors = np.load(str(BASE_DIR / "embeddings" / "vectors.npy"))

with open(str(BASE_DIR / "embeddings" / "meta.json")) as f:
    meta = json.load(f)

# AI Configuration from Environment
AI_API_BASE_URL = os.getenv("AI_API_BASE_URL", None)
AI_CHAT_MODEL = os.getenv("AI_CHAT_MODEL", "gpt-4o-mini")
AI_EMBEDDING_MODEL = os.getenv("AI_EMBEDDING_MODEL", "text-embedding-3-small")
AI_IMAGE_BASE_URL = os.getenv("AI_IMAGE_BASE_URL", "http://127.0.0.1:8000")

# Optional headers for OpenRouter
AI_HTTP_REFERER = os.getenv("AI_HTTP_REFERER", "")
AI_SITE_TITLE = os.getenv("AI_SITE_TITLE", "")

print(f"[CONFIG] AI Provider: {'Custom (' + AI_API_BASE_URL + ')' if AI_API_BASE_URL else 'OpenAI Default'}")
print(f"[CONFIG] Chat Model: {AI_CHAT_MODEL}")
print(f"[CONFIG] Embedding Model: {AI_EMBEDDING_MODEL}")
print(f"[CONFIG] Image Base URL: {AI_IMAGE_BASE_URL}")

# Lazy-initialize AI client
_client = None

def get_ai_client():
    global _client
    if _client is None:
        # Build extra headers for OpenRouter
        extra_headers = {}
        if AI_HTTP_REFERER:
            extra_headers["HTTP-Referer"] = AI_HTTP_REFERER
        if AI_SITE_TITLE:
            extra_headers["X-Title"] = AI_SITE_TITLE
            
        # Initialize client
        if AI_API_BASE_URL:
            _client = OpenAI(
                base_url=AI_API_BASE_URL,
                default_headers=extra_headers if extra_headers else None
            )
        else:
            _client = OpenAI()
    return _client


class QuestionRequest(BaseModel):
    question: str
    history: list = []


@app.get("/ask")
def ask(query: str):
    return process_question(query)


@app.post("/ask")
def ask_post(request: QuestionRequest):
    return process_question(request.question, request.history)


def process_question(query: str, history: list = []):
    try:
        print(f"[INFO] Processing question: {query[:50]}...")
        
        # --- STEP 1: Embed the query ---
        print(f"[INFO] Step 1: Creating embeddings using {AI_EMBEDDING_MODEL}...")
        q_embed = get_ai_client().embeddings.create(
            model=AI_EMBEDDING_MODEL,
            input=query,
            timeout=15
        ).data[0].embedding

        q_embed = np.array(q_embed).astype("float32").reshape(1, -1)

        # --- STEP 2: Search FAISS ---
        print("[INFO] Step 2: Searching FAISS index...")
        distances, ids = index.search(q_embed, 5)

        # --- STEP 3: Build context + collect images + pages ---
        print("[INFO] Step 3: Building context...")
        context = ""
        collected_images = []
        reference_pages = set()

        for i in ids[0]:
            chunk = meta[i]
            context += chunk["text"] + "\n\n"

            # collect images
            for img in chunk.get("images", []):
                # Ensure we don't have double slashes if AI_IMAGE_BASE_URL ends with one
                base_url = AI_IMAGE_BASE_URL.rstrip("/")
                collected_images.append(f"{base_url}/images/{img}")

            # collect pages
            reference_pages.add(chunk["page"])

        # --- STEP 4: Build prompt & messages ---
        print("[INFO] Step 4: Building messages...")
        
        # System instructions with context
        system_content = f"""You are a helpful AI assistant answering questions about the provided document.

Context:
{context}

INSTRUCTIONS:
1. For greetings or conversational interactions, respond politely.
2. For factual questions about the document, answer using ONLY the context provided above.
3. If the answer is not found in the context, reply "I cannot find the answer to that in the document.".
4. ALWAYS provide exactly 3 short, relevant follow-up questions the user might ask next (even for greetings).
5. MANDATORY FORMAT: You MUST wrap your response in [ANSWER] and [SUGGESTIONS] tags.

Example Output:
[ANSWER]
The document mentions that the interest rate is 5%.
[SUGGESTIONS]
- What are the eligibility criteria?
- How do I apply for this loan?
- Are there any processing fees?"""

        messages = [{"role": "system", "content": system_content}]
        
        # Add history
        for msg in history:
            if isinstance(msg, dict) and "role" in msg and "content" in msg:
                messages.append(msg)
            
        # Add current query
        messages.append({"role": "user", "content": query})

        # --- STEP 5: Get model response ---
        print(f"[INFO] Step 5: Calling Chat API using {AI_CHAT_MODEL}...")
        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=messages,
            timeout=60
        )

        full_response = result.choices[0].message.content
        
        # Parse Answer and Suggestions
        answer_text = full_response
        suggestions = []
        
        if "[SUGGESTIONS]" in full_response:
            parts = full_response.split("[SUGGESTIONS]")
            answer_text = parts[0].replace("[ANSWER]", "").strip()
            suggestion_text = parts[1].strip()
            
            # More robust parsing for suggestions (handles -, *, 1., etc.)
            import re
            suggestions = []
            for line in suggestion_text.split("\n"):
                # Remove common bullet points and whitespace
                clean_line = re.sub(r'^(\s*[-*•\d+.]\s*)+', '', line).strip()
                if clean_line:
                    suggestions.append(clean_line)
        elif "[ANSWER]" in full_response:
            answer_text = full_response.replace("[ANSWER]", "").strip()

        print("[INFO] Step 6: Returning response")

        # --- STEP 6: Return final API response ---
        return {
            "answer": answer_text,
            "suggestions": suggestions[:3],
            "images": collected_images,
            "reference_pages": sorted(list(reference_pages))
        }
    
    except Exception as e:
        print(f"[ERROR] Error processing question: {str(e)}")
        return {
            "answer": f"Error processing your question: {str(e)}",
            "images": [],
            "reference_pages": []
        }

