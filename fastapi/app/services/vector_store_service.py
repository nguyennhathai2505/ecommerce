from typing import List, Dict, Any, Optional
from qdrant_client import AsyncQdrantClient
from qdrant_client.http import models as qdrant_models
from app.config import get_settings
from app.utils.logger import logger

settings = get_settings()


class VectorStoreService:
    """Quản lý collection và dữ liệu vector trên Qdrant."""

    def __init__(self):
        self.client = AsyncQdrantClient(host=settings.QDRANT_HOST, port=settings.QDRANT_PORT)
        self.collection = settings.QDRANT_COLLECTION
        self.vector_size = settings.QDRANT_VECTOR_SIZE

    async def ensure_collection(self) -> None:
        """Tạo collection nếu chưa tồn tại."""
        try:
            collections = await self.client.get_collections()
            names = [c.name for c in collections.collections]
            if self.collection not in names:
                await self.client.create_collection(
                    collection_name=self.collection,
                    vectors_config=qdrant_models.VectorParams(
                        size=self.vector_size,
                        distance=qdrant_models.Distance.COSINE,
                    ),
                )
                logger.info(f"Qdrant collection '{self.collection}' created")
            else:
                logger.info(f"Qdrant collection '{self.collection}' exists")
        except Exception as e:
            logger.error(f"Qdrant ensure_collection error: {e}")
            raise

    async def upsert_product(
        self,
        product_id: int,
        vector: List[float],
        payload: Dict[str, Any],
    ) -> None:
        """Thêm hoặc cập nhật vector của một sản phẩm."""
        try:
            await self.client.upsert(
                collection_name=self.collection,
                points=[
                    qdrant_models.PointStruct(
                        id=product_id,
                        vector=vector,
                        payload=payload,
                    )
                ],
            )
            logger.info(f"Qdrant upsert product_id={product_id}")
        except Exception as e:
            logger.error(f"Qdrant upsert error: {e}")
            raise

    async def search_similar(
        self,
        vector: List[float],
        limit: int = 20,
        exclude_ids: Optional[List[int]] = None,
    ) -> List[Dict[str, Any]]:
        """Tìm các sản phẩm có vector tương tự."""
        try:
            query_filter = None
            if exclude_ids:
                query_filter = qdrant_models.Filter(
                    must_not=[
                        qdrant_models.HasIdCondition(has_id=exclude_ids),
                    ]
                )

            result = await self.client.search(
                collection_name=self.collection,
                query_vector=vector,
                limit=limit,
                query_filter=query_filter,
                with_payload=True,
            )

            return [
                {
                    "product_id": hit.id,
                    "score": hit.score,
                    "payload": hit.payload or {},
                }
                for hit in result
            ]
        except Exception as e:
            logger.error(f"Qdrant search error: {e}")
            raise

    async def get_product_vector(self, product_id: int) -> Optional[List[float]]:
        """Lấy vector của một sản phẩm."""
        try:
            result = await self.client.retrieve(
                collection_name=self.collection,
                ids=[product_id],
                with_vectors=True,
            )
            if result:
                return result[0].vector
            return None
        except Exception as e:
            logger.error(f"Qdrant retrieve error: {e}")
            raise

    async def delete_product(self, product_id: int) -> None:
        try:
            await self.client.delete(
                collection_name=self.collection,
                points_selector=qdrant_models.PointIdsList(points=[product_id]),
            )
            logger.info(f"Qdrant delete product_id={product_id}")
        except Exception as e:
            logger.error(f"Qdrant delete error: {e}")