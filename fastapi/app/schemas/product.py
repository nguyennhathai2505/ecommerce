from pydantic import BaseModel, Field
from typing import Optional, List


class ProductSyncRequest(BaseModel):
    product_id: int
    category_id: Optional[int] = None
    name: str
    slug: Optional[str] = None
    product_type: str
    base_price: float
    description: Optional[str] = None
    colors: Optional[List[str]] = None
    sizes: Optional[List[str]] = None
    stud_types: Optional[List[str]] = None
    silo: Optional[str] = None
    sleeve_type: Optional[str] = None


class ProductSyncResponse(BaseModel):
    success: bool
    message: str
    product_id: int
    embedding_done: bool = False