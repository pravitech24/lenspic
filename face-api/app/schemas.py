from typing import Optional

from pydantic import BaseModel, Field


class MatchItem(BaseModel):
    photo_id: int
    score: float = Field(ge=0.0, le=1.0)


class RecognizeResponse(BaseModel):
    group_id: Optional[int] = None
    group_name: Optional[str] = None
    photo_count: int = 0
    matches: list[MatchItem]
