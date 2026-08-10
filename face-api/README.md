# Kwikpic Face Recognition API

This is the Python FastAPI service that matches the Laravel client contract.

## Contract

`POST /recognize`

Multipart fields:
- `selfie`: the uploaded selfie image
- `photos`: one or more candidate group photos, repeated once per file
- `photo_ids`: the Laravel photo IDs in the same order as `photos`, repeated once per file
- `group_id`: optional group id
- `group_name`: optional group name
- `photo_count`: optional total photo count

Backward-compatible aliases are also accepted for `photos[]` and `photo_ids[]`.

Response shape:

```json
{
  "group_id": 1,
  "group_name": "Wedding",
  "photo_count": 10,
  "matches": [
    { "photo_id": 42, "score": 0.97 }
  ]
}
```

## Run locally

```bash
cd face-api
./run.sh
```

From the Laravel app root, you can also run:

```bash
php artisan face-api:start
```

## Run with Docker

```bash
docker compose up --build face-api
```

## Environment

- `FACE_RECOGNITION_API_URL=http://127.0.0.1:8001`
- `FACE_RECOGNITION_MATCH_PATH=/recognize`
- `FACE_RECOGNITION_TIMEOUT=90`
- `FACE_RECOGNITION_API_TOKEN=`

## Notes

The shipped matcher is a deterministic scaffold so the API is runnable right away. Replace `app/matcher.py` with your actual face embedding and similarity pipeline when you are ready for production face recognition.
