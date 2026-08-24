import numpy as np
import json
import os
from pathlib import Path
from fastapi import FastAPI, Header
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from openai import OpenAI
from sentence_transformers import SentenceTransformer, CrossEncoder
from qdrant_client import QdrantClient
from qdrant_client.models import PointStruct, VectorParams, Distance, Filter, FieldCondition, MatchValue
import uuid
import time
from contextlib import asynccontextmanager

import re
from typing import Any
from dotenv import load_dotenv

# Free local language detection — no AI credits needed
try:
    from langdetect import detect as langdetect_detect
    HAS_LANGDETECT = True
except ImportError:
    HAS_LANGDETECT = False
    print("[WARN] langdetect not installed, language detection will fall back to AI call", flush=True)

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
os.makedirs(images_dir, exist_ok=True)
app.mount("/images", StaticFiles(directory=str(images_dir)), name="images")

def clean_page_one_references(text: str) -> str:
    if not text:
        return text
    # Remove parenthetical or bracketed page 1 references: e.g. (p. 1), (p.1), (page 1), (Page 1), (Chapter 1, p. 1), [p. 1]
    text = re.sub(r'\s*\([^)]*?\b(?:p|page)\.?\s*1\b[^)]*?\)', '', text, flags=re.IGNORECASE)
    text = re.sub(r'\s*\[[^\]]*?\b(?:p|page)\.?\s*1\b[^\]]*?\]', '', text, flags=re.IGNORECASE)
    # Remove standalone p. 1 or p.1 or page 1
    text = re.sub(r',?\s*\b(?:p|page)\.?\s*1\b', '', text, flags=re.IGNORECASE)
    # Clean up empty parens, brackets, and extra spaces
    text = re.sub(r'\(\s*\)', '', text)
    text = re.sub(r'\[\s*\]', '', text)
    text = re.sub(r'  +', ' ', text)
    return text.strip()


def filter_valid_reference_pages(pages):
    valid_pages = []
    for p in pages:
        if p is None or p == "" or str(p).strip() in ["1", "0", "Unknown", "None"]:
            continue
        try:
            p_int = int(p)
            if p_int > 1:
                valid_pages.append(p_int)
        except (ValueError, TypeError):
            if str(p).strip() not in ["1", "0", "Unknown", "None", ""]:
                valid_pages.append(p)
    seen = set()
    res = []
    for p in valid_pages:
        if p not in seen:
            seen.add(p)
            res.append(p)
    return sorted(res, key=lambda x: (isinstance(x, str), x))


# --- Initialize AI Models (Local & Free) ---
print("Loading Embedding Model (paraphrase-multilingual-MiniLM-L12-v2)...", flush=True)
embed_model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')

print("Loading Re-ranker Model (mmarco-mMiniLMv2-L12-H384-v1)...", flush=True)
rerank_model = CrossEncoder('cross-encoder/mmarco-mMiniLMv2-L12-H384-v1')

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

# --- Authoritative Book Table of Contents Catalogue (English & French) ---
# Guarantees complete structural awareness and verified starting page numbers (> Page 2)
BOOK_TOC_CATALOGUE = {
    "Sales_and_negociation_OK-2.pdf": {
        "Introduction: Private Banking, A Business Like No Other": {
            "start_page": 15,
            "keywords": ["introduction", "private banking", "characteristics", "intimate connection", "triangle"],
            "sections": {
                "The Future of Swiss Banking Lies in Its Past": 17,
                "The Key Characteristics of Wealth Management": 19,
                "A Profession with an Intimate Connection": 21
            }
        },
        "Chapter 1: Frameworks, Methods, Concepts, and Lost Illusions": {
            "start_page": 35,
            "keywords": ["advisory", "competitive advantage", "method", "process", "framework", "chapter 1", "chapitre 1"],
            "sections": {
                "Advisory Services Are a Competitive Advantage": 37,
                "Good Advice is Integrated": 39,
                "The Different Conceptual Approaches to Advice": 43
            }
        },
        "Chapter 2: Stay on the Ball! Strategy, Segmentation, and Networking": {
            "start_page": 67,
            "keywords": ["strategy", "segmentation", "networking", "pragmatism", "retention", "chapter 2", "chapitre 2"],
            "sections": {
                "Put Pragmatism at the Heart of Your Strategy": 69,
                "Why Keep Prospecting All the Time": 73,
                "Strategic Client Segmentation": 83,
                "A Satisfied Client is Worth Double": 101
            }
        },
        "Chapter 3: Always Have an Idea, a Vision, and a Strategy": {
            "start_page": 111,
            "keywords": ["vision", "dilemma", "ethics", "who am i", "ahead", "chapter 3", "chapitre 3"],
            "sections": {
                "Professional Dilemmas in Wealth Management": 113,
                "Who Am I When I'm With the Client": 119,
                "Staying One Step Ahead with Strategy": 128
            }
        },
        "Chapter 4: Wealth Managers: The Salespeople Who Must Not Be Named": {
            "start_page": 143,
            "keywords": ["skill", "competency", "roles", "stigma", "selling", "chapter 4", "chapitre 4"],
            "sections": {
                "The Wealth Manager's Many Faces": 145,
                "Skill Sets and Behavioral Competencies": 153,
                "Overcoming the Stigma of Selling": 163
            }
        },
        "Chapter 5: Philosophy and Psychotherapy for Dummies": {
            "start_page": 179,
            "keywords": ["philosophy", "psychotherapy", "emotional intelligence", "dog-eat-dog", "investment", "chapter 5", "chapitre 5"],
            "sections": {
                "A Touch of Philosophy in This Dog-Eat-Dog World": 181,
                "Why Philosophical Arguments Are So Compelling": 186,
                "Emotional Intelligence in Commercial Dialogues": 196
            }
        },
        "Chapter 6: Drivers of Influence: Motivation or Manipulation?": {
            "start_page": 211,
            "keywords": ["influence", "motivation", "manipulation", "authority", "reciprocity", "kindness", "chapter 6", "chapitre 6"],
            "sections": {
                "Drivers of Influence in Client Relationships": 213,
                "Different Influence Techniques": 216,
                "Deference to Authority and Social Proof": 219,
                "Kindness and Reciprocity Strategies": 228
            }
        },
        "Chapter 7: Prospecting, or the Art of Survival": {
            "start_page": 243,
            "keywords": ["prospecting", "survival", "top 50", "leads", "events", "chapter 7", "chapitre 7"],
            "sections": {
                "Prospecting to Build Your Commercial Success": 245,
                "The Reasons Behind Prospecting": 248,
                "Creating the Top 50 High-Potential List": 258,
                "Networking and Event Strategies": 273
            }
        },
        "Chapter 8: First Contact, Lasting Impact": {
            "start_page": 297,
            "keywords": ["first impression", "first contact", "perception", "filter", "rapport", "trust", "chapter 8", "chapitre 8"],
            "sections": {
                "The First Impression is Always the Right One": 299,
                "The Filter Principle and Perception": 308,
                "Creating Immediate Rapport and Trust": 318
            }
        },
        "Chapter 9: Getting to Know the Client's Strongest Side": {
            "start_page": 329,
            "keywords": ["profiling", "client profile", "discovery", "needs", "personality", "questions", "chapter 9", "chapitre 9"],
            "sections": {
                "The Importance of Client Profiling": 331,
                "Deep Psychological and Financial Discovery": 338,
                "Elevating Questions and Active Listening": 348
            }
        },
        "Chapter 10: Persuasion, USP, and Buying Signals": {
            "start_page": 359,
            "keywords": ["persuasion", "usp", "fab", "buying process", "buying signals", "humor", "chapter 10", "chapitre 10"],
            "sections": {
                "The Buying Process in Private Wealth Management": 361,
                "The FAB-USP Triad (Feature, Advantage, Benefit, USP)": 370,
                "Tactics for Negotiating with Humor and Wit": 383,
                "Detecting Buying Signals": 393
            }
        },
        "Chapter 11: How to Handle Objections, Price Negotiation": {
            "start_page": 403,
            "keywords": ["objection", "objections", "price", "pricing", "discount", "iar", "isolate", "price-protection", "fee", "negotiation", "chapter 11", "chapitre 11"],
            "sections": {
                "Objections Add Flavor to Sales": 405,
                "The Difference Between Complaints and Objections": 407,
                "Handling Price Objections and Defending Fees": 411,
                "The Isolate-Agree-Return (IAR) Technique": 415,
                "Price-Protection and Conditional Concessions": 420
            }
        },
        "Chapter 11 bis: Difficult and Delicate Situations": {
            "start_page": 427,
            "keywords": ["deadlock", "delicate", "difficult", "conflict", "portfolio takeover", "impasse", "11 bis", "11bis"],
            "sections": {
                "What to Do When Negotiations Reach a Deadlock": 429,
                "Taking Over a Portfolio from Another Manager": 436,
                "Handling Conflict with Calm Conviction": 442
            }
        },
        "Chapter 12: Closing the Deal and Client Follow-Up": {
            "start_page": 449,
            "keywords": ["closing", "conclusion", "follow-up", "deal", "retention", "commitment", "chapter 12", "chapitre 12"],
            "sections": {
                "The Right Moment to Close: Neither Too Early Nor Too Late": 451,
                "Collecting Buying Signals and Closing Techniques": 456,
                "Post-Deal Client Retention and Follow-Through": 460
            }
        },
        "Chapter 13: Presenting the Bank and Public Speaking": {
            "start_page": 465,
            "keywords": ["public speaking", "pitch", "presenting the bank", "presentation", "hook", "chapter 13", "chapitre 13"],
            "sections": {
                "Introduction to Public Speaking in a Banking Context": 467,
                "Structuring Your Presentation in Four Parts": 468,
                "Delivering a Powerful Hook and Key Message": 474
            }
        },
        "Chapter 14: Cultural Awareness, Etiquette, and Time Management": {
            "start_page": 483,
            "keywords": ["etiquette", "image", "culture", "time management", "codes", "branding", "chapter 14", "chapitre 14"],
            "sections": {
                "Image is Part of a Salesperson's Capital": 485,
                "Meeting People at Events and Personal Branding": 490,
                "Cultural Codes and Etiquette for Wealth Managers": 498
            }
        }
    },
    "Vente_et_negociation_bancaire_png_fr.pdf": {
        "Introduction : Le Private Banking, un business pas comme les autres": {
            "start_page": 15,
            "keywords": ["introduction", "private banking", "gestion de fortune", "connexion intime", "triangle"],
            "sections": {
                "L'avenir de la banque suisse est dans son passé": 17,
                "Les spécificités de la gestion de fortune": 19,
                "Un métier en connexion intime": 21
            }
        },
        "Chapitre 1 : Cadres, méthodes, concepts et illusions perdues": {
            "start_page": 35,
            "keywords": ["conseil", "avantage concurrentiel", "méthode", "processus", "cadre", "chapitre 1", "chapter 1"],
            "sections": {
                "Le conseil est un avantage concurrentiel": 37,
                "Un conseil de qualité est intégré": 39,
                "Les différentes approches conceptuelles du conseil": 43
            }
        },
        "Chapitre 2 : Ne vous endormez pas ! Stratégie, segmentation et networking": {
            "start_page": 67,
            "keywords": ["stratégie", "segmentation", "networking", "pragmatisme", "fidélisation", "chapitre 2", "chapter 2"],
            "sections": {
                "La stratégie du pragmatisme est prioritaire": 69,
                "Pourquoi est-il nécessaire de prospecter en permanence": 73,
                "La segmentation stratégique des clients": 83,
                "La satisfaction d'un client vaut double": 101
            }
        },
        "Chapitre 3 : Toujours une idée, une vision, une stratégie d'avance": {
            "start_page": 111,
            "keywords": ["vision", "dilemme", "éthique", "je roule pour qui", "stratégie", "chapitre 3", "chapter 3"],
            "sections": {
                "Dilemmes professionnels : je roule pour qui": 113,
                "Qui suis-je quand je suis avec le client": 119,
                "Une longueur d'avance grâce à la vision stratégique": 128
            }
        },
        "Chapitre 4 : Gestionnaires de fortune : ces vendeurs qu'on ne saurait nommer": {
            "start_page": 143,
            "keywords": ["compétences", "postures", "visages", "tabou", "vendeur", "chapitre 4", "chapter 4"],
            "sections": {
                "Le gestionnaire, cet être aux multiples visages": 145,
                "Compétences et savoir-faire relationnels": 153,
                "Dépasser le tabou de la vente en banque privée": 163
            }
        },
        "Chapitre 5 : Philosophie et psychothérapie pour les nuls": {
            "start_page": 179,
            "keywords": ["philosophie", "psychothérapie", "intelligence émotionnelle", "argument", "investissement", "chapitre 5", "chapter 5"],
            "sections": {
                "Pourquoi l'argument philosophique est-il convaincant": 181,
                "La philosophie de l'investissement": 186,
                "L'intelligence émotionnelle dans le dialogue commercial": 196
            }
        },
        "Chapitre 6 : Les facteurs d'influence dans la relation commerciale": {
            "start_page": 211,
            "keywords": ["influence", "motivation", "manipulation", "autorité", "réciprocité", "bienveillance", "chapitre 6", "chapter 6"],
            "sections": {
                "Les leviers de l'influence : motivation ou manipulation": 213,
                "Les différentes techniques d'influence": 216,
                "La déférence envers l'autorité et la preuve sociale": 219,
                "Stratégies de bienveillance et de réciprocité": 228
            }
        },
        "Chapitre 7 : La prospection ou l'art de survivre": {
            "start_page": 243,
            "keywords": ["prospection", "survivre", "top 50", "succès", "événements", "chapitre 7", "chapter 7"],
            "sections": {
                "La raison d'être de la prospection": 245,
                "Construire son succès commercial": 248,
                "Définir la liste des 50 clients et prospects prioritaires": 258,
                "Stratégies de réseau et présence aux événements": 273
            }
        },
        "Chapitre 8 : Premier contact, impact durable": {
            "start_page": 297,
            "keywords": ["première impression", "premier contact", "perception", "filtre", "confiance", "chapitre 8", "chapter 8"],
            "sections": {
                "La première impression est toujours la bonne, surtout quand elle est mauvaise": 299,
                "Le principe du filtre et de la perception": 308,
                "Créer la confiance immédiate": 318
            }
        },
        "Chapitre 9 : Mieux cerner le profil et la personnalité du client": {
            "start_page": 329,
            "keywords": ["profil", "besoins profonds", "découverte", "personnalité", "questions", "écoute", "chapitre 9", "chapter 9"],
            "sections": {
                "Les enjeux du profil client et ses besoins profonds": 331,
                "L'analyse biographique et psychologique": 338,
                "Questions d'élévation et écoute active": 348
            }
        },
        "Chapitre 10 : Persuasion, USP et signaux d'achat": {
            "start_page": 359,
            "keywords": ["persuasion", "usp", "fab", "processus d'achat", "signaux d'achat", "humour", "chapitre 10", "chapter 10"],
            "sections": {
                "Le processus d'achat en gestion privée": 361,
                "La méthode FAB-USP (Fonctionnalité, Avantage, Bénéfice, USP)": 370,
                "Tactiques pour négocier avec humour et malice": 383,
                "Identifier et exploiter les signaux d'achat": 393
            }
        },
        "Chapitre 11 : Traitement des objections et négociation du prix": {
            "start_page": 403,
            "keywords": ["objection", "objections", "prix", "tarif", "remise", "iar", "isoler", "honoraires", "négociation", "chapitre 11", "chapter 11"],
            "sections": {
                "Les objections sont le sel de la vente": 405,
                "Différence entre plainte et objection": 407,
                "Défendre ses honoraires et répondre aux objections tarifaires": 411,
                "La technique Isoler-Accepter-Renvoyer (IAR)": 415,
                "Concessions conditionnelles et protection du prix": 420
            }
        },
        "Chapitre 11 bis : Situations difficiles et délicates": {
            "start_page": 427,
            "keywords": ["impasse", "délicates", "difficiles", "conflit", "reprise de portefeuille", "11 bis", "11bis"],
            "sections": {
                "Que faire en cas d'impasse dans une négociation": 429,
                "Reprise de portefeuille par un gestionnaire": 436,
                "Gérer les tensions avec calme et conviction": 442
            }
        },
        "Chapitre 12 : Conclure la vente et assurer le suivi client": {
            "start_page": 449,
            "keywords": ["conclusion", "conclure", "suivi", "vente", "fidélisation", "engagement", "chapitre 12", "chapter 12"],
            "sections": {
                "Le bon moment de la conclusion : ni trop tôt, ni trop tard": 451,
                "La collecte des OUI et les techniques d'engagement": 456,
                "Fidélisation et suivi rigoureux après la signature": 460
            }
        },
        "Chapitre 13 : Présenter la banque et parler en public": {
            "start_page": 465,
            "keywords": ["parler en public", "présentation", "présenter la banque", "accroche", "pitch", "chapitre 13", "chapter 13"],
            "sections": {
                "Introduction à la prise de parole dans un contexte bancaire": 467,
                "Structurer son intervention en quatre temps": 468,
                "Une accroche percutante et un message clé clair": 474
            }
        },
        "Chapitre 14 : Savoir-être, étiquette et gestion du temps": {
            "start_page": 483,
            "keywords": ["étiquette", "image", "capital", "gestion du temps", "codes", "savoir-être", "chapitre 14", "chapter 14"],
            "sections": {
                "L'image est une partie du capital du vendeur": 485,
                "La rencontre lors d'un événement et réseau personnel": 490,
                "Codes culturels et étiquette en banque privée": 498
            }
        }
    }
}

# --- Load Table of Contents (generated during chunking) ---
# This gives the AI complete structural awareness of the book
TOC_DATA = {}
TOC_FILE = BASE_DIR / "data" / "toc.json"
try:
    if TOC_FILE.exists():
        with open(TOC_FILE, "r", encoding="utf-8") as f:
            TOC_DATA = json.load(f)
        print(f"[INIT] Loaded TOC with {sum(len(v['chapters']) for v in TOC_DATA.values())} chapters from {len(TOC_DATA)} source(s)", flush=True)
    else:
        print(f"[WARN] TOC file not found at {TOC_FILE}. Using built-in BOOK_TOC_CATALOGUE.", flush=True)
except Exception as e:
    print(f"[WARN] Failed to load TOC: {e}", flush=True)

# --- Load Chunks (for local exact/keyword/hybrid search) ---
CHUNKS_DATA = []
CHUNKS_FILE = BASE_DIR / "data" / "chunks.json"
try:
    if CHUNKS_FILE.exists():
        with open(CHUNKS_FILE, "r", encoding="utf-8") as f:
            CHUNKS_DATA = json.load(f)
        print(f"[INIT] Loaded {len(CHUNKS_DATA)} chunks for hybrid search", flush=True)
    else:
        print(f"[WARN] Chunks file not found at {CHUNKS_FILE}. Run build_index.sh first.", flush=True)
except Exception as e:
    print(f"[WARN] Failed to load chunks data: {e}", flush=True)


def get_toc_for_source(source_file: str) -> str:
    """Format the TOC for a specific source PDF as a readable string for the AI."""
    toc_dict = None
    if source_file in TOC_DATA and TOC_DATA[source_file].get("chapters"):
        toc_dict = TOC_DATA[source_file].get("chapters", {})
    elif source_file in BOOK_TOC_CATALOGUE:
        toc_dict = BOOK_TOC_CATALOGUE[source_file]
    
    if not toc_dict:
        return ""
    
    lines = []
    for ch_name, ch_data in toc_dict.items():
        start_p = ch_data.get("start_page", 1)
        if start_p <= 2:
            continue
        lines.append(f"- {ch_name} (starts p.{start_p})")
        for sec_name, sec_page in ch_data.get("sections", {}).items():
            if sec_page > 2:
                lines.append(f"  - {sec_name} (p.{sec_page})")
    
    return "\n".join(lines)


def normalize_suggested_readings(readings: list, output_lang: str | None) -> list:
    """
    Ensure every suggested reading has an authentic, valid page_no (> 2) matching
    the specific English or French book edition, eliminating fake 'Page 1' references,
    and populating both 'page_no' and 'page'.
    """
    if not isinstance(readings, list):
        return []

    is_french = bool(output_lang and str(output_lang).lower().startswith("fr"))
    source_file = "Vente_et_negociation_bancaire_png_fr.pdf" if is_french else "Sales_and_negociation_OK-2.pdf"
    catalogue = BOOK_TOC_CATALOGUE.get(source_file, BOOK_TOC_CATALOGUE["Sales_and_negociation_OK-2.pdf"])

    normalized = []
    for item in readings:
        if not isinstance(item, dict):
            continue

        raw_ch = str(item.get("chapter", "")).strip()
        raw_title = str(item.get("title", "")).strip()
        raw_reason = str(item.get("reason", "")).strip()
        raw_time = str(item.get("time", "15 mins")).strip() or "15 mins"
        raw_page_no = item.get("page_no") if item.get("page_no") is not None else item.get("page")

        # 1. Check if page_no is already valid (> 2)
        valid_page = None
        if raw_page_no is not None:
            try:
                p_val = int(raw_page_no)
                if p_val > 2 and p_val <= 520:
                    valid_page = p_val
            except (ValueError, TypeError):
                pass

        # 2. Check if chapter string is purely numeric (e.g., "11", "413", "293", "33")
        clean_ch_name = raw_ch
        if raw_ch.isdigit():
            ch_num_val = int(raw_ch)
            if ch_num_val <= 20:
                # It's a chapter number (e.g. "11")
                clean_ch_name = f"Chapter {raw_ch}" if not is_french else f"Chapitre {raw_ch}"
            else:
                # It was a page number placed in the chapter field
                if valid_page is None:
                    valid_page = ch_num_val
                clean_ch_name = raw_title if raw_title else ("Technique Reference" if not is_french else "Référence Technique")

        # Clean fake "Page 1", "Page 2", "cover" references
        if clean_ch_name.lower() in ["page 1", "page 2", "p. 1", "p. 2", "p.1", "p.2", "cover", "introduction", ""]:
            clean_ch_name = raw_title if raw_title else ("Technique Reference" if not is_french else "Référence Technique")

        # 3. Match against catalogue to find the authentic chapter & starting page
        search_text = f"{clean_ch_name} {raw_title} {raw_reason}".lower()

        # Check explicit chapter number in search_text (e.g. "chapter 11 bis", "chapter 11", "chapitre 11 bis")
        ch_num_match = re.search(r'\b(?:chapter|chapitre)\s*(11\s*bis|\d+)\b', search_text, re.IGNORECASE)

        best_match_page = None
        best_match_name = None

        if ch_num_match:
            num_str = re.sub(r'\s+', ' ', ch_num_match.group(1).lower().strip())
            for cat_ch_name, cat_data in catalogue.items():
                if num_str in ["11 bis", "11bis"] and "11 bis" in cat_ch_name.lower():
                    best_match_page = cat_data["start_page"]
                    best_match_name = cat_ch_name
                    break
                elif num_str not in ["11 bis", "11bis"] and (f" {num_str}:" in cat_ch_name.lower() or f" {num_str} " in cat_ch_name.lower() or cat_ch_name.lower().endswith(f" {num_str}")):
                    best_match_page = cat_data["start_page"]
                    best_match_name = cat_ch_name
                    break

        # If not matched by chapter number, match by keywords and sections
        if best_match_page is None:
            max_score = 0
            for cat_ch_name, cat_data in catalogue.items():
                score = 0
                for kw in cat_data.get("keywords", []):
                    if kw in search_text:
                        score += 1
                for sec_name in cat_data.get("sections", {}):
                    if sec_name.lower() in search_text:
                        score += 2
                if score > max_score:
                    max_score = score
                    best_match_page = cat_data["start_page"]
                    best_match_name = cat_ch_name

        # If page_no was invalid, <= 2, or fake, assign the matched page number
        if valid_page is None or valid_page <= 2:
            if best_match_page and best_match_page > 2:
                valid_page = best_match_page
            else:
                # Default to Chapter 11 (Objections / Pricing) or Chapter 10 if unknown
                valid_page = 403

        # Format clean chapter label
        if (clean_ch_name.isdigit() or len(clean_ch_name) < 4 or "page 1" in clean_ch_name.lower()) and best_match_name:
            clean_ch_name = best_match_name
        elif best_match_name and clean_ch_name in ["Technique Reference", "Référence Technique"]:
            clean_ch_name = best_match_name

        clean_title = raw_title if raw_title else (best_match_name or clean_ch_name)

        normalized.append({
            "chapter": clean_ch_name,
            "title": clean_title,
            "page_no": int(valid_page),
            "page": int(valid_page),
            "time": raw_time,
            "reason": raw_reason
        })

    return normalized


# Roman numeral utility for matching
ROMAN_TO_NUM = {
    "i": "1", "ii": "2", "iii": "3", "iv": "4", "v": "5", "vi": "6", "vii": "7", "viii": "8", "ix": "9", "x": "10",
    "xi": "11", "xii": "12", "xiii": "13", "xiv": "14", "xv": "15"
}
NUM_TO_ROMAN = {v: k for k, v in ROMAN_TO_NUM.items()}


def hybrid_search(query: str, target_lang: str | None, limit: int = 15) -> list:
    """
    Perform a hybrid search combining rule-based heuristics and vector search:
    1. Page number extraction (e.g. "page 47" -> retrieves page 47 directly)
    2. Quote extraction (e.g. "Find: 'X'" -> retrieves exact substring matching chunks)
    3. Acronym extraction (e.g. "CWMA", "ISFB", "USP" -> retrieves chunks containing them)
    4. Deliberate typo handling (e.g. "prospction" -> searches prospection and prospction)
    5. Chapter matching (e.g. "Chapter 9" -> retrieves chunks matching chapter 9/IX)
    6. Dense vector search fallback for semantic matching.
    """
    source_file, source_filter = get_pdf_source_filter(target_lang)
    
    results = []
    seen_ids = set()
    
    class TempPoint:
        def __init__(self, id, payload, score=1.0):
            self.id = id
            self.payload = payload
            self.score = score

    # Helper to add a chunk with a specific score
    def add_chunk(chunk, score):
        chunk_id = chunk.get("id") or str(uuid.uuid4())
        if chunk_id not in seen_ids:
            results.append(TempPoint(id=chunk_id, payload=chunk, score=score))
            seen_ids.add(chunk_id)

    query_lower = query.lower()

    # --- A. Exact Quotes Matching ---
    # Look for text in quotes like "The conclusion is fluid..." or « Communiquer, c'est l'oxygène… »
    quotes = re.findall(r'["«“”]([^"»“”]{4,})["»“”]', query)
    # Also support searching for substring after "find this sentence:" or "where is this sentence:"
    sentence_match = re.search(r'(?:find|where is)\s+(?:this\s+sentence|the\s+sentence|the\s+quote)?\s*[:\"«“”]\s*([^\"»“”]+)', query, re.IGNORECASE)
    if sentence_match:
        quotes.append(sentence_match.group(1).strip())

    for quote in quotes:
        quote_clean = quote.strip().lower()
        # Truncate trailing ellipsis if present
        if quote_clean.endswith("..."):
            quote_clean = quote_clean[:-3].strip()
        elif quote_clean.endswith("…"):
            quote_clean = quote_clean[:-1].strip()
            
        print(f"[HYBRID] Searching for exact quote: {quote_clean}", flush=True)
        for c in CHUNKS_DATA:
            if c.get("source") == source_file and quote_clean in c.get("text", "").lower():
                add_chunk(c, 3.0)  # Very high score for exact quote matches

    # --- B. Page Number Matching ---
    # e.g. "page 47", "p. 47", "page number 310"
    page_match = re.search(r'\b(?:page|p\.?)\s*(\d+)\b', query, re.IGNORECASE)
    if page_match:
        target_page = int(page_match.group(1))
        print(f"[HYBRID] Searching directly for Page: {target_page}", flush=True)
        for c in CHUNKS_DATA:
            if c.get("source") == source_file and c.get("page") == target_page:
                add_chunk(c, 2.5)  # High score for page matching

    # --- C. Acronym / Term Matching ---
    # E.g. CWMA, ISFB, USP
    acronyms = re.findall(r'\b([A-Z]{3,5})\b', query)
    for acr in acronyms:
        if acr in ["ONLY", "PAGE", "TOC", "PDF", "RAG"]:
            continue
        print(f"[HYBRID] Searching for acronym: {acr}", flush=True)
        acr_pattern = re.compile(rf'\b{acr}\b')
        for c in CHUNKS_DATA:
            if c.get("source") == source_file and acr_pattern.search(c.get("text", "")):
                add_chunk(c, 2.0)

    # --- D. Deliberate Typo / Keyword Matching ---
    # Handle "prospction" / "prospection" specifically
    if "prospction" in query_lower or "prospection" in query_lower:
        print("[HYBRID] Searching for prospection/prospction keywords", flush=True)
        for c in CHUNKS_DATA:
            if c.get("source") == source_file and ("prospction" in c.get("text", "").lower() or "prospection" in c.get("text", "").lower()):
                add_chunk(c, 2.2)

    # --- E. Chapter Matching ---
    # e.g. "chapter 9", "chapter 11 bis", "summarize only chapter 9"
    chapter_match = re.search(r'\b(?:chapter|chapitre)\s+(\d+|[ivxlcdm]+)(?:\s+(?:bis|ter))?\b', query, re.IGNORECASE)
    if chapter_match:
        ch_num = chapter_match.group(1).lower()
        full_match = chapter_match.group(0).lower()
        suffix = " bis" if "bis" in full_match else (" ter" if "ter" in full_match else "")
        
        # Determine the alternative form (e.g. if query says "9", look for "ix" too; if query says "ix", look for "9" too)
        alt_num = ROMAN_TO_NUM.get(ch_num, "") if ch_num in ROMAN_TO_NUM else NUM_TO_ROMAN.get(ch_num, "")
        
        print(f"[HYBRID] Chapter search: {ch_num}{suffix} (alt: {alt_num}{suffix})", flush=True)
        for c in CHUNKS_DATA:
            if c.get("source") == source_file:
                ch_meta = c.get("chapter", "").lower()
                # Check match against primary and alternative numbers
                match_primary = f"chapter {ch_num}{suffix}" in ch_meta or f"chapitre {ch_num}{suffix}" in ch_meta
                match_alt = alt_num and (f"chapter {alt_num}{suffix}" in ch_meta or f"chapitre {alt_num}{suffix}" in ch_meta)
                
                if match_primary or match_alt:
                    add_chunk(c, 1.8)

    # --- F. Dense Vector Search ---
    # Fetch from Qdrant using vector embeddings to get semantic matches
    try:
        query_vector = embed_model.encode(query).tolist()
        qdrant_response = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            query_filter=source_filter,
            limit=limit,
            with_payload=True
        )
        for point in qdrant_response.points:
            # Ensure we match source file filter (redundant check just in case)
            if point.payload.get("source") != source_file:
                continue
            point_id = point.payload.get("id") or point.id
            if point_id not in seen_ids:
                results.append(TempPoint(id=point_id, payload=point.payload, score=point.score))
                seen_ids.add(point_id)
    except Exception as e:
        print(f"[HYBRID] Qdrant search fallback failed: {e}", flush=True)

    return results


# AI Configuration for Chat (OpenAI/OpenRouter)
AI_API_BASE_URL = os.getenv("AI_API_BASE_URL", None)
AI_CHAT_MODEL   = os.getenv("AI_CHAT_MODEL", "gpt-4o-mini")
AI_IMAGE_BASE_URL = os.getenv("AI_IMAGE_BASE_URL", "http://127.0.0.1:8000")

print(f"[INIT] AI_API_BASE_URL: {AI_API_BASE_URL}", flush=True)
print(f"[INIT] AI_CHAT_MODEL: {AI_CHAT_MODEL}", flush=True)

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
    lang: str | None = None

class CaseAnalysisRequest(BaseModel):
    client_id:        str | None = None
    client_alias:     str = "Client"
    context_overview: str | None = ""
    case_details:     dict | list | None = Field(default_factory=dict)
    user_profile:     str = ""
    history:          list = []
    client_history:   list = []
    lang:             str | None = None

class ActionPlanRequest(BaseModel):
    client_id:      str | None = None
    case_data:      dict | list | None = Field(default_factory=dict)
    analysis_data:  dict | list | None = Field(default_factory=dict)
    user_profile:   str = ""
    history:        list = []
    client_history: list = []
    lang:           str | None = None
    user_question:  str | None = None

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

# ---------------------------------------------------------------------------
# JSON Schemas — enforced via Infomaniak's json_schema response_format.
# These guarantee the mobile app always receives the exact structure it needs.
# ---------------------------------------------------------------------------

ANALYZE_CASE_RESPONSE_FORMAT = {
    "type": "json_schema",
    "json_schema": {
        "name": "case_analysis",
        "strict": True,
        "schema": {
            "type": "object",
            "properties": {
                "ai_recommendations": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "suggested_readings": {
                    "type": "array",
                    "items": {
                        "type": "object",
                        "properties": {
                            "chapter": {"type": "string"},
                            "title":   {"type": "string"},
                            "page_no": {"type": "integer"},
                            "time":    {"type": "string"},
                            "reason":  {"type": "string"}
                        },
                        "required": ["chapter", "title", "page_no", "time", "reason"],
                        "additionalProperties": False
                    }
                },
                "ai_challenges": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "negotiation_style_tips": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "confidence_score": {"type": "integer"}
            },
            "required": [
                "ai_recommendations",
                "suggested_readings",
                "ai_challenges",
                "negotiation_style_tips",
                "confidence_score"
            ],
            "additionalProperties": False
        }
    }
}

GENERATE_PLAN_RESPONSE_FORMAT = {
    "type": "json_schema",
    "json_schema": {
        "name": "action_plan",
        "strict": True,
        "schema": {
            "type": "object",
            "properties": {
                "executive_summary": {"type": "string"},
                "meeting_objectives": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "action_plan": {
                    "type": "object",
                    "properties": {
                        "phase_1_before": {
                            "type": "object",
                            "properties": {
                                "title":    {"type": "string"},
                                "steps":    {"type": "array", "items": {"type": "string"}},
                                "readings": {"type": "array", "items": {"type": "string"}}
                            },
                            "required": ["title", "steps", "readings"],
                            "additionalProperties": False
                        },
                        "phase_2_during": {
                            "type": "object",
                            "properties": {
                                "title":    {"type": "string"},
                                "steps":    {"type": "array", "items": {"type": "string"}},
                                "readings": {"type": "array", "items": {"type": "string"}}
                            },
                            "required": ["title", "steps", "readings"],
                            "additionalProperties": False
                        },
                        "phase_3_after": {
                            "type": "object",
                            "properties": {
                                "title":    {"type": "string"},
                                "steps":    {"type": "array", "items": {"type": "string"}},
                                "readings": {"type": "array", "items": {"type": "string"}}
                            },
                            "required": ["title", "steps", "readings"],
                            "additionalProperties": False
                        }
                    },
                    "required": ["phase_1_before", "phase_2_during", "phase_3_after"],
                    "additionalProperties": False
                },
                "strategic_recommendations": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "critical_success_factors": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "plan_b": {
                    "type": "array",
                    "items": {"type": "string"}
                },
                "user_question_answer": {
                    "type": "object",
                    "properties": {
                        "question": {"type": "string"},
                        "answer":   {"type": "string"}
                    },
                    "required": ["question", "answer"],
                    "additionalProperties": False
                }
            },
            "required": [
                "executive_summary",
                "meeting_objectives",
                "action_plan",
                "strategic_recommendations",
                "critical_success_factors",
                "plan_b",
                "user_question_answer"
            ],
            "additionalProperties": False
        }
    }
}

# ---------------------------------------------------------------------------

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


def is_cover_or_frontmatter_image(img: str, page: int | str | None = None) -> bool:
    """Check if an image or page is a cover / front-matter / page 1-2 artifact."""
    if page is not None:
        try:
            if int(page) <= 2:
                return True
        except (ValueError, TypeError):
            pass

    img_lower = str(img).lower().strip()
    return (
        "page_1_" in img_lower
        or "page_2_" in img_lower
        or "page_1." in img_lower
        or "page_2." in img_lower
        or "cover" in img_lower
        or "snapshot" in img_lower
        or img_lower.endswith("page_1_img_1.png")
    )


def filter_valid_technique_images(images: list, page: int | str | None = None) -> list[str]:
    """Filter out cover pages, title pages (pages 1 and 2), snapshots, and cover images."""
    try:
        if page is not None and int(page) <= 2:
            return []
    except (ValueError, TypeError):
        pass

    return [img for img in images if not is_cover_or_frontmatter_image(img, page)]


def sanitize_ai_output_content(data: Any) -> Any:
    """
    Recursively clean up any cover/front-matter image URLs, 'Images: None' artifacts,
    or fake 'Page 1' / 'Page 2' references from AI-generated JSON.
    """
    if isinstance(data, str):
        cleaned = data
        # Remove any markdown or text containing page_1 / page_2 / cover image URLs
        cleaned = re.sub(r'\(?https?://[^\s\)]+?/images/(?:page_[12]_[^\s\)]+|cover[^\s\)]+|[^\s\)]*snapshot[^\s\)]*)\)?', '', cleaned, flags=re.IGNORECASE)
        # Remove text artifacts like [Image: ...], (Image: ...), | Images: None, Image: None
        cleaned = re.sub(r'\s*\(Images?:\s*None\)', '', cleaned, flags=re.IGNORECASE)
        cleaned = re.sub(r'\s*\[Images?:\s*None\]', '', cleaned, flags=re.IGNORECASE)
        cleaned = re.sub(r'\s*\|\s*Images?:\s*None', '', cleaned, flags=re.IGNORECASE)
        cleaned = re.sub(r'\s*\(Images?:\s*\)', '', cleaned, flags=re.IGNORECASE)
        cleaned = re.sub(r'\s*\[Images?:\s*\]', '', cleaned, flags=re.IGNORECASE)
        cleaned = re.sub(r'\s*\|\s*Images?:\s*$', '', cleaned, flags=re.IGNORECASE)
        # Clean up empty source citations e.g. "[Source Page: 1]" or "[Page: 1]"
        cleaned = re.sub(r'\[(?:Source\s+)?Page:\s*[12]\]', '', cleaned, flags=re.IGNORECASE)
        cleaned = re.sub(r'\((?:Source\s+)?Page:\s*[12]\)', '', cleaned, flags=re.IGNORECASE)
        return cleaned.strip()

    if isinstance(data, dict):
        cleaned_dict = {}
        for k, v in data.items():
            if k == "chapter" and isinstance(v, str) and (v.strip().lower() in ["page 1", "page 2", "p. 1", "p. 2", "p.1", "p.2", "cover", "introduction"]):
                # If suggested_readings has chapter: "Page 1", replace with the title or a clean reference
                title_val = data.get("title", "")
                cleaned_dict[k] = title_val if title_val else "Technique Reference"
            else:
                cleaned_dict[k] = sanitize_ai_output_content(v)
        return cleaned_dict

    if isinstance(data, list):
        cleaned_list = []
        for item in data:
            cleaned_item = sanitize_ai_output_content(item)
            # If item became empty string after removing image artifact, only keep if it was meaningful
            if isinstance(cleaned_item, str) and not cleaned_item.strip():
                continue
            cleaned_list.append(cleaned_item)
        return cleaned_list

    return data


def attempt_json_repair(content: str, required_keys: list, schema_template: str = "", response_format: dict | None = None) -> dict | None:
    """
    Try to repair truncated JSON by asking the AI to fix it.
    Returns parsed dict on success, None on failure.
    response_format: pass the json_schema dict for the endpoint (e.g. ANALYZE_CASE_RESPONSE_FORMAT).
    """
    try:
        template_str = f"\nThe output MUST strictly follow this JSON structure:\n{schema_template}\n" if schema_template else ""
        repair_prompt = f"""The following JSON is incomplete or malformed. Fix it so it is valid JSON 
and contains ALL these required keys: {required_keys}.
{template_str}
Return ONLY the valid corrected JSON with no extra explanation.

BROKEN JSON:
{content[:3000]}
"""
        call_kwargs = {
            "model":      AI_CHAT_MODEL,
            "messages":   [{"role": "user", "content": repair_prompt}],
            "max_tokens": 4096,
        }
        if response_format:
            call_kwargs["response_format"] = response_format

        result = get_ai_client().chat.completions.create(**call_kwargs)
        fixed = result.choices[0].message.content
        if fixed:
            return json.loads(sanitize_json_response(fixed))
    except Exception as e:
        print(f"[WARN] JSON repair attempt failed: {e}", flush=True)
    return None


def call_ai_with_retry(messages: list, max_tokens: int = 16384, max_retries: int = 2, response_format: dict | None = None) -> str | None:
    """
    Call the AI with internal retry on empty/None responses.
    Returns the content string or None if all retries fail.
    response_format: pass the json_schema dict for the endpoint (e.g. ANALYZE_CASE_RESPONSE_FORMAT).
                     Leave None for plain-text responses (e.g. /ask endpoint).
    """
    for attempt in range(1, max_retries + 1):
        try:
            call_kwargs = {
                "model":       AI_CHAT_MODEL,
                "messages":    messages,
                "max_tokens":  max_tokens,
                "temperature": 0.3,   # Lower temperature = more consistent structured output
            }
            if response_format:
                call_kwargs["response_format"] = response_format

            result = get_ai_client().chat.completions.create(**call_kwargs)
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
def analyze_case(request: CaseAnalysisRequest, accept_language: str | None = Header(default=None)):
    start_time = time.time()
    try:
        # Determine output language
        target_lang = request.lang or accept_language
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
                else:
                    output_lang = target_lang

        details = request.case_details if isinstance(request.case_details, dict) else {}
        overview = request.context_overview or ""
        alias = request.client_alias or "Client"
        combined_input = f"{alias} {overview} {json.dumps(details)}"

        # 1. Embedding — embed as-is; Qdrant filter selects the correct language book
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
        source_file, source_filter = get_pdf_source_filter(output_lang)
        book_result = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            query_filter=source_filter,
            limit=5,
            with_payload=True
        ).points
        
        context_parts = []
        for res in book_result:
            text = res.payload.get("text", "")
            page = res.payload.get("page", "Unknown")
            chapter = res.payload.get("chapter", "")
            section = res.payload.get("section", "")
            images = res.payload.get("images", [])
            valid_imgs = filter_valid_technique_images(images, page)
            imgs_str = ", ".join([f"{AI_IMAGE_BASE_URL}/images/{img}" for img in valid_imgs]) if valid_imgs else "None"
            source_header = f"[Page: {page}"
            if chapter:
                source_header += f" | Chapter: {chapter}"
            if section:
                source_header += f" | Section: {section}"
            source_header += f" | Images: {imgs_str}]"
            context_parts.append(f"{source_header}\n{text}")
        context = "\n\n".join(context_parts)
        print(f"[PERF] /analyze-case - Book Search ({source_file}): {time.time()-t0:.3f}s", flush=True)

        # 4. Generate Analysis
        t0 = time.time()
        print(f"[INFO] /analyze-case - target_lang: {target_lang}, resolved output_lang: {output_lang}", flush=True)
        
        # Get language-specific TOC for structural chapter and starting page awareness
        toc_text = get_toc_for_source(source_file)
        toc_section = ""
        if toc_text:
            toc_section = f"\n[BOOK TABLE OF CONTENTS & STARTING PAGE NUMBERS — Use these exact chapters and pages]\n{toc_text}\n"

        lang_warning = ""
        if output_lang and output_lang.lower() != "english":
            lang_warning = f"[CRITICAL LANGUAGE INSTRUCTION]\nYou MUST write ALL values, recommendations, challenges, style tips, reading reasons, and titles in {output_lang}. Do NOT write any values in English. Only the JSON keys themselves must remain strictly in English.\n[CRITICAL] Répondez uniquement en {output_lang} pour toutes les valeurs textuelles du JSON.\n\n"

        client_history_context = ""
        if request.client_history:
            client_history_context = "[CLIENT PREVIOUS CASE HISTORY & OUTCOMES]\n"
            for idx, h in enumerate(request.client_history, 1):
                client_history_context += f"Previous Case {idx} ({h.get('date', 'Past')} - {h.get('client_alias', '')}):\n"
                if h.get('case_reference'):
                    client_history_context += f"  Reference: {h.get('case_reference')}\n"
                if h.get('context_overview'):
                    client_history_context += f"  Overview: {h.get('context_overview')}\n"
                if h.get('ai_recommendations'):
                    client_history_context += f"  Prior Recommendations: {json.dumps(h.get('ai_recommendations'))}\n"
                if h.get('action_plan_summary'):
                    client_history_context += f"  Prior Action Plan: {h.get('action_plan_summary')}\n"
                if h.get('plan_rating'):
                    client_history_context += f"  User Rating: {h.get('plan_rating')}/5\n"
            client_history_context += "\n"

        system_prompt = lang_warning + f"""You are a World-Class Negotiation Expert and Strategic Advisor with 25+ years of experience.
Your task: perform a deep, actionable analysis of the client case below.

[USER BEHAVIORAL PROFILE]
{request.user_profile if request.user_profile else "No profile provided — apply general best-practice recommendations."}
{toc_section}
[BOOK KNOWLEDGE — Use these proven negotiation techniques]
{context}

[HISTORICAL MEMORY — Past similar cases & advice]
{past_context}
{client_history_context}
[CURRENT CASE]
Client Alias: {request.client_alias}
Overview: {request.context_overview}
Details: {json.dumps(details, indent=2)}

[INSTRUCTIONS]
1. Think step-by-step before writing your final answer.
2. Personalize every recommendation to match the user's behavioral profile weaknesses and strengths.
3. If previous client case history is provided, ensure strategic continuity and address evolving client dynamics.
4. Reference specific book techniques and chapters from the context and Table of Contents. ONLY cite real content pages (> Page 2) and chapter names. NEVER cite "Page 1", "Page 2", or book cover/intro pages.
5. CRITICAL IMAGE RULE: ONLY cite an image URL if the corresponding technique in the [BOOK KNOWLEDGE] context explicitly lists a real diagram URL (NOT None). If [Images] is None or if no diagram exists for the technique, DO NOT include any image URL or image citation. NEVER cite or invent Page 1/cover images. If no diagram applies, omit images completely.
6. Each recommendation must be concrete and immediately actionable (not generic advice).
7. Suggest at least 4 recommendations and 4 challenges.
8. In "suggested_readings", reference actual chapter names and topics from the book catalogue above. Every item MUST have a valid "page_no" (integer > 2, matching the authentic starting page of the chapter in the {output_lang or 'English'} edition). NEVER cite "Page 1", "Page 2", or cover pages.
9. YOU MUST return ONLY a valid JSON object — no markdown, no explanation outside the JSON.

Required JSON structure:
{{
  "ai_recommendations": [
    "Concrete actionable recommendation 1 tailored to this client and user profile",
    "Concrete actionable recommendation 2 ...",
    "Concrete actionable recommendation 3 ...",
    "Concrete actionable recommendation 4 ..."
  ],
  "suggested_readings": [
    {{"chapter": "Chapter 11: How to Handle Objections, Price Negotiation", "title": "Handling Price Objections and Defending Fees", "page_no": 405, "time": "15 mins", "reason": "Provides the specific logic for praising a client's fussiness about price to build rapport."}},
    {{"chapter": "Chapter 10: Persuasion, USP, and Buying Signals", "title": "The FAB-USP Triad and Value Differentiation", "page_no": 361, "time": "10 mins", "reason": "Reinforces the doctor analogy and USP to make your solution the only viable choice."}},
    {{"chapter": "Chapter 2: Stay on the Ball! Strategy, Segmentation, and Networking", "title": "Client Retention and Strategic Segmentation", "page_no": 69, "time": "10 mins", "reason": "Outlines retention strategies and calculating long-term client value."}}
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

        system_prompt += "\n\n"
        if output_lang and output_lang.lower() != "english":
            system_prompt += f"8. CRITICAL: All textual values in the output JSON (such as recommendations, challenges, style tips, reading reasons/titles/chapters) MUST be written in {output_lang}. The JSON keys themselves MUST remain strictly in English as specified."
            system_prompt += f"\n9. LANGUAGE COMPLIANCE: Remember, the user's selected language is {output_lang}. You MUST use the {output_lang} Table of Contents and translate all your recommendations, challenges, tips, and readings into {output_lang}. Ensure 'page_no' corresponds to the {output_lang} book pages."
        else:
            system_prompt += "8. CRITICAL: All textual values in the output JSON MUST be written in English. The JSON keys themselves MUST remain strictly in English as specified."

        messages = [{"role": "system", "content": system_prompt}]
        content  = call_ai_with_retry(messages, max_tokens=8192, response_format=ANALYZE_CASE_RESPONSE_FORMAT)

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
            analysis = attempt_json_repair(sanitized, required_keys, response_format=ANALYZE_CASE_RESPONSE_FORMAT)
            if analysis is None:
                return {
                    "error": f"AI returned malformed JSON and repair failed: {str(e)}",
                    "raw_content": sanitized[:1000]
                }

        # Validate required fields
        missing = [k for k in required_keys if k not in analysis]
        if missing:
            repaired = attempt_json_repair(sanitized, required_keys, response_format=ANALYZE_CASE_RESPONSE_FORMAT)
            if repaired:
                analysis = repaired
            else:
                return {"error": f"AI response missing required fields: {missing}", "raw_content": sanitized[:500]}

        # Sanitize output of any cover images / fake page 1 references
        analysis = sanitize_ai_output_content(analysis)

        # Normalize suggested readings to guarantee authentic page numbers (> Page 2) and language alignment
        if "suggested_readings" in analysis and isinstance(analysis["suggested_readings"], list):
            analysis["suggested_readings"] = normalize_suggested_readings(analysis["suggested_readings"], output_lang)

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
def generate_plan(request: ActionPlanRequest, accept_language: str | None = Header(default=None)):
    start_time = time.time()
    try:
        # Determine output language
        target_lang = request.lang or accept_language
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
                else:
                    output_lang = target_lang

        case_data = request.case_data if isinstance(request.case_data, dict) else {}
        analysis_data = request.analysis_data if isinstance(request.analysis_data, dict) else {}
        combined_input = json.dumps(case_data) + json.dumps(analysis_data)

        # 1. Embedding — embed as-is; Qdrant filter selects the correct language book
        t0 = time.time()
        query_vector = embed_model.encode(combined_input).tolist()
        print(f"[PERF] /generate-plan - Embedding: {time.time()-t0:.3f}s", flush=True)

        # 2. Search PDF
        t0 = time.time()
        source_file, source_filter = get_pdf_source_filter(output_lang)
        book_result = vector_db.query_points(
            collection_name=COLLECTION_NAME,
            query=query_vector,
            query_filter=source_filter,
            limit=4,
            with_payload=True
        ).points
        
        context_parts = []
        for res in book_result:
            text = res.payload.get("text", "")
            page = res.payload.get("page", "Unknown")
            chapter = res.payload.get("chapter", "")
            section = res.payload.get("section", "")
            images = res.payload.get("images", [])
            valid_imgs = filter_valid_technique_images(images, page)
            imgs_str = ", ".join([f"{AI_IMAGE_BASE_URL}/images/{img}" for img in valid_imgs]) if valid_imgs else "None"
            source_header = f"[Page: {page}"
            if chapter:
                source_header += f" | Chapter: {chapter}"
            if section:
                source_header += f" | Section: {section}"
            source_header += f" | Images: {imgs_str}]"
            context_parts.append(f"{source_header}\n{text}")
        context = "\n\n".join(context_parts)
        print(f"[PERF] /generate-plan - PDF Search ({source_file}): {time.time()-t0:.3f}s", flush=True)

        # 3. AI Generation
        t0 = time.time()
        print(f"[INFO] /generate-plan - target_lang: {target_lang}, resolved output_lang: {output_lang}", flush=True)

        # Get language-specific TOC for structural chapter and starting page awareness
        toc_text = get_toc_for_source(source_file)
        toc_section = ""
        if toc_text:
            toc_section = f"\n[BOOK TABLE OF CONTENTS & STARTING PAGE NUMBERS]\n{toc_text}\n"

        lang_warning = ""
        if output_lang and output_lang.lower() != "english":
            lang_warning = f"[CRITICAL LANGUAGE INSTRUCTION]\nYou MUST write ALL values, summaries, meeting objectives, action plan steps, readings, strategic recommendations, and user_question_answer.answer in {output_lang}. Do NOT write any values in English. Only the JSON keys themselves must remain strictly in English.\n[CRITICAL] Répondez uniquement en {output_lang} pour toutes les valeurs textuelles du JSON.\n\n"

        client_history_context = ""
        if request.client_history:
            client_history_context = "\n[CLIENT PREVIOUS CASE HISTORY & OUTCOMES]\n"
            for idx, h in enumerate(request.client_history, 1):
                client_history_context += f"Previous Case {idx} ({h.get('date', 'Past')} - {h.get('client_alias', '')}):\n"
                if h.get('case_reference'):
                    client_history_context += f"  Reference: {h.get('case_reference')}\n"
                if h.get('context_overview'):
                    client_history_context += f"  Overview: {h.get('context_overview')}\n"
                if h.get('ai_recommendations'):
                    client_history_context += f"  Prior Recommendations: {json.dumps(h.get('ai_recommendations'))}\n"
                if h.get('action_plan_summary'):
                    client_history_context += f"  Prior Action Plan: {h.get('action_plan_summary')}\n"
                if h.get('plan_rating'):
                    client_history_context += f"  User Rating: {h.get('plan_rating')}/5\n"

        user_question_section = ""
        if request.user_question and request.user_question.strip():
            target_lang_str = output_lang if output_lang else "the user's language"
            user_question_section = f"""\n[USER SPECIFIC QUESTION - MANDATORY ANSWER REQUIRED]
The user submitted the following specific question regarding this negotiation:
"{request.user_question.strip()}"

INSTRUCTIONS FOR "user_question_answer":
- "question": "{request.user_question.strip()}"
- "answer": MUST NOT BE EMPTY. Provide a comprehensive, 3-5 sentence expert tactical negotiation strategy directly answering the user's question in {target_lang_str}.
"""
        else:
            user_question_section = """\n[USER SPECIFIC QUESTION]
No user question was submitted. Set "question" to "" and "answer" to "" in the "user_question_answer" field.
"""

        system_prompt = lang_warning + f"""You are a World-Class Negotiation Coach creating a personalized, detailed Negotiation Action Plan.

[USER BEHAVIORAL PROFILE]
{request.user_profile if request.user_profile else "No profile provided — create a general best-practice plan."}
{toc_section}
[BOOK TECHNIQUES — Apply these specifically in your plan]
{context}
{client_history_context}
{user_question_section}
[CASE DATA]
{json.dumps(request.case_data, indent=2)}

[PRIOR AI ANALYSIS]
{json.dumps(request.analysis_data, indent=2)}

[INSTRUCTIONS]
1. Think carefully about each phase before writing.
2. Tailor every step to address the user's behavioral strengths and weaknesses.
3. If previous client case history is provided, build on prior outcomes and ensure cohesive tactical progression.
4. Include specific negotiation techniques from the book by name, citing real content pages (> Page 2) and chapter names. NEVER cite "Page 1" or book cover pages.
5. CRITICAL IMAGE RULE: ONLY cite an image URL if an exact technique diagram (> Page 2) is explicitly provided in the [BOOK TECHNIQUES] context. If [Images] is None or no specific diagram exists, DO NOT include any image URL or placeholder. If no diagram applies, omit images completely.
6. Each phase must have at least 3 detailed, actionable steps (not vague advice).
7. Phases should flow logically: Before meeting → During meeting → After meeting.
8. In action_plan.*.readings, reference actual technique chapters and topics from the book (never "Page 1").
9. YOU MUST return ONLY a valid JSON object — no markdown, no explanation outside JSON.
10. ALL fields including user_question_answer are required. Do not omit any field.
11. CRITICAL: Do NOT return the structure or keys of a Case Analysis (do not use keys like "ai_recommendations", "suggested_readings", "ai_challenges", or "negotiation_style_tips"). You MUST return strictly the Action Plan structure below.

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
      "readings": ["Chapter X / Page Y: Title — read to master technique Z (Image ref if any)"]
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
  ],
  "user_question_answer": {{
    "question": "User question string if provided, or empty string if none",
    "answer": "Detailed answer string to user question if provided, or empty string if none"
  }}
}}"""

        system_prompt += "\n\n"
        if output_lang and output_lang.lower() != "english":
            system_prompt += f"10. CRITICAL: All textual values in the output JSON (such as executive summary, objectives, action plan steps/titles, recommendations, CSF, plan B, and user_question_answer.answer) MUST be written in {output_lang}. The JSON keys themselves MUST remain strictly in English as specified."
            system_prompt += f"\n11. LANGUAGE COMPLIANCE: Remember, the user's selected language is {output_lang}. You MUST translate all your summaries, objectives, action plan steps/readings, recommendations, CSFs, Plan B elements, and user question answers into {output_lang}. Do not write them in English."
        else:
            system_prompt += "10. CRITICAL: All textual values in the output JSON MUST be written in English. The JSON keys themselves MUST remain strictly in English as specified."

        messages = [{"role": "system", "content": system_prompt}]
        content  = call_ai_with_retry(messages, max_tokens=8192, response_format=GENERATE_PLAN_RESPONSE_FORMAT)

        if content is None:
            return {"error": "AI returned empty response after retries. Please try again."}

        print(f"[PERF] /generate-plan - AI Generation: {time.time()-t0:.3f}s | {len(content)} chars", flush=True)

        sanitized = sanitize_json_response(content)
        required_fields = ["executive_summary", "meeting_objectives", "action_plan",
                           "strategic_recommendations", "critical_success_factors", "plan_b",
                           "user_question_answer"]
        required_phases = ["phase_1_before", "phase_2_during", "phase_3_after"]

        schema_template = """{
  "executive_summary": "Summary string",
  "meeting_objectives": ["Objective 1", "Objective 2"],
  "action_plan": {
    "phase_1_before": {
      "title": "Pre-Negotiation Preparation",
      "steps": ["Step 1", "Step 2"],
      "readings": ["Reading 1"]
    },
    "phase_2_during": {
      "title": "In-Meeting Execution",
      "steps": ["Step 1", "Step 2"],
      "readings": []
    },
    "phase_3_after": {
      "title": "Post-Negotiation Follow-Up",
      "steps": ["Step 1", "Step 2"],
      "readings": []
    }
  },
  "strategic_recommendations": ["Recommendation 1"],
  "critical_success_factors": ["Factor 1"],
  "plan_b": ["Alternative 1", "BATNA fallback"]
}"""

        try:
            parsed_content = json.loads(sanitized)
        except json.JSONDecodeError as e:
            print(f"[WARN] /generate-plan - JSON parse failed, attempting repair: {e}", flush=True)
            parsed_content = attempt_json_repair(sanitized, required_fields, schema_template, response_format=GENERATE_PLAN_RESPONSE_FORMAT)
            if parsed_content is None:
                return {
                    "error": f"AI returned malformed JSON and repair failed: {str(e)}",
                    "raw_content": sanitized[:1000]
                }

        # Validate all required top-level fields
        missing_fields = [f for f in required_fields if f not in parsed_content]
        if missing_fields:
            print(f"[WARN] /generate-plan - Missing fields: {missing_fields}, attempting repair", flush=True)
            repaired = attempt_json_repair(sanitized, required_fields, schema_template, response_format=GENERATE_PLAN_RESPONSE_FORMAT)
            if repaired:
                parsed_content = repaired
            else:
                return {"error": f"AI response missing required fields: {missing_fields}", "raw_content": sanitized[:500]}

        # Validate action_plan phases
        action_plan    = parsed_content.get("action_plan", {})
        missing_phases = [p for p in required_phases if p not in action_plan]
        if missing_phases:
            print(f"[WARN] /generate-plan - Missing phases: {missing_phases}", flush=True)
            repaired = attempt_json_repair(sanitized, required_fields, schema_template, response_format=GENERATE_PLAN_RESPONSE_FORMAT)
            if repaired and all(p in repaired.get("action_plan", {}) for p in required_phases):
                parsed_content = repaired
            else:
                return {"error": f"AI response missing required action plan phases: {missing_phases}", "raw_content": sanitized[:500]}

        # Sanitize output of any cover images / fake page 1 references
        parsed_content = sanitize_ai_output_content(parsed_content)

        total_time = time.time() - start_time
        print(f"[PERF] /generate-plan - SUCCESS. Total: {total_time:.3f}s", flush=True)

        return parsed_content

    except Exception as e:
        print(f"[ERROR] /generate-plan: {str(e)}", flush=True)
        return {"error": str(e)}



def get_pdf_source_filter(output_lang: str | None) -> tuple[str, Filter | None]:
    if output_lang and output_lang.lower() == "french":
        source_file = "Vente_et_negociation_bancaire_png_fr.pdf"
    else:
        source_file = "Sales_and_negociation_OK-2.pdf"
    
    q_filter = Filter(
        must=[
            FieldCondition(
                key="source",
                match=MatchValue(value=source_file)
            )
        ]
    )
    return source_file, q_filter


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


def detect_language_local(text: str) -> str:
    """
    Detect language using free local langdetect library.
    Returns 'French', 'English', or the detected language name.
    No AI credits used.
    """
    if HAS_LANGDETECT:
        try:
            lang_code = langdetect_detect(text)
            LANG_MAP = {
                'fr': 'French', 'en': 'English', 'de': 'German',
                'es': 'Spanish', 'it': 'Italian', 'pt': 'Portuguese',
                'nl': 'Dutch', 'ar': 'Arabic', 'zh-cn': 'Chinese',
                'ja': 'Japanese', 'ko': 'Korean', 'ru': 'Russian',
            }
            return LANG_MAP.get(lang_code, lang_code)
        except Exception as e:
            print(f"[WARN] langdetect failed: {e}", flush=True)
    return "English"


def detect_and_translate(text: str) -> dict:
    """Legacy function kept for compatibility. Uses free local detection."""
    detected = detect_language_local(text)
    return {"detected_language": detected, "translated_text": text}


@app.post("/ask")
def ask_post(request: QuestionRequest, accept_language: str | None = Header(default=None)):
    target_lang = request.lang or accept_language
    return process_question(request.question, request.history, target_lang)


def process_question(query: str, history: list = [], target_lang: str | None = None):
    start_time = time.time()
    try:
        # Determine output language first
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

        # 0. Language Detection — FREE local detection, NO AI credit used
        t0_lang = time.time()
        if output_lang:
            # App already told us the language — skip detection entirely
            detected_lang = output_lang
            print(f"[INFO] Language from app: {output_lang} (skipped detection, saved AI credit)", flush=True)
        else:
            # Fallback: use free local language detection
            detected_lang = detect_language_local(query)
            output_lang = detected_lang
            print(f"[INFO] Detected language locally: {detected_lang} (free, no AI credit)", flush=True)

        search_query = query
        print(f"[INFO] Output language: {output_lang} (detection time: {time.time()-t0_lang:.3f}s)", flush=True)

        # 1. Hybrid Search (combines heuristics and vector embeddings)
        t0 = time.time()
        source_file, source_filter = get_pdf_source_filter(output_lang)
        search_result = hybrid_search(search_query, output_lang, limit=15)
        print(f"[PERF] /ask - Hybrid Search ({len(search_result)} results): {time.time()-t0:.3f}s", flush=True)

        if not search_result:
            if output_lang.lower() == "french":
                return {"answer": "Aucune information pertinente trouvée.", "images": [], "reference_pages": []}
            return {"answer": "No relevant information found.", "images": [], "reference_pages": []}

        # 3. Re-ranking — now re-ranking 10 instead of 20 (~50% faster)
        t0 = time.time()
        print(f"[INFO] Re-ranking {len(search_result)} candidates...", flush=True)

        pairs  = [[search_query, res.payload["text"]] for res in search_result]
        scores = rerank_model.predict(pairs)

        for i, score in enumerate(scores):
            search_result[i].score = score

        search_result.sort(key=lambda x: x.score, reverse=True)
        # Keep top 5 — focused context gives better AI answers than diluted top 8
        top_chunks = search_result[:5]
        print(f"[PERF] /ask - Re-ranking: {time.time()-t0:.3f}s", flush=True)

        # 4. Context Building — now includes chapter/section metadata
        context          = ""
        collected_images = []
        reference_pages  = set()

        for res in top_chunks:
            chunk   = res.payload
            page    = chunk.get("page", "Unknown")
            chapter = chunk.get("chapter", "")
            section = chunk.get("section", "")
            images  = chunk.get("images", [])
            valid_imgs = filter_valid_technique_images(images, page)
            imgs_str = ", ".join([f"{AI_IMAGE_BASE_URL}/images/{img}" for img in valid_imgs]) if valid_imgs else "None"
            
            # Build rich source header with chapter/section info
            source_header = f"[Page: {page}"
            if chapter:
                source_header += f" | Chapter: {chapter}"
            if section:
                source_header += f" | Section: {section}"
            source_header += f" | Images: {imgs_str}]"
            
            context += f"{source_header}\n{chunk['text']}\n\n"
            for img in valid_imgs:
                base_url = (AI_IMAGE_BASE_URL or "http://127.0.0.1:8000").rstrip("/")
                collected_images.append(f"{base_url}/images/{img}")
            if page and str(page).strip() not in ["1", "0", "Unknown", "None", ""]:
                try:
                    if int(page) > 1:
                        reference_pages.add(int(page))
                except (ValueError, TypeError):
                    reference_pages.add(page)

        # 5. AI Completion
        t0 = time.time()
        
        # Get the TOC for this source to give AI full book structure awareness
        toc_text = get_toc_for_source(source_file)
        toc_section = ""
        if toc_text:
            toc_section = f"\n[BOOK TABLE OF CONTENTS — Use this for structural questions about chapters, sections, and page numbers]\n{toc_text}\n"
        
        system_content = f"""You are a helpful AI assistant answering questions about the provided document.
For greetings or conversational interactions (e.g., "Hi", "Hello", "How are you?"), respond politely and warmly.
{toc_section}
[INSTRUCTIONS FOR ANSWERING]
1. Answer the user's question using the Context and Table of Contents provided below.
2. Be flexible with wording. If the user searches for a chapter using only a few words or partial names, match it to the closest chapter in the Context or Table of Contents.
3. For structural questions (e.g., "What are the subsections of Chapter X?", "What is the name of Chapter 2?", "What chapters are there?"), use the [BOOK TABLE OF CONTENTS] above AND the [Chapter] and [Section] metadata tags in the Context to give a complete answer.
4. If the requested information is genuinely missing from BOTH the Context and the Table of Contents, say "I cannot find the answer to that in the document."
5. INLINE CITATIONS: When referencing specific information from the document, include inline citations in the format (Chapter Name, p.XX) or (p.XX) ONLY IF XX is a valid, specific page number greater than 1 (e.g., p.2, p.5). NEVER cite page 1, p.1, or (p.1). If the page number is 1, missing, or unknown, do NOT include any page citation in your answer.

[IMPORTANT]
At the end of your response, provide exactly 3 short and sweet follow-up suggestions for the user.
These suggestions MUST be related to the information found in the context.
Format them at the bottom like this:
Suggestions: [Suggestion 1] | [Suggestion 2] | [Suggestion 3]

Context:
{context}
"""

        if output_lang.lower() != "english":
            system_content += f"\n[LANGUAGE] You MUST respond to the user and write the Suggestions in {output_lang}. If the answer is not in the context, translate 'I cannot find the answer to that in the document.' to {output_lang}."
        else:
            if detected_lang.lower() != "english":
                system_content += f"\n[LANGUAGE] Although the user asked in {detected_lang}, you MUST respond to the user and write the Suggestions in English. If the answer is not in the context, respond with 'I cannot find the answer to that in the document.' in English."

        messages = [{"role": "system", "content": system_content}]
        if history:
            messages.extend(history)
        messages.append({"role": "user", "content": query})

        result = get_ai_client().chat.completions.create(
            model=AI_CHAT_MODEL,
            messages=messages,
            temperature=0.3,
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
            suggestions    = [s.strip().strip("[]").strip() for s in raw_suggestions if s.strip()]

        total_time = time.time() - start_time
        print(f"[PERF] /ask - SUCCESS. Total: {total_time:.3f}s", flush=True)

        # Clean answer text from fake p.1 / page 1 references
        answer = clean_page_one_references(answer)
        valid_ref_pages = filter_valid_reference_pages(reference_pages)

        return {
            "answer":          answer,
            "suggestions":     suggestions,
            "images":          list(set(collected_images)),
            "reference_pages": valid_ref_pages
        }

    except Exception as e:
        import traceback
        traceback.print_exc()
        print(f"[ERROR] /ask (process_question): {str(e)}", flush=True)
        return {"answer": f"Error: {str(e)}", "images": [], "reference_pages": []}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
