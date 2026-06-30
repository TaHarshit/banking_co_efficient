import json
import os
from sentence_transformers import SentenceTransformer
from qdrant_client import QdrantClient
from qdrant_client.models import Distance, VectorParams, PointStruct
from pathlib import Path

# Load models (Free & Local)
print("Loading embedding model (paraphrase-multilingual-MiniLM-L12-v2)...")
model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')

# Qdrant Configuration
# When running inside docker, host should be "qdrant". 
# When running locally for testing, you might need "localhost".
def get_qdrant_client():
    hosts = ["qdrant", "banking-qdrant"]
    for host in hosts:
        try:
            client = QdrantClient(host=host, port=6333, timeout=10)
            client.get_collections()
            return client
        except:
            continue
    raise Exception("Could not connect to Qdrant")

client = get_qdrant_client()

INPUT = "data/chunks.json"
COLLECTION_NAME = "pdf_chunks"

def embed_chunks():
    if not Path(INPUT).exists():
        print(f"Error: {INPUT} not found. Run chunk_pdf.py first.")
        return

    with open(INPUT, "r") as f:
        chunks = json.load(f)

    print(f"Embedding {len(chunks)} chunks...")
    texts = [c["text"] for c in chunks]
    
    # Generate embeddings locally
    vectors = model.encode(texts)
    vector_size = len(vectors[0])

    # Create/Recreate collection in Qdrant
    print(f"Creating Qdrant collection: {COLLECTION_NAME}...")
    client.recreate_collection(
        collection_name=COLLECTION_NAME,
        vectors_config=VectorParams(size=vector_size, distance=Distance.COSINE),
    )

    # Prepare points for upload
    points = []
    for idx, (chunk, vector) in enumerate(zip(chunks, vectors)):
        points.append(PointStruct(
            id=idx,
            vector=vector.tolist(),
            payload=chunk
        ))

    # Batch upload
    print("Uploading to Qdrant...")
    client.upsert(collection_name=COLLECTION_NAME, points=points)

    print("✅ Successfully embedded and uploaded to Qdrant!")

if __name__ == "__main__":
    embed_chunks()
