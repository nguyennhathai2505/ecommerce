from fastapi import HTTPException, Request

from app.services.cache_service import CacheService
from app.services.embedding_service import EmbeddingService
from app.services.llm_service import LLMService
from app.services.vector_store_service import VectorStoreService


def get_cache_service(request: Request) -> CacheService:
    return request.app.state.cache_service


def get_embedding_service(request: Request) -> EmbeddingService:
    service = request.app.state.embedding_service
    if service is None:
        raise HTTPException(
            status_code=503,
            detail="Embedding service is unavailable; configure OPENAI_API_KEY.",
        )
    return service


def get_llm_service(request: Request) -> LLMService:
    service = request.app.state.llm_service
    if service is None:
        raise HTTPException(
            status_code=503,
            detail="Recommendation ranking is unavailable; configure OPENAI_API_KEY.",
        )
    return service


def get_vector_store_service(request: Request) -> VectorStoreService:
    return request.app.state.vector_store_service
