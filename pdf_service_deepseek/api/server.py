import faiss
import numpy as np
import json
import os
from pathlib import Path
from fastapi import FastAPI, Header
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
    lang: str | None = None


TRANSLATION_RESPONSE_FORMAT = {
    "type": "json_schema",
    "json_schema": {
        "name": "language_translation",
        "strict": True,
        "schema": {
            "type": "object",
            "properties": {
                "detected_language": {"type": "string"},
                "translated_text": {"type": "string"}
            },
            "required": ["detected_language", "translated_text"],
            "additionalProperties": False
        }
    }
}


def detect_and_translate(text: str) -> dict:
    try:
        system_prompt = (
            "You are a translation helper. Analyze the user's query.\n"
            "1. Detect the language of the query.\n"
            "2. If it is NOT English, translate it to English.\n"
            "3. Return a JSON object with 'detected_language' (e.g., 'French', 'English', etc.) and 'translated_text' (the English translation, or original text if already English)."
        )
        messages = [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": text}
        ]
        result = get_client().chat.completions.create(
            model=DEEPSEEK_MODEL,
            messages=messages,
            temperature=0.0,
            response_format=TRANSLATION_RESPONSE_FORMAT
        )
        content = result.choices[0].message.content
        if content:
            return json.loads(content)
    except Exception as e:
        print(f"[WARN] detect_and_translate failed: {e}", flush=True)
    return {"detected_language": "English", "translated_text": text}


@app.get("/ask")
def ask(query: str, accept_language: str | None = Header(default=None)):
    return process_question(query, target_lang=accept_language)


@app.post("/ask")
def ask_post(request: QuestionRequest, accept_language: str | None = Header(default=None)):
    target_lang = request.lang or accept_language
    return process_question(request.question, request.history, target_lang)


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


def process_question(query: str, history: list = None, target_lang: str | None = None):
    if history is None:
        history = []
    try:
        print(f"[INFO] Processing question: {query[:50]}...")

        # Language Detection & Translation
        translation_info = detect_and_translate(query)
        detected_lang = translation_info.get("detected_language", "English")
        search_query = translation_info.get("translated_text", query)
        
        # Determine output language
        output_lang = None
        if target_lang:
            target_lang_lower = target_lang.lower()
            if target_lang_lower.startswith("fr"):
                output_lang = "French"
            elif target_lang_lower.startswith("en"):
                output_lang = "English"
            else:
                if "french" in target_lang_lower:
                    output_lang = "French"
                elif "english" in target_lang_lower:
                    output_lang = "English"
                else:
                    output_lang = target_lang

        if not output_lang:
            output_lang = detected_lang

        print(f"[INFO] Detected language: {detected_lang}, Output language: {output_lang}, Search query: {search_query}...")

        # --- STEP 1: Embed the query (local, free) ---
        print("[INFO] Step 1: Creating embeddings (local model)...")
        q_embed = embed_model.encode([search_query])
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

            pg = chunk.get("page")
            if pg and str(pg).strip() not in ["1", "0", "Unknown", "None", ""]:
                try:
                    if int(pg) > 1:
                        reference_pages.add(int(pg))
                except (ValueError, TypeError):
                    reference_pages.add(pg)

        # --- STEP 4: Build prompt ---
        print("[INFO] Step 4: Building prompt...")
        system_prompt = f"""
        You are a helpful AI assistant answering questions about the provided document.
        For greetings or conversational interactions (e.g., "Hi", "Hello", "How are you?"), respond politely and warmly as an AI assistant.
        For factual questions about the document, answer using ONLY the context provided below.
        If a user asks a question that is clearly not a greeting and the answer is not found in the context, reply "I cannot find the answer to that in the document."
        INLINE CITATIONS: Only cite specific page numbers if greater than 1. Never cite page 1 or (p.1).

        Context:
        {context}
        """

        if output_lang.lower() != "english":
            system_prompt += f"\n[LANGUAGE] You MUST respond to the user in {output_lang}. If the answer is not in the context, translate 'I cannot find the answer to that in the document.' to {output_lang}."
        else:
            if detected_lang.lower() != "english":
                system_prompt += f"\n[LANGUAGE] Although the user asked in {detected_lang}, you MUST respond to the user in English. If the answer is not in the context, respond with 'I cannot find the answer to that in the document.' in English."

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

        answer_text = result.choices[0].message.content or ""
        print("[INFO] Step 6: Returning response")

        # Clean answer text from fake p.1 / page 1 references
        import re
        answer_text = re.sub(r'\s*\([^)]*?\b(?:p|page)\.?\s*1\b[^)]*?\)', '', answer_text, flags=re.IGNORECASE)
        answer_text = re.sub(r'\s*\[[^\]]*?\b(?:p|page)\.?\s*1\b[^\]]*?\]', '', answer_text, flags=re.IGNORECASE)
        answer_text = re.sub(r',?\s*\b(?:p|page)\.?\s*1\b', '', answer_text, flags=re.IGNORECASE)
        answer_text = re.sub(r'\(\s*\)', '', answer_text)
        answer_text = re.sub(r'\[\s*\]', '', answer_text)
        answer_text = re.sub(r'  +', ' ', answer_text).strip()

        filtered_ref_pages = []
        for p in reference_pages:
            if p is None or p == "" or str(p).strip() in ["1", "0", "Unknown", "None"]:
                continue
            try:
                p_int = int(p)
                if p_int > 1:
                    filtered_ref_pages.append(p_int)
            except (ValueError, TypeError):
                filtered_ref_pages.append(p)

        # --- STEP 6: Return final API response ---
        return {
            "answer": answer_text,
            "images": collected_images,
            "reference_pages": sorted(list(set(filtered_ref_pages)), key=lambda x: (isinstance(x, str), x))
        }

    except Exception as e:
        print(f"[ERROR] Error processing question: {str(e)}")
        return {
            "answer": f"Error processing your question: {str(e)}",
            "images": [],
            "reference_pages": []
        }
