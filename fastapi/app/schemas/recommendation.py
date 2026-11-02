from typing import List, Optional, Dict, Any
from pydantic import BaseModel, Field


class RecommendationItem(BaseModel):
    product_id: int
    score: float = 0.0
    reason: Optional[str] = None
    metadata: Optional[Dict[str, Any]] = None


class RecommendationResponse(BaseModel):
    items: List[RecommendationItem]
    type: str
    source: str = "rag"  # rag, cache
    generated_at: Optional[str] = None