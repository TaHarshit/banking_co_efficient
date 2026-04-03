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

client = OpenAI()

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


@app.get("/ask")
def ask(query: str):
    return process_question(query)


@app.post("/ask")
def ask_post(request: QuestionRequest):
    return process_question(request.question)


def process_question(query: str):
    try:
        print(f"[INFO] Processing question: {query[:50]}...")
        
        # --- STEP 1: Embed the query ---
        print("[INFO] Step 1: Creating embeddings...")
        q_embed = client.embeddings.create(
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

        # --- STEP 4: Build prompt ---
        print("[INFO] Step 4: Building prompt...")
        prompt = f"""
        Answer the user's question using ONLY this context.
        If answer is not found, reply "Not found in document".

        Context:
        {context}

        Question: {query}
        """

        # --- STEP 5: Get model response ---
        print("[INFO] Step 5: Calling OpenAI API...")
        result = client.chat.completions.create(
            model="gpt-4.1-mini",
            messages=[{"role": "user", "content": prompt}],
            timeout=60
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

