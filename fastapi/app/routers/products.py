from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.exc import SQLAlchemyError
from sqlalchemy.orm import Session

from typing import Any, Dict

from app.config import get_settings
from app.database import get_db
from app.models.product_feature import ProductFeature
from app.routers.dependencies import (
    get_cache_service,
    get_embedding_service,
    get_vector_store_service,
)
from app.schemas.product import ProductSyncRequest, ProductSyncResponse
from app.services.cache_service import CacheService
from app.services.embedding_service import EmbeddingService
from app.services.vector_store_service import VectorStoreService
from app.utils.logger import logger

router = APIRouter(prefix="/api/v1/products", tags=["products"])
settings = get_settings()


def _product_payload(product: ProductFeature) -> Dict[str, Any]:
    return {
        "product_id": product.product_id,
        "category_id": product.category_id,
        "name": product.name,
        "slug": product.slug,
        "product_type": product.product_type,
        "base_price": float(product.base_price),
        "description": product.description,
        "colors": product.colors,
        "sizes": product.sizes,
        "stud_types": product.stud_types,
        "silo": product.silo,
        "sleeve_type": product.sleeve_type,
    }


@router.post("/sync", response_model=ProductSyncResponse)
async def sync_product(
    request: ProductSyncRequest,
    db: Session = Depends(get_db),
    cache: CacheService = Depends(get_cache_service),
    embedding_service: EmbeddingService = Depends(get_embedding_service),
    vector_store: VectorStoreService = Depends(get_vector_store_service),
) -> ProductSyncResponse:
    product = (
        db.query(ProductFeature)
        .filter(ProductFeature.product_id == request.product_id)
        .one_or_none()
    )
    fields = request.model_dump()

    if product is None:
        product = ProductFeature(**fields)
    else:
        for field, value in fields.items():
            setattr(product, field, value)

    text_for_embedding = EmbeddingService.build_product_text(
        name=product.name,
        product_type=product.product_type,
        base_price=float(product.base_price),
        colors=product.colors,
        sizes=product.sizes,
        stud_types=product.stud_types,
        silo=product.silo,
        sleeve_type=product.sleeve_type,
        description=product.description,
    )
    product.text_for_embedding = text_for_embedding

    try:
        db.add(product)
        db.commit()
        db.refresh(product)
    except SQLAlchemyError as exc:
        db.rollback()
        logger.exception("Product sync database write failed")
        raise HTTPException(
            status_code=500,
            detail="Product could not be saved.",
        ) from exc

    try:
        await vector_store.ensure_collection()
        vector = await embedding_service.embed_text(text_for_embedding)
        if len(vector) != settings.QDRANT_VECTOR_SIZE:
            raise ValueError(
                f"Embedding dimension {len(vector)} does not match "
                f"Qdrant dimension {settings.QDRANT_VECTOR_SIZE}."
            )
        await vector_store.upsert_product(
            product_id=product.product_id,
            vector=vector,
            payload=_product_payload(product),
        )
    except Exception as exc:
        logger.exception(
            "Product %s was saved but vector indexing failed", product.product_id
        )
        raise HTTPException(
            status_code=502,
            detail="Product was saved, but vector indexing failed; retry the sync.",
        ) from exc

    await cache.delete_pattern("recommendations:*")
    return ProductSyncResponse(
        success=True,
        message="Product synced successfully.",
        product_id=product.product_id,
        embedding_done=True,
    )