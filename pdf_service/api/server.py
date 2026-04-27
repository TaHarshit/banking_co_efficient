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

# Lazy-initialize OpenAI client to avoid crash on Windows with --reload
_client = None

def get_openai_client():
    global _client
    if _client is None:
        _client = OpenAI()
    return _client

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
        print("[INFO] Step 1: Creating embeddings...")
        q_embed = get_openai_client().embeddings.create(
            model="text-embedding-3-small",
            input=query,
            timeout=10
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
                collected_images.append(f"http://127.0.0.1:8000/images/{img}")

            # collect pages
            reference_pages.add(chunk["page"])

        # --- STEP 4: Build prompt & messages ---
        print("[INFO] Step 4: Building messages...")
        
        # Build the message list for OpenAI
        messages = []
        
        # System instructions with context
        system_content = f"""You are a helpful AI assistant answering questions about the provided document.
For greetings or conversational interactions, respond politely.
For factual questions about the document, answer using ONLY the context provided below.
If a user asks a question that is clearly not a greeting and the answer is not found in the context, reply "I cannot find the answer to that in the document.".

Context:
{context}

INSTRUCTIONS:
1. Provide a detailed answer based on the context.
2. After your answer, provide exactly 3 short follow-up questions the user might ask next.
3. Format your response exactly like this:
[ANSWER]
(your answer here)
[SUGGESTIONS]
- (suggestion 1)
- (suggestion 2)
- (suggestion 3)"""

        messages.append({"role": "system", "content": system_content})
        
        # Add history
        for msg in history:
            # Ensure each message has the correct format
            if isinstance(msg, dict) and "role" in msg and "content" in msg:
                messages.append(msg)
            
        # Add current query
        messages.append({"role": "user", "content": query})

        # --- STEP 5: Get model response ---
        print("[INFO] Step 5: Calling OpenAI API...")
        result = get_openai_client().chat.completions.create(
            model="gpt-4o-mini",
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
            suggestions = [line.strip("- ").strip() for line in suggestion_text.split("\n") if line.strip("- ").strip()]
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

