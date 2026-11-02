from datetime import datetime, timezone
from typing import Any, Dict, List, Optional

from fastapi import APIRouter, Depends, HTTPException, Path, Query
from pydantic import ValidationError
from sqlalchemy.orm import Session

from app.config import get_settings
from app.database import get_db
from app.models.product_feature import ProductFeature
from app.models.user_interaction import UserInteraction
from app.routers.dependencies import (
    get_cache_service,
    get_embedding_service,
    get_llm_service,
    get_vector_store_service,
)
from app.schemas.recommendation import RecommendationResponse
from app.services.cache_service import CacheService
from app.services.embedding_service import EmbeddingService
from app.services.llm_service import LLMService
from app.services.vector_store_service import VectorStoreService
from app.utils.logger import logger

router = APIRouter(prefix="/api/v1/recommendations", tags=["recommendations"])
settings = get_settings()


async def _cached_response(
    cache: CacheService,
    key: str,
) -> Optional[RecommendationResponse]:
    value = await cache.get(key)
    if value is None:
        return None

    try:
        response = RecommendationResponse.model_validate(value)
    except ValidationError:
        logger.warning("Ignoring invalid recommendation cache entry: %s", key)
        return None

    return response.model_copy(update={"source": "cache"})


async def _get_product_vector(
    product: ProductFeature,
    embedding_service: EmbeddingService,
    vector_store: VectorStoreService,
) -> List[float]:
    vector = await vector_store.get_product_vector(product.product_id)
    if vector is not None:
        return vector

    text = product.text_for_embedding or EmbeddingService.build_product_text(
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
    vector = await embedding_service.embed_text(text)
    if len(vector) != settings.QDRANT_VECTOR_SIZE:
        raise ValueError(
            f"Embedding dimension {len(vector)} does not match "
            f"Qdrant dimension {settings.QDRANT_VECTOR_SIZE}."
        )

    payload = {
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
    await vector_store.upsert_product(product.product_id, vector, payload)
    return vector


@router.get("/similar/{product_id}", response_model=RecommendationResponse)
async def recommend_similar_products(
    product_id: int = Path(gt=0),
    limit: int = Query(default=5, ge=1, le=50),
    db: Session = Depends(get_db),
    cache: CacheService = Depends(get_cache_service),
    embedding_service: EmbeddingService = Depends(get_embedding_service),
    llm_service: LLMService = Depends(get_llm_service),
    vector_store: VectorStoreService = Depends(get_vector_store_service),
) -> RecommendationResponse:
    cache_key = f"recommendations:similar:{product_id}:{limit}"
    cached = await _cached_response(cache, cache_key)
    if cached is not None:
        return cached

    product = (
        db.query(ProductFeature)
        .filter(ProductFeature.product_id == product_id)
        .one_or_none()
    )
    if product is None:
        raise HTTPException(status_code=404, detail="Product not found.")

    try:
        await vector_store.ensure_collection()
        vector = await _get_product_vector(product, embedding_service, vector_store)
        candidates = await vector_store.search_similar(
            vector=vector,
            limit=max(limit * 4, limit),
            exclude_ids=[product_id],
        )
        items = await llm_service.rerank_products(
            context=f"Find products similar to {product.name} ({product.product_type}).",
            candidates=candidates,
            top_k=limit,
        )
    except Exception as exc:
        logger.exception("Similar product recommendation failed for %s", product_id)
        raise HTTPException(
            status_code=502,
            detail="Similar product recommendations are temporarily unavailable.",
        ) from exc

    response = RecommendationResponse(
        items=items,
        type="similar",
        source="rag",
        generated_at=datetime.now(timezone.utc).isoformat(),
    )
    await cache.set(cache_key, response.model_dump(mode="json"))
    return response


@router.get("/personalized/{user_id}", response_model=RecommendationResponse)
async def recommend_for_user(
    user_id: int = Path(gt=0),
    limit: int = Query(default=5, ge=1, le=50),
    db: Session = Depends(get_db),
    cache: CacheService = Depends(get_cache_service),
    embedding_service: EmbeddingService = Depends(get_embedding_service),
    llm_service: LLMService = Depends(get_llm_service),
    vector_store: VectorStoreService = Depends(get_vector_store_service),
) -> RecommendationResponse:
    cache_key = f"recommendations:personalized:{user_id}:{limit}"
    cached = await _cached_response(cache, cache_key)
    if cached is not None:
        return cached

    interactions = (
        db.query(UserInteraction)
        .filter(UserInteraction.user_id == user_id)
        .order_by(UserInteraction.created_at.desc())
        .limit(20)
        .all()
    )
    product_ids = list(dict.fromkeys(item.product_id for item in interactions))
    products = (
        db.query(ProductFeature)
        .filter(ProductFeature.product_id.in_(product_ids))
        .all()
        if product_ids
        else []
    )
    products_by_id = {product.product_id: product for product in products}
    seed_products = [
        products_by_id[product_id]
        for product_id in product_ids
        if product_id in products_by_id
    ][:3]

    if not seed_products:
        return RecommendationResponse(
            items=[],
            type="personalized",
            source="rag",
            generated_at=datetime.now(timezone.utc).isoformat(),
        )

    try:
        await vector_store.ensure_collection()
        combined: Dict[int, Dict[str, Any]] = {}
        for product in seed_products:
            vector = await _get_product_vector(
                product,
                embedding_service,
                vector_store,
            )
            candidates = await vector_store.search_similar(
                vector=vector,
                limit=max(limit * 4, limit),
                exclude_ids=product_ids,
            )
            for candidate in candidates:
                candidate_id = int(candidate["product_id"])
                current = combined.get(candidate_id)
                if current is None or candidate["score"] > current["score"]:
                    combined[candidate_id] = candidate

        candidates = sorted(
            combined.values(),
            key=lambda candidate: candidate["score"],
            reverse=True,
        )
        context = "Products recently interacted with by the user: " + ", ".join(
            product.name for product in seed_products
        )
        items = await llm_service.rerank_products(
            context=context,
            candidates=candidates,
            top_k=limit,
        )
    except Exception as exc:
        logger.exception("Personalized recommendation failed for user %s", user_id)
        raise HTTPException(
            status_code=502,
            detail="Personalized recommendations are temporarily unavailable.",
        ) from exc

    response = RecommendationResponse(
        items=items,
        type="personalized",
        source="rag",
        generated_at=datetime.now(timezone.utc).isoformat(),
    )
    await cache.set(cache_key, response.model_dump(mode="json"))
    return response