import numpy as np
import json
import os
from pathlib import Path
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
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
AI_CHAT_MODEL   = os.getenv("AI_CHAT_MODEL", "gpt-4o-mini")
AI_IMAGE_BASE_URL = os.getenv("AI_IMAGE_BASE_URL", "http://127.0.0.1:8000")

_client = None
def get_ai_client():
    global _client
    if _client is None:
        kwargs = {
            "timeout":     120.0,
            "max_retries": 2,
        }
        if AI_API_BASE_URL:
            kwargs["base_url"] = AI_API_BASE_URL
        _client = OpenAI(**kwargs)
    return _client

class QuestionRequest(BaseModel):
    question: str
    history: list = []

class CaseAnalysisRequest(BaseModel):
    client_alias:     str
    context_overview: str = ""
    case_details:     dict
    user_profile:     str = ""
    history:          list = []

class ActionPlanRequest(BaseModel):
    case_data:     dict
    analysis_data: dict
    user_profile:  str = ""
    history:       list = []

class Phase(BaseModel):
    title:    str
    steps:    list[str]
    readings: list[str] = []

class ActionPlanSchema(BaseModel):
    executive_summary:        str
    meeting_objectives:       list[str]
    action_plan:              dict
    strategic_recommendations: list[str]
    critical_success_factors: list[str]
    plan_b:                   list[str]

# --- Case Memory Storage ---
CASES_COLLECTION    = "past_cases"
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
    - Replace curly/smart quotes with straight quotes
    - Remove control characters
    - Fix trailing commas in arrays/objects
    - Attempt to close truncated JSON
    """
    # Replace curly quotes with straight quotes
    content = content.replace('\u201c', '"').replace('\u201d', '"')  # " "
    content = content.replace('\u2018', "'").replace('\u2019', "'")  # ' '

    # Remove control characters except newlines and tabs
    content = re.sub(r'[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]', '', content)

    # Fix trailing commas in arrays/objects
    content = re.sub(r',\s*}', '}', content)
    content = re.sub(r',\s*]', ']', content)

    # Strip markdown code fences if AI wrapped JSON in ```json ... ```
    stripped = content.strip()
    if stripped.startswith("```"):
        lines = stripped.split('\n')
        # Remove first and last fence lines
        inner = lines[1:-1] if lines[-1].strip() == '```' else lines[1:]
        content = '\n'.join(inner)

    return content.strip()


def attempt_json_repair(content: str, required_keys: list) -> dict | None:
    """
    Try to repair truncated JSON by asking the AI to fix it.
    Returns parsed dict on success, None on failure.
    """
    try:
        repair_prompt = f"""The following JSON is incomplete or malformed. Fix it so it is valid JSON 
and contains ALL these required keys: {required_keys}.
Return ONLY the corrected JSON with no explanation.

BROKEN JSON:
{content[:3000]}
"""
        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=[{"role": "user", "content": repair_prompt}],
            response_format={"type": "json_object"},
            max_tokens=4096,
        )
        fixed = result.choices[0].message.content
        if fixed:
            return json.loads(sanitize_json_response(fixed))
    except Exception as e:
        print(f"[WARN] JSON repair attempt failed: {e}", flush=True)
    return None


def call_ai_with_retry(messages: list, max_tokens: int = 16384, max_retries: int = 2) -> str | None:
    """
    Call the AI with internal retry on empty/None responses.
    Returns the content string or None if all retries fail.
    """
    for attempt in range(1, max_retries + 1):
        try:
            result = get_ai_client().chat.completions.create(
                model=AI_CHAT_MODEL,
                messages=messages,
                response_format={"type": "json_object"},
                max_tokens=max_tokens,
                temperature=0.3,       # Lower temperature = more consistent structured output
            )
            content = result.choices[0].message.content
            if content and content.strip():
                return content
            finish_reason = result.choices[0].finish_reason if result.choices else "N/A"
            print(f"[WARN] AI returned empty content on attempt {attempt}. Finish reason: {finish_reason}", flush=True)
        except Exception as e:
            print(f"[ERROR] AI call attempt {attempt} failed: {e}", flush=True)
        if attempt < max_retries:
            time.sleep(3)
    return None


# --- Health Check ---
@app.get("/health")
def health():
    return {"status": "ok", "model": AI_CHAT_MODEL}


@app.post("/analyze-case")
def analyze_case(request: CaseAnalysisRequest):
    start_time = time.time()
    try:
        details       = request.case_details
        combined_input = f"{request.client_alias} {request.context_overview} {json.dumps(details)}"

        # 1. Embedding
        t0 = time.time()
        query_vector = embed_model.encode(combined_input).tolist()
        print(f"[PERF] /analyze-case - Embedding: {time.time()-t0:.3f}s", flush=True)

        # 2. Search Past Memory
        t0 = time.time()
        past_cases    = vector_db.query_points(collection_name=CASES_COLLECTION,    query=query_vector, limit=2).points
        past_analysis = vector_db.query_points(collection_name=ANALYZED_COLLECTION, query=query_vector, limit=2).points
        print(f"[PERF] /analyze-case - Memory Search: {time.time()-t0:.3f}s", flush=True)

        past_context = "PAST CASES:\n"
        for res in past_cases:
            past_context += f"- {res.payload.get('objective', '')}\n"
        past_context += "\nPAST AI ADVICE:\n"
        for res in past_analysis:
            past_context += f"- {res.payload.get('recommendations', '')}\n"

        # 3. Search PDF Book
        t0 = time.time()
        book_result = vector_db.query_points(collection_name=COLLECTION_NAME, query=query_vector, limit=5).points
        context     = "\n\n".join([res.payload["text"] for res in book_result])
        print(f"[PERF] /analyze-case - Book Search: {time.time()-t0:.3f}s", flush=True)

        # 4. Generate Analysis
        t0 = time.time()
        system_prompt = f"""You are a World-Class Negotiation Expert and Strategic Advisor with 25+ years of experience.
Your task: perform a deep, actionable analysis of the client case below.

[USER BEHAVIORAL PROFILE]
{request.user_profile if request.user_profile else "No profile provided — apply general best-practice recommendations."}

[BOOK KNOWLEDGE — Use these proven negotiation techniques]
{context}

[HISTORICAL MEMORY — Past similar cases & advice]
{past_context}

[CURRENT CASE]
Client Alias: {request.client_alias}
Overview: {request.context_overview}
Details: {json.dumps(details, indent=2)}

[INSTRUCTIONS]
1. Think step-by-step before writing your final answer.
2. Personalize every recommendation to match the user's behavioral profile weaknesses and strengths.
3. Reference specific book techniques by name where applicable.
4. Each recommendation must be concrete and immediately actionable (not generic advice).
5. Suggest at least 4 recommendations and 4 challenges.
6. YOU MUST return ONLY a valid JSON object — no markdown, no explanation outside the JSON.

Required JSON structure:
{{
  "ai_recommendations": [
    "Concrete actionable recommendation 1 tailored to this client and user profile",
    "Concrete actionable recommendation 2 ...",
    "Concrete actionable recommendation 3 ...",
    "Concrete actionable recommendation 4 ..."
  ],
  "suggested_readings": [
    {{"chapter": "Chapter number", "title": "Chapter title", "time": "Estimated reading time", "reason": "Why this chapter applies"}},
    {{"chapter": "...", "title": "...", "time": "...", "reason": "..."}}
  ],
  "ai_challenges": [
    "Specific challenge this user will likely face in this negotiation",
    "Challenge 2 ...",
    "Challenge 3 ...",
    "Challenge 4 ..."
  ],
  "negotiation_style_tips": [
    "Tip tailored to user profile for this specific situation"
  ],
  "confidence_score": 85
}}"""

        messages = [{"role": "system", "content": system_prompt}]
        content  = call_ai_with_retry(messages, max_tokens=8192)

        if content is None:
            return {"error": "AI returned empty response after retries. Please try again."}

        print(f"[PERF] /analyze-case - AI Generation: {time.time()-t0:.3f}s | {len(content)} chars", flush=True)

        # Sanitize and parse JSON
        sanitized = sanitize_json_response(content)
        required_keys = ["ai_recommendations", "suggested_readings", "ai_challenges"]

        try:
            analysis = json.loads(sanitized)
        except json.JSONDecodeError as e:
            print(f"[WARN] /analyze-case - JSON parse failed, attempting repair: {e}", flush=True)
            analysis = attempt_json_repair(sanitized, required_keys)
            if analysis is None:
                return {
                    "error": f"AI returned malformed JSON and repair failed: {str(e)}",
                    "raw_content": sanitized[:1000]
                }

        # Validate required fields
        missing = [k for k in required_keys if k not in analysis]
        if missing:
            repaired = attempt_json_repair(sanitized, required_keys)
            if repaired:
                analysis = repaired
            else:
                return {"error": f"AI response missing required fields: {missing}", "raw_content": sanitized[:500]}

        # 5. Store in memory
        t0 = time.time()
        case_id = str(uuid.uuid4())

        recommendations     = analysis.get("ai_recommendations", [])
        recommendations_str = ", ".join(recommendations) if isinstance(recommendations, list) else str(recommendations)

        vector_db.upsert(
            collection_name=CASES_COLLECTION,
            points=[PointStruct(id=case_id, vector=query_vector, payload={
                "alias":     request.client_alias,
                "objective": details.get("objective", "")
            })]
        )
        vector_db.upsert(
            collection_name=ANALYZED_COLLECTION,
            points=[PointStruct(id=case_id, vector=query_vector, payload={
                "recommendations": recommendations_str
            })]
        )
        print(f"[PERF] /analyze-case - Storage: {time.time()-t0:.3f}s", flush=True)

        total_time = time.time() - start_time
        print(f"[PERF] /analyze-case - SUCCESS. Total: {total_time:.3f}s", flush=True)

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
        print(f"[PERF] /generate-plan - Embedding: {time.time()-t0:.3f}s", flush=True)

        # 2. Search PDF
        t0 = time.time()
        book_result = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=4,
            with_payload=True
        ).points
        context = "\n\n".join([res.payload["text"] for res in book_result])
        print(f"[PERF] /generate-plan - PDF Search: {time.time()-t0:.3f}s", flush=True)

        # 3. AI Generation
        t0 = time.time()
        system_prompt = f"""You are a World-Class Negotiation Coach creating a personalized, detailed Negotiation Action Plan.

[USER BEHAVIORAL PROFILE]
{request.user_profile if request.user_profile else "No profile provided — create a general best-practice plan."}

[BOOK TECHNIQUES — Apply these specifically in your plan]
{context}

[CASE DATA]
{json.dumps(request.case_data, indent=2)}

[PRIOR AI ANALYSIS]
{json.dumps(request.analysis_data, indent=2)}

[INSTRUCTIONS]
1. Think carefully about each phase before writing.
2. Tailor every step to address the user's behavioral strengths and weaknesses.
3. Include specific negotiation techniques from the book by name.
4. Each phase must have at least 3 detailed, actionable steps (not vague advice).
5. Phases should flow logically: Before meeting → During meeting → After meeting.
6. YOU MUST return ONLY a valid JSON object — no markdown, no explanation outside JSON.
7. ALL 6 fields are required. Do not omit any field.

Required JSON structure (return ALL fields, keep steps detailed but concise):
{{
  "executive_summary": "2-3 sentence summary of the negotiation situation and the plan's core strategy",
  "meeting_objectives": [
    "Clear, measurable objective 1 for this negotiation",
    "Clear, measurable objective 2",
    "Clear, measurable objective 3"
  ],
  "action_plan": {{
    "phase_1_before": {{
      "title": "Pre-Negotiation Preparation",
      "steps": [
        "Detailed step 1 — what to do and why",
        "Detailed step 2 — what to do and why",
        "Detailed step 3 — what to do and why"
      ],
      "readings": ["Chapter X: Title — read to master technique Y"]
    }},
    "phase_2_during": {{
      "title": "In-Meeting Execution",
      "steps": [
        "Detailed step 1 with specific technique reference",
        "Detailed step 2 with specific technique reference",
        "Detailed step 3 with specific technique reference"
      ],
      "readings": []
    }},
    "phase_3_after": {{
      "title": "Post-Negotiation Follow-Up",
      "steps": [
        "Detailed follow-up step 1",
        "Detailed follow-up step 2",
        "Detailed follow-up step 3"
      ],
      "readings": []
    }}
  }},
  "strategic_recommendations": [
    "Strategic recommendation 1 personalized to user profile",
    "Strategic recommendation 2",
    "Strategic recommendation 3"
  ],
  "critical_success_factors": [
    "Factor 1 — the most important thing to get right",
    "Factor 2",
    "Factor 3"
  ],
  "plan_b": [
    "Alternative approach 1 if primary strategy fails",
    "Alternative approach 2",
    "BATNA (Best Alternative To Negotiated Agreement): describe the fallback"
  ]
}}"""

        messages = [{"role": "system", "content": system_prompt}]
        content  = call_ai_with_retry(messages, max_tokens=8192)

        if content is None:
            return {"error": "AI returned empty response after retries. Please try again."}

        print(f"[PERF] /generate-plan - AI Generation: {time.time()-t0:.3f}s | {len(content)} chars", flush=True)

        sanitized = sanitize_json_response(content)
        required_fields = ["executive_summary", "meeting_objectives", "action_plan",
                           "strategic_recommendations", "critical_success_factors", "plan_b"]
        required_phases = ["phase_1_before", "phase_2_during", "phase_3_after"]

        try:
            parsed_content = json.loads(sanitized)
        except json.JSONDecodeError as e:
            print(f"[WARN] /generate-plan - JSON parse failed, attempting repair: {e}", flush=True)
            parsed_content = attempt_json_repair(sanitized, required_fields)
            if parsed_content is None:
                return {
                    "error": f"AI returned malformed JSON and repair failed: {str(e)}",
                    "raw_content": sanitized[:1000]
                }

        # Validate all required top-level fields
        missing_fields = [f for f in required_fields if f not in parsed_content]
        if missing_fields:
            print(f"[WARN] /generate-plan - Missing fields: {missing_fields}, attempting repair", flush=True)
            repaired = attempt_json_repair(sanitized, required_fields)
            if repaired:
                parsed_content = repaired
            else:
                return {"error": f"AI response missing required fields: {missing_fields}", "raw_content": sanitized[:500]}

        # Validate action_plan phases
        action_plan    = parsed_content.get("action_plan", {})
        missing_phases = [p for p in required_phases if p not in action_plan]
        if missing_phases:
            print(f"[WARN] /generate-plan - Missing phases: {missing_phases}", flush=True)
            repaired = attempt_json_repair(sanitized, required_fields)
            if repaired and all(p in repaired.get("action_plan", {}) for p in required_phases):
                parsed_content = repaired
            else:
                return {"error": f"AI response missing required action plan phases: {missing_phases}", "raw_content": sanitized[:500]}

        total_time = time.time() - start_time
        print(f"[PERF] /generate-plan - SUCCESS. Total: {total_time:.3f}s", flush=True)

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
        print(f"[PERF] /ask - Embedding: {time.time()-t0:.3f}s", flush=True)

        # 2. Qdrant Search
        t0 = time.time()
        response = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            limit=20,
            with_payload=True
        )
        search_result = response.points
        print(f"[PERF] /ask - Qdrant Search: {time.time()-t0:.3f}s", flush=True)

        if not search_result:
            return {"answer": "No relevant information found.", "images": [], "reference_pages": []}

        # 3. Re-ranking
        t0 = time.time()
        print(f"[INFO] Re-ranking {len(search_result)} candidates...", flush=True)

        pairs  = [[query, res.payload["text"]] for res in search_result]
        scores = rerank_model.predict(pairs)

        for i, score in enumerate(scores):
            search_result[i].score = score

        search_result.sort(key=lambda x: x.score, reverse=True)
        top_chunks = search_result[:5]
        print(f"[PERF] /ask - Re-ranking: {time.time()-t0:.3f}s", flush=True)

        # 4. Context Building
        context          = ""
        collected_images = []
        reference_pages  = set()

        for res in top_chunks:
            chunk   = res.payload
            context += chunk["text"] + "\n\n"
            for img in (chunk.get("images") or []):
                base_url = (AI_IMAGE_BASE_URL or "http://127.0.0.1:8000").rstrip("/")
                collected_images.append(f"{base_url}/images/{img}")
            reference_pages.add(chunk["page"])

        # 5. AI Completion
        t0 = time.time()
        system_content = f"""You are a helpful AI assistant answering questions about the provided document.
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
            messages=messages,
            temperature=0.5,
        )
        print(f"[PERF] /ask - AI Generation: {time.time()-t0:.3f}s", flush=True)

        full_response = result.choices[0].message.content
        if full_response is None:
            finish_reason = result.choices[0].finish_reason if result.choices else "N/A"
            print(f"[ERROR] /ask - AI returned empty response. Finish reason: {finish_reason}", flush=True)
            return {"answer": "Error: AI returned empty response. Please try again.", "images": [], "reference_pages": []}

        # Split answer and suggestions
        answer      = full_response
        suggestions = []
        if "Suggestions:" in full_response:
            parts          = full_response.split("Suggestions:")
            answer         = parts[0].strip()
            raw_suggestions = parts[1].strip().split("|")
            suggestions    = [s.strip() for s in raw_suggestions if s.strip()]

        total_time = time.time() - start_time
        print(f"[PERF] /ask - SUCCESS. Total: {total_time:.3f}s", flush=True)

        return {
            "answer":          answer,
            "suggestions":     suggestions,
            "images":          list(set(collected_images)),
            "reference_pages": sorted(list(reference_pages))
        }

    except Exception as e:
        print(f"[ERROR] /ask (process_question): {str(e)}", flush=True)
        return {"answer": f"Error: {str(e)}", "images": [], "reference_pages": []}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
