from typing import Optional
import os
import secrets

from fastapi import FastAPI, HTTPException, Request, UploadFile

from app.matcher import match_faces
from app.schemas import MatchItem, RecognizeResponse

app = FastAPI(title="LensPic Face Recognition API", version="0.1.0")
API_TOKEN = os.getenv("FACE_RECOGNITION_API_TOKEN", "")


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/recognize", response_model=RecognizeResponse)
async def recognize(request: Request) -> RecognizeResponse:
    if not API_TOKEN:
        raise HTTPException(status_code=503, detail="Face service authentication is not configured.")
    supplied = request.headers.get("authorization", "").removeprefix("Bearer ").strip()
    if not supplied or not secrets.compare_digest(supplied, API_TOKEN):
        raise HTTPException(status_code=401, detail="Unauthorized.")
    form = await request.form()

    selfie = form.get("selfie")
    if selfie is None or not hasattr(selfie, "read"):
        raise HTTPException(status_code=422, detail="selfie is required.")

    photos = form.getlist("photos") or form.getlist("photos[]")
    photo_ids_raw = form.getlist("photo_ids") or form.getlist("photo_ids[]")

    group_id_raw = form.get("group_id")
    group_name = form.get("group_name")
    photo_count_raw = form.get("photo_count")

    group_id: Optional[int] = int(group_id_raw) if group_id_raw not in (None, "") else None
    photo_count: Optional[int] = int(photo_count_raw) if photo_count_raw not in (None, "") else None

    if not photos:
        raise HTTPException(status_code=422, detail="At least one photo is required.")

    photo_ids = [int(photo_id) for photo_id in photo_ids_raw if str(photo_id).strip() != ""]

    if photo_ids and len(photo_ids) != len(photos):
        raise HTTPException(status_code=422, detail="photo_ids[] must match photos[] one-to-one.")

    selfie_bytes = await selfie.read()
    photo_payloads: list[tuple[int, bytes]] = []

    for index, photo in enumerate(photos):
        photo_bytes = await photo.read()
        photo_id = photo_ids[index] if photo_ids else index + 1
        photo_payloads.append((photo_id, photo_bytes))

    matches = match_faces(selfie_bytes, photo_payloads)

    return RecognizeResponse(
        group_id=group_id,
        group_name=group_name,
        photo_count=photo_count or len(photos),
        matches=[MatchItem(photo_id=match.photo_id, score=match.score) for match in matches],
    )
