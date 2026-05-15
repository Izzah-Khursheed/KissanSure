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


# ── /segment endpoint DISABLED ──────────────────────────────────────────────
# Uncomment below to re-enable segmentation.
#
# @app.post("/segment")
# async def run_segment(
#     crop_type: str = Form(...),
#     file: UploadFile = File(...)
# ):
#     file_location = f"{UPLOAD_FOLDER}/seg_{file.filename}"
#     with open(file_location, "wb") as buffer:
#         shutil.copyfileobj(file.file, buffer)
#     try:
#         result = segment(file_location, crop_type)
#         os.remove(file_location)
#         return result
#     except NotImplementedError as e:
#         try: os.remove(file_location)
#         except Exception: pass
#         from fastapi.responses import JSONResponse
#         return JSONResponse(status_code=501, content={"error": str(e)})
#     except Exception as e:
#         try: os.remove(file_location)
#         except Exception: pass
#         from fastapi.responses import JSONResponse
#         return JSONResponse(status_code=500, content={"error": str(e)})
