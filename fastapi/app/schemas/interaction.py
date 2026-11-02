from pydantic import BaseModel, Field
from typing import Optional
from datetime import datetime


class InteractionCreate(BaseModel):
    user_id: int = Field(..., gt=0)
    product_id: int = Field(..., gt=0)
    interaction_type: str = Field(..., pattern="^(view|click|add_to_cart|purchase|wishlist|rating)$")
    weight: Optional[float] = 1.0


class InteractionResponse(BaseModel):
    success: bool
    message: str
    interaction_id: Optional[int] = None