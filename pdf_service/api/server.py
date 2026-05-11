import numpy as np
import json
import os
from pathlib import Path
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from openai import OpenAI
from sentence_transformers import SentenceTransformer, CrossEncoder
import qdrant_client
from qdrant_client import QdrantClient

from dotenv import load_dotenv

# Load .env
env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

# FastAPI App Initialization
app = FastAPI()

# Enable CORS for Laravel integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

BASE_DIR = Path(__file__).resolve().parent.parent

# Serve images
images_dir = BASE_DIR / "data" / "images"
if images_dir.exists():
    app.mount("/images", StaticFiles(directory=str(images_dir)), name="images")

# --- Initialize AI Models (Local & Free) ---
print("Loading Embedding Model (all-MiniLM-L6-v2)...")
embed_model = SentenceTransformer('all-MiniLM-L6-v2')

print("Loading Re-ranker Model (ms-marco-MiniLM-L-6-v2)...")
rerank_model = CrossEncoder('cross-encoder/ms-marco-MiniLM-L-6-v2')

# --- Initialize Qdrant Client ---
QDRANT_HOST = os.getenv("QDRANT_HOST", "qdrant")
vector_db = QdrantClient(host=QDRANT_HOST, port=6333)
COLLECTION_NAME = "pdf_chunks"


# AI Configuration for Chat (OpenAI/OpenRouter)
AI_API_BASE_URL = os.getenv("AI_API_BASE_URL", None)
AI_CHAT_MODEL = os.getenv("AI_CHAT_MODEL", "gpt-4o-mini")
AI_IMAGE_BASE_URL = os.getenv("AI_IMAGE_BASE_URL", "http://127.0.0.1:8000")

_client = None
def get_ai_client():
    global _client
    if _client is None:
        if AI_API_BASE_URL:
            _client = OpenAI(base_url=AI_API_BASE_URL)
        else:
            _client = OpenAI()
    return _client

class QuestionRequest(BaseModel):
    question: str
    history: list = []

@app.post("/ask")
def ask_post(request: QuestionRequest):
    return process_question(request.question, request.history)

def process_question(query: str, history: list = []):
    try:
        # --- STEP 1: Embed Query locally ---
        query_vector = embed_model.encode(query).tolist()

        # --- STEP 2: Initial Search in Qdrant (Top 20) ---
        search_result = vector_db.search(
            collection_name=COLLECTION_NAME,
            query_vector=query_vector,
            limit=20,
            with_payload=True
        )


        if not search_result:
            return {"answer": "No relevant information found.", "images": [], "reference_pages": []}

        # --- STEP 3: Re-ranking (Two-Stage Retrieval) ---
        print(f"[INFO] Re-ranking {len(search_result)} candidates...")
        
        # Prepare pairs for re-ranking: [ [query, chunk1], [query, chunk2], ... ]
        pairs = [[query, res.payload["text"]] for res in search_result]
        scores = rerank_model.predict(pairs)

        # Sort results by re-ranker score
        for i, score in enumerate(scores):
            search_result[i].score = score # Overwrite vector score with re-ranker score
        
        search_result.sort(key=lambda x: x.score, reverse=True)
        
        # Take the top 5 best chunks after re-ranking
        top_chunks = search_result[:5]

        # --- STEP 4: Build Context & Metadata ---
        context = ""
        collected_images = []
        reference_pages = set()

        for res in top_chunks:
            chunk = res.payload
            context += chunk["text"] + "\n\n"
            for img in chunk.get("images", []):
                base_url = AI_IMAGE_BASE_URL.rstrip("/")
                collected_images.append(f"{base_url}/images/{img}")
            reference_pages.add(chunk["page"])

        # --- STEP 5: Chat Completion (LLM) ---
        system_content = f"Answer the user based on this context:\n\n{context}\n\n[ANSWER]\n[SUGGESTIONS]"
        messages = [{"role": "system", "content": system_content}]
        messages.extend(history)
        messages.append({"role": "user", "content": query})

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=messages
        )

        return {
            "answer": result.choices[0].message.content,
            "images": list(set(collected_images)), # Remove duplicates
            "reference_pages": sorted(list(reference_pages))
        }

    except Exception as e:
        import traceback
        debug_info = {
            "error": str(e),
            "type_of_client": str(type(vector_db)),
            "has_search": hasattr(vector_db, "search"),
            "attributes": [attr for attr in dir(vector_db) if not attr.startswith("_")][:20]
        }
        print(f"[ERROR] {debug_info}")
        return {
            "answer": f"Deep Debug Error: {str(e)}",
            "debug": debug_info,
            "images": [],
            "reference_pages": []
        }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
