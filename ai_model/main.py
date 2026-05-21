from fastapi import FastAPI, UploadFile, File, Form
import shutil
import os
from predict import predict  # segment disabled

app = FastAPI()

UPLOAD_FOLDER = "temp"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)


@app.post("/analyze")
async def analyze(
    crop_type: str = Form(...),
    file: UploadFile = File(...)
):
    file_location = f"{UPLOAD_FOLDER}/{file.filename}"
    with open(file_location, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    result = predict(file_location, crop_type)
    os.remove(file_location)
    return result


