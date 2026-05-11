import qdrant_client
from qdrant_client import QdrantClient
import os

print(f"Version: {qdrant_client.__version__}")
client = QdrantClient(host="localhost", port=6333)
print(f"Attributes: {dir(client)}")
if hasattr(client, 'search'):
    print("search attribute exists")
else:
    print("search attribute DOES NOT exist")
