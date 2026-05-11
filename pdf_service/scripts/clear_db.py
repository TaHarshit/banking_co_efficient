import os
from qdrant_client import QdrantClient
from dotenv import load_dotenv
from pathlib import Path

# Load .env
env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

QDRANT_HOST = os.getenv("QDRANT_HOST", "qdrant") # Use 'qdrant' for docker

# All collections we want to clear
COLLECTIONS = ["pdf_chunks", "past_cases", "analyzed_cases"]

def clear_db():
    # Use 'qdrant' as host since we are running inside the docker network
    # This ignores any 'localhost' setting in your .env file
    client = QdrantClient(host="qdrant", port=6333)
    
    for coll in COLLECTIONS:
        try:
            print(f"Deleting collection: {coll}...")
            client.delete_collection(collection_name=coll)
            print(f"Success: {coll} deleted.")
        except Exception as e:
            print(f"Skipped {coll}: {str(e)}")

if __name__ == "__main__":
    clear_db()
