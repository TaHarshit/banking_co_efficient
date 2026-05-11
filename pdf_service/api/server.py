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

class CaseAnalysisRequest(BaseModel):
    business_objective: str
    context: str
    client_profile: str
    decision_ecosystem: str
    history: list = []

class ActionPlanRequest(BaseModel):
    case_data: dict
    analysis_data: dict
    history: list = []

# --- Case Memory Storage ---
CASES_COLLECTION = "past_cases"

def ensure_cases_collection():
    try:
        vector_db.get_collection(CASES_COLLECTION)
    except:
        print(f"Creating collection: {CASES_COLLECTION}...")
        from qdrant_client.models import VectorParams, Distance
        vector_db.create_collection(
            collection_name=CASES_COLLECTION,
            vectors_config=VectorParams(size=384, distance=Distance.COSINE) # size for all-MiniLM-L6-v2
        )

# Initialize collections
ensure_cases_collection()

@app.post("/analyze-case")
def analyze_case(request: CaseAnalysisRequest):
    try:
        combined_input = f"{request.business_objective} {request.context} {request.client_profile} {request.decision_ecosystem}"
        query_vector = embed_model.encode(combined_input).tolist()

        # 1. Search Past Cases (Memory)
        past_cases_result = vector_db.query_points(
            collection_name=CASES_COLLECTION,
            query=query_vector,
            limit=2,
            with_payload=True
        ).points
        
        past_context = ""
        for res in past_cases_result:
            past_context += f"Past Case: {res.payload['objective']}\nStrategy Used: {res.payload['recommendations']}\n\n"

        # 2. Search PDF Book (Knowledge)
        book_result = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=10,
            with_payload=True
        ).points

        context = ""
        for res in book_result:
            context += res.payload["text"] + "\n\n"

        # 3. Generate Analysis
        system_prompt = f"""
        You are a World-Class Negotiation Expert. Analyze the user's case and provide structured guidance.
        
        [CONTEXT FROM BOOK]
        {context}
        
        [PAST CASE MEMORY]
        {past_context}

        [USER CASE]
        Objective: {request.business_objective}
        Context: {request.context}
        Profile: {request.client_profile}
        Ecosystem: {request.decision_ecosystem}

        Output the response in the following JSON format ONLY:
        {{
            "ai_recommendations": [
                "Detailed recommendation 1. Reference: Chapter X - Name",
                "Detailed recommendation 2. Reference: Chapter Y - Name"
            ],
            "suggested_readings": [
                {{ "chapter": "Chapter 4", "title": "Name", "time": "10 min" }},
                {{ "chapter": "Chapter 7", "title": "Name", "time": "12 min" }}
            ],
            "ai_challenges": [
                "Question challenging the user's approach 1",
                "Question challenging the user's approach 2"
            ]
        }}
        """

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=[{"role": "system", "content": system_prompt}],
            response_format={ "type": "json_object" }
        )
        
        analysis = json.loads(result.choices[0].message.content)

        # 4. Store this case in Memory for future
        from qdrant_client.models import PointStruct
        import uuid
        vector_db.upsert(
            collection_name=CASES_COLLECTION,
            points=[
                PointStruct(
                    id=str(uuid.uuid4()),
                    vector=query_vector,
                    payload={
                        "objective": request.business_objective,
                        "recommendations": str(analysis["ai_recommendations"])
                    }
                )
            ]
        )

        return analysis

    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return {"error": str(e)}

@app.post("/generate-plan")
def generate_plan(request: ActionPlanRequest):
    try:
        combined_input = json.dumps(request.case_data) + json.dumps(request.analysis_data)
        query_vector = embed_model.encode(combined_input).tolist()

        # Search PDF for specific planning techniques
        book_result = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=10,
            with_payload=True
        ).points

        context = ""
        for res in book_result:
            context += res.payload["text"] + "\n\n"

        system_prompt = f"""
        Generate a detailed "Negotiation Action Plan" based on this case and the book techniques.
        
        [BOOK TECHNIQUES]
        {context}

        [CASE DATA]
        {json.dumps(request.case_data)}

        [ANALYSIS DATA]
        {json.dumps(request.analysis_data)}

        Output the response in the following JSON format ONLY:
        {{
            "executive_summary": "...",
            "meeting_objectives": ["obj 1", "obj 2"],
            "action_plan": {{
                "phase_1_before": {{ "title": "...", "steps": ["step 1", "step 2"], "readings": ["Chapter X: Name"] }},
                "phase_2_during": {{ "title": "...", "steps": ["step 1", "step 2"] }},
                "phase_3_after": {{ "title": "...", "steps": ["step 1", "step 2"] }}
            }},
            "strategic_recommendations": ["rec 1", "rec 2"],
            "critical_success_factors": ["factor 1", "factor 2"],
            "plan_b": ["alternative 1", "alternative 2"]
        }}
        """

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=[{"role": "system", "content": system_prompt}],
            response_format={ "type": "json_object" }
        )

        return json.loads(result.choices[0].message.content)

    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return {"error": str(e)}

@app.post("/ask")
def ask_post(request: QuestionRequest):
    return process_question(request.question, request.history)

def process_question(query: str, history: list = []):
    try:
        # --- STEP 1: Embed Query locally ---
        query_vector = embed_model.encode(query).tolist()

        # --- STEP 2: Initial Search in Qdrant (Top 20) ---
        # Using query_points (the modern replacement for search in 1.11+)
        response = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=20,
            with_payload=True
        )
        search_result = response.points



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
            for img in (chunk.get("images") or []):
                base_url = (AI_IMAGE_BASE_URL or "http://127.0.0.1:8000").rstrip("/")
                collected_images.append(f"{base_url}/images/{img}")
            reference_pages.add(chunk["page"])

        # --- STEP 5: Chat Completion (LLM) ---
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

        full_response = result.choices[0].message.content
        
        # Split answer and suggestions
        answer = full_response
        suggestions = []
        if "Suggestions:" in full_response:
            parts = full_response.split("Suggestions:")
            answer = parts[0].strip()
            raw_suggestions = parts[1].strip().split("|")
            suggestions = [s.strip() for s in raw_suggestions if s.strip()]

        return {
            "answer": answer,
            "suggestions": suggestions,
            "images": list(set(collected_images)), # Remove duplicates
            "reference_pages": sorted(list(reference_pages))
        }

    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return {"answer": f"Error: {str(e)}", "images": [], "reference_pages": []}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
