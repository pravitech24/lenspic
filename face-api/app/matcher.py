from __future__ import annotations

from dataclasses import dataclass
from io import BytesIO
from typing import Iterable
import os

import face_recognition
import numpy as np
from PIL import Image, ImageOps


@dataclass(frozen=True)
class MatchCandidate:
    photo_id: int
    score: float


def _normalize_orientation(image: Image.Image) -> Image.Image:
    try:
        return ImageOps.exif_transpose(image)
    except Exception:
        return image


def _image_to_array(image_bytes: bytes) -> np.ndarray:
    with Image.open(BytesIO(image_bytes)) as image:
        image = _normalize_orientation(image)
        return np.array(image.convert('RGB'))


def _resize_if_needed(image: np.ndarray, max_side: int = 1000, max_pixels: int = 1000000) -> np.ndarray:
    height, width = image.shape[:2]
    if width <= 0 or height <= 0:
        return image

    scale = min(1.0, max_side / max(width, height), (max_pixels / max(width * height, 1)) ** 0.5)
    if scale >= 1.0:
        return image

    new_width = max(1, int(round(width * scale)))
    new_height = max(1, int(round(height * scale)))
    return np.array(Image.fromarray(image).resize((new_width, new_height), Image.LANCZOS))


def _encode_faces(image_bytes: bytes) -> list[np.ndarray]:
    image = _image_to_array(image_bytes)
    image = _resize_if_needed(image, max_side=600, max_pixels=400_000)
    locations = face_recognition.face_locations(image, model='hog')
    if not locations:
        return []

    encodings = face_recognition.face_encodings(image, locations, num_jitters=1, model='small')
    return encodings


def _choose_primary_encoding(encodings: list[np.ndarray], locations: list[tuple[int, int, int, int]]) -> np.ndarray | None:
    if not encodings:
        return None

    if len(encodings) == 1 or not locations:
        return encodings[0]

    areas = [
        (bottom - top) * (right - left)
        for top, right, bottom, left in locations
    ]
    largest_index = int(np.argmax(areas))
    return encodings[largest_index]


def _score_from_distance(distance: float, tolerance: float = 0.6) -> float:
    if distance > tolerance:
        return 0.0

    score = 1.0 - distance
    return round(max(0.0, min(1.0, score)), 4)


def _minimum_score() -> float:
    try:
        return float(os.getenv('FACE_RECOGNITION_MIN_SCORE', '0.35'))
    except ValueError:
        return 0.35


def _result_limit() -> int:
    try:
        return max(1, int(os.getenv('FACE_RECOGNITION_RESULT_LIMIT', '3')))
    except ValueError:
        return 3


def match_faces(
    selfie_bytes: bytes,
    photos: Iterable[tuple[int, bytes]],
    minimum_score: float | None = None,
) -> list[MatchCandidate]:
    minimum_score = _minimum_score() if minimum_score is None else minimum_score

    selfie_encodings = _encode_faces(selfie_bytes)
    if not selfie_encodings:
        return []

    candidates: list[MatchCandidate] = []
    for photo_id, photo_bytes in photos:
        photo_encodings = _encode_faces(photo_bytes)
        if not photo_encodings:
            continue

        best_distance = float('inf')
        for photo_encoding in photo_encodings:
            distances = face_recognition.face_distance(selfie_encodings, photo_encoding)
            if distances.size:
                best_distance = min(best_distance, float(np.min(distances)))

        if best_distance == float('inf'):
            continue

        score = _score_from_distance(best_distance)
        if score >= minimum_score:
            candidates.append(MatchCandidate(photo_id=photo_id, score=score))

    candidates.sort(key=lambda candidate: candidate.score, reverse=True)
    return candidates[:_result_limit()]
