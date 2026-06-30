from qdrant_client import QdrantClient

def check():
    c = QdrantClient(host='localhost', port=6333)
    res = c.scroll(collection_name='pdf_chunks', limit=10, with_payload=True)
    for p in res[0]:
        print(f"Source: {p.payload.get('source')}, Page: {p.payload.get('page')}")
        print(f"Text snippet: {p.payload.get('text', '')[:100]}")
        print("---")

if __name__ == '__main__':
    check()
