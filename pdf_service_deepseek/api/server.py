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
from sentence_transformers import SentenceTransformer

# Load .env file
try:
    from dotenv import load_dotenv
    env_path = Path(__file__).resolve().parent.parent / ".env"
    if env_path.exists():
        load_dotenv(env_path)
except ImportError:
    pass

# --- Configuration ---
OPENROUTER_API_KEY = os.getenv("OPENROUTER_API_KEY", "")
DEEPSEEK_MODEL = os.getenv("DEEPSEEK_MODEL", "deepseek/deepseek-chat-v3-0324")

# Lazy-initialize OpenRouter client
_client = None

def get_client():
    global _client
    if _client is None:
        _client = OpenAI(
            base_url="https://openrouter.ai/api/v1",
            api_key=OPENROUTER_API_KEY,
            timeout=30.0,
            max_retries=2
        )
    return _client

# --- Load embedding model (local, free) ---
BASE_DIR = Path(__file__).resolve().parent.parent

print("[INFO] Loading local embedding model (all-MiniLM-L6-v2)...")
embed_model = SentenceTransformer("all-MiniLM-L6-v2")
print("[INFO] Embedding model loaded!")

app = FastAPI(title="PDF Q&A Service (DeepSeek via OpenRouter)")

# Enable CORS for Laravel integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

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


@app.post("/reload")
def reload_index():
    global index, vectors, meta
    try:
        print("[INFO] Reloading FAISS index and metadata...")
        index = faiss.read_index(str(BASE_DIR / "vectorstore" / "index.faiss"))
        vectors = np.load(str(BASE_DIR / "embeddings" / "vectors.npy"))
        with open(str(BASE_DIR / "embeddings" / "meta.json")) as f:
            meta = json.load(f)
        print("[INFO] Reload successful.")
        return {"success": True, "message": "Index reloaded successfully"}
    except Exception as e:
        print(f"[ERROR] Failed to reload index: {e}")
        return {"success": False, "message": f"Failed to reload: {str(e)}"}


def process_question(query: str, history: list = None):
    if history is None:
        history = []
    try:
        print(f"[INFO] Processing question: {query[:50]}...")

        # --- STEP 1: Embed the query (local, free) ---
        print("[INFO] Step 1: Creating embeddings (local model)...")
        q_embed = embed_model.encode([query])
        q_embed = np.array(q_embed).astype("float32")

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

            for img in chunk.get("images", []):
                collected_images.append(f"http://127.0.0.1:8000/images/{img}")

            reference_pages.add(chunk["page"])

        # --- STEP 4: Build prompt ---
        print("[INFO] Step 4: Building prompt...")
        system_prompt = f"""
        You are a helpful AI assistant answering questions about the provided document.
        For greetings or conversational interactions (e.g., "Hi", "Hello", "How are you?"), respond politely and warmly as an AI assistant.
        For factual questions about the document, answer using ONLY the context provided below.
        If a user asks a question that is clearly not a greeting and the answer is not found in the context, reply "I cannot find the answer to that in the document."

        Context:
        {context}
        """

        messages = [{"role": "system", "content": system_prompt}]
        
        # Append history if any (expecting [{"role": "user"/"assistant", "content": "..."}])
        for msg in history:
            messages.append({
                "role": msg.get("role", "user"), 
                "content": msg.get("content", "")
            })
            
        # Append current question
        messages.append({"role": "user", "content": query})

        # --- STEP 5: Get DeepSeek response via OpenRouter ---
        print(f"[INFO] Step 5: Calling DeepSeek via OpenRouter ({DEEPSEEK_MODEL})...")
        result = get_client().chat.completions.create(
            model=DEEPSEEK_MODEL,
            messages=messages,
        )

        answer_text = result.choices[0].message.content
        print("[INFO] Step 6: Returning response")

        # --- STEP 6: Return final API response ---
        return {
            "answer": answer_text,
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
