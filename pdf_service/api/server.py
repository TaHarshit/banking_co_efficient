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
from qdrant_client import QdrantClient
from qdrant_client.models import PointStruct, VectorParams, Distance
import uuid
import time
from contextlib import asynccontextmanager

import re
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
print("Loading Embedding Model (all-MiniLM-L6-v2)...", flush=True)
embed_model = SentenceTransformer('all-MiniLM-L6-v2')

print("Loading Re-ranker Model (ms-marco-MiniLM-L-6-v2)...", flush=True)
rerank_model = CrossEncoder('cross-encoder/ms-marco-MiniLM-L-6-v2')

# --- Initialize Qdrant Client ---
def get_qdrant_client():
    hosts = [os.getenv("QDRANT_HOST", "qdrant"), "banking-qdrant"]
    for host in hosts:
        try:
            c = QdrantClient(host=host, port=6333, timeout=5)
            c.get_collections()
            return c
        except:
            continue
    # Fallback to default
    return QdrantClient(host="qdrant", port=6333)

vector_db = get_qdrant_client()
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
            _client = OpenAI(
                base_url=AI_API_BASE_URL,
                timeout=60.0,  # Increased timeout for larger responses
                max_retries=3
            )
        else:
            _client = OpenAI(
                timeout=60.0,  # Increased timeout for larger responses
                max_retries=3
            )
    return _client

class QuestionRequest(BaseModel):
    question: str
    history: list = []

class CaseAnalysisRequest(BaseModel):
    client_alias: str
    context_overview: str = ""
    case_details: dict
    user_profile: str = "" # New field for behavioral data
    history: list = []

class ActionPlanRequest(BaseModel):
    case_data: dict
    analysis_data: dict
    user_profile: str = "" # New field for behavioral data
    history: list = []

# --- Case Memory Storage ---
CASES_COLLECTION = "past_cases"
ANALYZED_COLLECTION = "analyzed_cases"

def ensure_collections():
    for coll in [CASES_COLLECTION, ANALYZED_COLLECTION]:
        try:
            vector_db.get_collection(coll)
        except:
            print(f"Creating collection: {coll}...", flush=True)
            vector_db.create_collection(
                collection_name=coll,
                vectors_config=VectorParams(size=384, distance=Distance.COSINE)
            )

# Initialize collections
ensure_collections()

def sanitize_json_response(content: str) -> str:
    """
    Sanitize AI response to fix common JSON issues:
    - Replace curly quotes with straight quotes
    - Remove control characters
    - Fix other common JSON formatting issues
    """
    # Replace curly quotes with straight quotes
    content = content.replace('"', '"')  # Left double quote
    content = content.replace('"', '"')  # Right double quote
    content = content.replace(''', "'")  # Left single quote
    content = content.replace(''', "'")  # Right single quote
    
    # Remove control characters except newlines and tabs
    content = re.sub(r'[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]', '', content)
    
    # Fix common issues with trailing commas in arrays/objects
    content = re.sub(r',\s*}', '}', content)
    content = re.sub(r',\s*]', ']', content)
    
    return content

@app.post("/analyze-case")
def analyze_case(request: CaseAnalysisRequest):
    start_time = time.time()
    try:
        details = request.case_details
        combined_input = f"{request.client_alias} {request.context_overview} {json.dumps(details)}"
        
        # 1. Embedding
        t0 = time.time()
        query_vector = embed_model.encode(combined_input).tolist()
        t_embed = time.time() - t0
        print(f"[PERF] /analyze-case - Embedding finished in {t_embed:.3f}s", flush=True)

        # 2. Search Past Memory
        t0 = time.time()
        past_cases = vector_db.query_points(collection_name=CASES_COLLECTION, query=query_vector, limit=1).points
        past_analysis = vector_db.query_points(collection_name=ANALYZED_COLLECTION, query=query_vector, limit=1).points
        t_search_memory = time.time() - t0
        print(f"[PERF] /analyze-case - Memory Search finished in {t_search_memory:.3f}s", flush=True)
        
        past_context = "PAST CASES:\n"
        for res in past_cases:
            past_context += f"- {res.payload.get('objective', '')}\n"
        
        past_context += "\nPAST AI ADVICE:\n"
        for res in past_analysis:
            past_context += f"- {res.payload.get('recommendations', '')}\n"

        # 3. Search PDF Book
        t0 = time.time()
        book_result = vector_db.query_points(collection_name=COLLECTION_NAME, query=query_vector, limit=5).points
        context = "\n".join([res.payload["text"] for res in book_result])
        t_search_book = time.time() - t0
        print(f"[PERF] /analyze-case - Book Search finished in {t_search_book:.3f}s", flush=True)

        # 4. Generate Analysis
        t0 = time.time()
        # ... system prompt remains same ...
        system_prompt = f"""
        You are a World-Class Negotiation Expert. 
        Analyze this case for {request.client_alias}.
        
        [USER BEHAVIORAL PROFILE]
        {request.user_profile}

        [BOOK KNOWLEDGE]
        {context}
        
        [HISTORICAL MEMORY]
        {past_context}

        [CURRENT CASE]
        Overview: {request.context_overview}
        Details: {json.dumps(details)}

        [GOAL]
        Provide recommendations that align with the user's specific negotiation style and challenges described in their profile.
        {{
            "ai_recommendations": ["..."],
            "suggested_readings": [{{ "chapter": "...", "title": "...", "time": "..." }}],
            "ai_challenges": ["..."]
        }}
        """

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=[{"role": "system", "content": system_prompt}],
            response_format={ "type": "json_object" },
            max_tokens=16384
        )

        content = result.choices[0].message.content
        if content is None:
            raise ValueError("AI response content is empty (None).")

        # Log the raw AI response for debugging
        print(f"[DEBUG] /analyze-case - Raw AI response length: {len(content)} chars", flush=True)
        print(f"[DEBUG] /analyze-case - First 500 chars: {content[:500]}", flush=True)
        
        # Sanitize the AI response to fix common JSON issues
        sanitized_content = sanitize_json_response(content)
        print(f"[DEBUG] /analyze-case - Sanitized response length: {len(sanitized_content)} chars", flush=True)
        
        # Try to parse JSON with better error handling
        try:
            analysis = json.loads(sanitized_content)
        except json.JSONDecodeError as e:
            print(f"[ERROR] /analyze-case - JSON parsing failed: {str(e)}", flush=True)
            print(f"[ERROR] /analyze-case - Problematic content around position {e.pos}: {sanitized_content[max(0, e.pos-100):e.pos+100]}", flush=True)
            # Return error response instead of crashing
            return {
                "error": f"AI returned invalid JSON: {str(e)}",
                "raw_content": sanitized_content[:1000]  # Return first 1000 chars for debugging
            }
        t_ai = time.time() - t0
        print(f"[PERF] /analyze-case - AI Generation finished in {t_ai:.3f}s", flush=True)

        # 5. Store Separately
        t0 = time.time()
        # ... store logic remains same ...
        case_id = str(uuid.uuid4())
        
        # Safely get recommendations, default to empty string if missing
        recommendations = analysis.get("ai_recommendations", [])
        if isinstance(recommendations, list):
            recommendations_str = ", ".join(recommendations)
        else:
            recommendations_str = str(recommendations)

        vector_db.upsert(
            collection_name=CASES_COLLECTION,
            points=[PointStruct(id=case_id, vector=query_vector, payload={
                "alias": request.client_alias, 
                "objective": details.get("objective", "")
            })]
        )
        vector_db.upsert(
            collection_name=ANALYZED_COLLECTION,
            points=[PointStruct(id=case_id, vector=query_vector, payload={
                "recommendations": recommendations_str
            })]
        )
        t_store = time.time() - t0
        print(f"[PERF] /analyze-case - Storage finished in {t_store:.3f}s", flush=True)

        total_time = time.time() - start_time
        print(f"[PERF] /analyze-case - SUCCESS. Total time: {total_time:.3f}s", flush=True)

        return analysis

    except Exception as e:
        print(f"[ERROR] /analyze-case: {str(e)}", flush=True)
        return {"error": str(e)}

@app.post("/generate-plan")
def generate_plan(request: ActionPlanRequest):
    start_time = time.time()
    try:
        combined_input = json.dumps(request.case_data) + json.dumps(request.analysis_data)
        
        # 1. Embedding
        t0 = time.time()
        query_vector = embed_model.encode(combined_input).tolist()
        t_embed = time.time() - t0
        print(f"[PERF] /generate-plan - Embedding finished in {t_embed:.3f}s", flush=True)

        # 2. Search PDF
        t0 = time.time()
        book_result = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=3,  # Reduced from 5 to leave more tokens for response
            with_payload=True
        ).points
        context = ""
        for res in book_result:
            context += res.payload["text"] + "\n\n"
        t_search = time.time() - t0
        print(f"[PERF] /generate-plan - PDF Search finished in {t_search:.3f}s", flush=True)

        # 3. AI Generation
        t0 = time.time()
        # ... prompt ...
        system_prompt = f"""
        Generate a detailed "Negotiation Action Plan" based on this case and the book techniques.
        
        [USER BEHAVIORAL PROFILE]
        {request.user_profile}

        [BOOK TECHNIQUES]
        {context}

        [CASE DATA]
        {json.dumps(request.case_data)}

        [ANALYSIS DATA]
        {json.dumps(request.analysis_data)}

        [GOAL]
        Personalize the plan phases and strategic recommendations to complement the user's strengths and mitigate their weaknesses as identified in their profile.

        [IMPORTANT - OUTPUT FORMAT]
        You MUST respond with a complete valid JSON object. Do NOT truncate or cut off your response. Include ALL fields listed below:
        {{
            "executive_summary": "A concise 2-3 sentence summary",
            "meeting_objectives": ["obj 1", "obj 2", "obj 3"],
            "action_plan": {{
                "phase_1_before": {{ "title": "...", "steps": ["step 1", "step 2", "step 3"], "readings": ["Chapter X: Name"] }},
                "phase_2_during": {{ "title": "...", "steps": ["step 1", "step 2", "step 3"] }},
                "phase_3_after": {{ "title": "...", "steps": ["step 1", "step 2", "step 3"] }}
            }},
            "strategic_recommendations": ["rec 1", "rec 2", "rec 3"],
            "critical_success_factors": ["factor 1", "factor 2"],
            "plan_b": ["alternative 1", "alternative 2"]
        }}

        CRITICAL: You must complete ALL fields including phase_2_during, phase_3_after, executive_summary, meeting_objectives, strategic_recommendations, critical_success_factors, and plan_b. Do NOT stop mid-response.
        """

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=[{"role": "system", "content": system_prompt}],
            response_format={ "type": "json_object" },
            max_tokens=16384
        )

        content = result.choices[0].message.content
        if content is None:
            raise ValueError("AI response content is empty (None).")

        # Log the raw AI response for debugging
        print(f"[DEBUG] /generate-plan - Raw AI response length: {len(content)} chars", flush=True)
        print(f"[DEBUG] /generate-plan - First 500 chars: {content[:500]}", flush=True)
        
        # Sanitize the AI response to fix common JSON issues
        sanitized_content = sanitize_json_response(content)
        print(f"[DEBUG] /generate-plan - Sanitized response length: {len(sanitized_content)} chars", flush=True)
        
        t_ai = time.time() - t0
        print(f"[PERF] /generate-plan - AI Generation finished in {t_ai:.3f}s", flush=True)

        # Try to parse JSON with better error handling
        try:
            parsed_content = json.loads(sanitized_content)
        except json.JSONDecodeError as e:
            print(f"[ERROR] /generate-plan - JSON parsing failed: {str(e)}", flush=True)
            print(f"[ERROR] /generate-plan - Problematic content around position {e.pos}: {sanitized_content[max(0, e.pos-100):e.pos+100]}", flush=True)
            # Return error response instead of crashing
            return {
                "error": f"AI returned invalid JSON: {str(e)}",
                "raw_content": sanitized_content[:1000]  # Return first 1000 chars for debugging
            }

        total_time = time.time() - start_time
        print(f"[PERF] /generate-plan - SUCCESS. Total time: {total_time:.3f}s", flush=True)

        return parsed_content

    except Exception as e:
        print(f"[ERROR] /generate-plan: {str(e)}", flush=True)
        return {"error": str(e)}

@app.post("/ask")
def ask_post(request: QuestionRequest):
    return process_question(request.question, request.history)

def process_question(query: str, history: list = []):
    start_time = time.time()
    try:
        # 1. Embedding
        t0 = time.time()
        query_vector = embed_model.encode(query).tolist()
        t_embed = time.time() - t0
        print(f"[PERF] /ask - Embedding finished in {t_embed:.3f}s", flush=True)

        # 2. Qdrant Search
        t0 = time.time()
        response = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=20,
            with_payload=True
        )
        search_result = response.points
        t_search = time.time() - t0
        print(f"[PERF] /ask - Qdrant Search finished in {t_search:.3f}s", flush=True)

        if not search_result:
            return {"answer": "No relevant information found.", "images": [], "reference_pages": []}

        # 3. Re-ranking
        t0 = time.time()
        print(f"[INFO] Re-ranking {len(search_result)} candidates...", flush=True)
        
        # Prepare pairs for re-ranking: [ [query, chunk1], [query, chunk2], ... ]
        pairs = [[query, res.payload["text"]] for res in search_result]
        scores = rerank_model.predict(pairs)

        # Sort results by re-ranker score
        for i, score in enumerate(scores):
            search_result[i].score = score # Overwrite vector score with re-ranker score
        
        search_result.sort(key=lambda x: x.score, reverse=True)
        
        # Take the top 5 best chunks after re-ranking
        top_chunks = search_result[:5]
        t_rerank = time.time() - t0
        print(f"[PERF] /ask - Re-ranking finished in {t_rerank:.3f}s", flush=True)

        # 4. Context Building
        context = ""
        collected_images = []
        reference_pages = set()

        for res in top_chunks:
            chunk = res.payload
            context += chunk["text"] + "\n\n"
            for img in (chunk.get("images") or []):
                base_url = (AI_IMAGE_BASE_URL or "http://127.0.0.1:8000").rstrip("/")
                collected_images.append(f"{base_url}/images/{img}")
            reference_pages.add(chunk["page"])

        # 5. AI Completion
        t0 = time.time()
        system_content = f"""
        You are a helpful AI assistant answering questions about the provided document.
        For greetings or conversational interactions (e.g., "Hi", "Hello", "How are you?"), respond politely and warmly.
        For factual questions about the document, answer using ONLY the context provided below.
        If the answer is not in the context, say "I cannot find the answer to that in the document."

        [IMPORTANT]
        At the end of your response, provide exactly 3 short and sweet follow-up suggestions for the user.
        These suggestions MUST be related to the information found in the context.
        Format them at the bottom like this:
        Suggestions: [Suggestion 1] | [Suggestion 2] | [Suggestion 3]

        Context:
        {context}
        """
        
        messages = [{"role": "system", "content": system_content}]
        if history:
            messages.extend(history)
        messages.append({"role": "user", "content": query})

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=messages
        )
        t_ai = time.time() - t0
        print(f"[PERF] /ask - AI Generation finished in {t_ai:.3f}s", flush=True)

        full_response = result.choices[0].message.content
        
        # Split answer and suggestions
        answer = full_response
        suggestions = []
        if "Suggestions:" in full_response:
            parts = full_response.split("Suggestions:")
            answer = parts[0].strip()
            raw_suggestions = parts[1].strip().split("|")
            suggestions = [s.strip() for s in raw_suggestions if s.strip()]

        total_time = time.time() - start_time
        print(f"[PERF] /ask - SUCCESS. Total time: {total_time:.3f}s", flush=True)

        return {
            "answer": answer,
            "suggestions": suggestions,
            "images": list(set(collected_images)), # Remove duplicates
            "reference_pages": sorted(list(reference_pages))
        }

    except Exception as e:
        print(f"[ERROR] /ask (process_question): {str(e)}", flush=True)
        return {"answer": f"Error: {str(e)}", "images": [], "reference_pages": []}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
