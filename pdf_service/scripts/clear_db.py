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
    # Try both the service name and the container name
    hosts_to_try = ["qdrant", "banking-qdrant"]
    
    for host in hosts_to_try:
        try:
            print(f"Connecting to Qdrant at {host}:6333...")
            client = QdrantClient(host=host, port=6333, timeout=10)
            
            # Test connection
            client.get_collections()
            print(f"✅ Connected successfully to {host}")
            
            for coll in COLLECTIONS:
                try:
                    print(f"Deleting collection: {coll}...")
                    client.delete_collection(collection_name=coll)
                    print(f"Success: {coll} deleted.")
                except Exception as e:
                    print(f"Skipped {coll}: {str(e)}")
            return # Exit if successful
            
        except Exception as e:
            print(f"❌ Failed to connect to {host}: {str(e)}")
    
    print("FATAL: Could not connect to any Qdrant host.")

if __name__ == "__main__":
    clear_db()
