import sys
import subprocess
import os

def run_step(command):
    print(f"\n=============================================")
    print(f"▶ Running: {' '.join(command)}")
    print(f"=============================================")
    result = subprocess.run(command)
    if result.returncode != 0:
        print(f"\n❌ Error executing: {' '.join(command)}")
        print(f"Pipeline stopped due to error.")
        sys.exit(1)

def main():
    # Default PDF if none is provided
    pdf_path = "data/Sales_and_negociation_OK-2.pdf"
    if len(sys.argv) > 1:
        pdf_path = sys.argv[1]

    if not os.path.exists(pdf_path):
        print(f"Error: Could not find PDF at {pdf_path}")
        sys.exit(1)

    # Step 1: Extract Text from the PDF
    run_step(["python", "scripts/extract_pdf.py"])

    # Step 2: Extract Images from all PDFs (filters small icons out)
    run_step(["python", "scripts/extract_images.py"])

    # Step 3: Combine Text and Images into Chunks
    # This step attaches the image filenames to the correct text chunks!
    run_step(["python", "scripts/chunk_pdf.py"])

    # Step 4: Upload everything to Qdrant Database
    # This step actually pushes the text + image metadata into the Vector DB!
    run_step(["python", "scripts/embed_chunks.py"])

    print("\n✅ All processing steps completed successfully! Your database is now up to date.")

if __name__ == "__main__":
    main()
