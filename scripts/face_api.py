from fastapi import FastAPI, UploadFile, File, Form
import uvicorn
import os
import shutil
import base64
from deepface import DeepFace
import json
import logging

app = FastAPI()

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

TEMP_DIR = "temp_faces"
os.makedirs(TEMP_DIR, exist_ok=True)

@app.post("/verify")
async def verify_face(
    captured_image_base64: str = Form(...),
    reference_image_path: str = Form(...)
):
    try:
        # 1. Save captured image from base64
        captured_path = os.path.join(TEMP_DIR, "captured.jpg")
        header, encoded = captured_image_base64.split(",", 1)
        with open(captured_path, "wb") as f:
            f.write(base64.b64decode(encoded))

        # 2. Perform verification using DeepFace
        # We use 'VGG-Face' or 'Facenet' for performance/accuracy balance
        result = DeepFace.verify(
            img1_path=captured_path,
            img2_path=reference_image_path,
            enforce_detection=True,
            model_name="VGG-Face",
            detector_backend="opencv"
        )

        logger.info(f"Verification result: {result['verified']} (Distance: {result['distance']})")

        return {
            "verified": result["verified"],
            "distance": result["distance"],
            "threshold": result["threshold"],
            "model": result["model"],
            "detector_backend": result["detector_backend"]
        }

    except Exception as e:
        logger.error(f"Error during verification: {str(e)}")
        return {
            "verified": False,
            "error": str(e)
        }

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8001)
