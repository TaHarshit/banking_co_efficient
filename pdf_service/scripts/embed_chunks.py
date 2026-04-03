import json
import numpy as np
import faiss
from openai import OpenAI

client = OpenAI()

INPUT = "data/chunks.json"
FAISS_INDEX = "vectorstore/index.faiss"
EMBED_STORE = "embeddings/vectors.npy"
META_STORE = "embeddings/meta.json"

def embed_chunks():
    with open(INPUT, "r") as f:
        chunks = json.load(f)

    texts = [c["text"] for c in chunks]

    response = client.embeddings.create(
        model="text-embedding-3-small",
        input=texts
    )

    vectors = np.array([d.embedding for d in response.data]).astype("float32")

    dimension = vectors.shape[1]
    index = faiss.IndexFlatL2(dimension)
    index.add(vectors)

    faiss.write_index(index, FAISS_INDEX)
    np.save(EMBED_STORE, vectors)

    with open(META_STORE, "w") as f:
        json.dump(chunks, f, indent=2)

    print("Embeddings + FAISS index created!")

if __name__ == "__main__":
    embed_chunks()

