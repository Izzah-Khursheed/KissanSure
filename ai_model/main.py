# FastAPI microservice entry point.
# PHP (admin/analyze_ai.php & user/analyze_ai.php) posts crop images here via cURL.
# This server receives ONE image at a time and returns a JSON damage classification.

from fastapi import FastAPI, UploadFile, File, Form
import shutil
import os
from predict import predict  # segment disabled

app = FastAPI()

# Temp folder to hold uploaded images while the model runs; files are deleted right after.
UPLOAD_FOLDER = "temp"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)


# POST /analyze — main endpoint called by PHP once per image (6 times per claim submission).
# Accepts: crop_type (e.g. "wheat"/"rice") and one image file.
# Returns: {"class": "damaged"|"healthy", "confidence": float}
@app.post("/analyze")
async def analyze(
    crop_type: str = Form(...),
    file: UploadFile = File(...)
):
    # Save the uploaded image to disk so predict() can read it
    file_location = f"{UPLOAD_FOLDER}/{file.filename}"
    with open(file_location, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    # Run the MobileNetV2 classifier on the saved image
    result = predict(file_location, crop_type)

    # Delete the temp file immediately after inference to free disk space
    os.remove(file_location)
    return result


