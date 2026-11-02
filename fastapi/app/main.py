from contextlib import asynccontextmanager
from typing import AsyncIterator

from fastapi import FastAPI

from app.config import get_settings
from app.routers import health, interactions, products, recommendations
from app.services.cache_service import CacheService
from app.services.embedding_service import EmbeddingService
from app.services.llm_service import LLMService
from app.services.vector_store_service import VectorStoreService

settings = get_settings()


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncIterator[None]:
    cache_service = CacheService()
    vector_store_service = VectorStoreService()
    embedding_service = None
    llm_service = None

    await cache_service.connect()
    if settings.OPENAI_API_KEY.strip():
        embedding_service = EmbeddingService()
        llm_service = LLMService()

    app.state.cache_service = cache_service
    app.state.vector_store_service = vector_store_service
    app.state.embedding_service = embedding_service
    app.state.llm_service = llm_service

    try:
        yield
    finally:
        await cache_service.close()
        await vector_store_service.client.close()
        if embedding_service:
            await embedding_service.client.close()
        if llm_service:
            await llm_service.client.close()


app = FastAPI(
    title="Recommendation Service",
    version="1.0",
    lifespan=lifespan,
)

app.include_router(health.router)
app.include_router(products.router)
app.include_router(interactions.router)
app.include_router(recommendations.router)


@app.get("/")
def root():
    return {"message": "Recommendation Service is running"}