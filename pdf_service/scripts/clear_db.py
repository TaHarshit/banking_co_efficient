import os
from qdrant_client import QdrantClient
from dotenv import load_dotenv
from pathlib import Path

# Load .env
env_path = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(env_path)

QDRANT_HOST = os.getenv("QDRANT_HOST", "localhost")
COLLECTION_NAME = "pdf_chunks"

def clear_db():
    client = QdrantClient(host=QDRANT_HOST, port=6333)
    
    try:
        print(f"Deleting collection: {COLLECTION_NAME}...")
        client.delete_collection(collection_name=COLLECTION_NAME)
        print("Success: Collection deleted.")
    except Exception as e:
        print(f"Error (maybe it didn't exist?): {str(e)}")

if __name__ == "__main__":
    clear_db()
