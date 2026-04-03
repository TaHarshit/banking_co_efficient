import json
import numpy as np
import faiss
from sentence_transformers import SentenceTransformer

# Use the same local embedding model as the server
MODEL_NAME = "all-MiniLM-L6-v2"

INPUT = "../data/chunks.json"
FAISS_INDEX = "../vectorstore/index.faiss"
EMBED_STORE = "../embeddings/vectors.npy"
META_STORE = "../embeddings/meta.json"

def embed_chunks():
    print(f"[INFO] Loading embedding model: {MODEL_NAME}")
    model = SentenceTransformer(MODEL_NAME)

    with open(INPUT, "r") as f:
        chunks = json.load(f)

    texts = [c["text"] for c in chunks]

    print(f"[INFO] Embedding {len(texts)} chunks...")
    vectors = model.encode(texts, show_progress_bar=True)
    vectors = np.array(vectors).astype("float32")

    dimension = vectors.shape[1]
    index = faiss.IndexFlatL2(dimension)
    index.add(vectors)

    faiss.write_index(index, FAISS_INDEX)
    np.save(EMBED_STORE, vectors)

    with open(META_STORE, "w") as f:
        json.dump(chunks, f, indent=2)

    print(f"[INFO] Done! Created FAISS index with {len(texts)} vectors (dim={dimension})")

if __name__ == "__main__":
    embed_chunks()
