from typing import List, Optional
from google import genai
from google.genai import types
from tenacity import retry, stop_after_attempt, wait_exponential
from app.config import get_settings
from app.utils.logger import logger

settings = get_settings()


class EmbeddingService:
    """Sinh embedding vector cho text bằng Gemini."""

    def __init__(self):
        self._sdk_client = genai.Client(api_key=settings.GEMINI_API_KEY)
        self.client = self._sdk_client.aio
        self.model = settings.GEMINI_EMBEDDING_MODEL

    async def close(self) -> None:
        await self.client.aclose()
        self._sdk_client.close()

    @retry(
        stop=stop_after_attempt(3),
        wait=wait_exponential(multiplier=1, min=2, max=10),
    )
    async def embed_text(self, text: str) -> List[float]:
        """Trả về vector embedding cho một đoạn text."""
        if not text or not text.strip():
            # vector 0 nếu text rỗng
            return [0.0] * settings.QDRANT_VECTOR_SIZE

        try:
            response = await self.client.models.embed_content(
                model=self.model,
                contents=text.strip()[:8000],
                config=types.EmbedContentConfig(
                    task_type="RETRIEVAL_DOCUMENT",
                    output_dimensionality=settings.QDRANT_VECTOR_SIZE,
                ),
            )
            if not response.embeddings or response.embeddings[0].values is None:
                raise ValueError("Gemini returned no embedding.")
            vector = response.embeddings[0].values
            logger.info(f"Embedding generated, dim={len(vector)}")
            return vector
        except Exception as e:
            logger.error(f"Embedding error: {e}")
            raise

    async def embed_texts(self, texts: List[str]) -> List[List[float]]:
        """Sinh embedding cho nhiều text một lúc (batch)."""
        if not texts:
            return []

        non_empty_texts = [
            (index, text.strip()[:8000])
            for index, text in enumerate(texts)
            if text and text.strip()
        ]
        if not non_empty_texts:
            return [[0.0] * settings.QDRANT_VECTOR_SIZE for _ in texts]

        try:
            response = await self.client.models.embed_content(
                model=self.model,
                contents=[text for _, text in non_empty_texts],
                config=types.EmbedContentConfig(
                    task_type="RETRIEVAL_DOCUMENT",
                    output_dimensionality=settings.QDRANT_VECTOR_SIZE,
                ),
            )
            embeddings = response.embeddings or []
            if len(embeddings) != len(non_empty_texts):
                raise ValueError("Gemini returned an unexpected number of embeddings.")

            results = [[0.0] * settings.QDRANT_VECTOR_SIZE for _ in texts]
            for (index, _), embedding in zip(non_empty_texts, embeddings):
                if embedding.values is None:
                    raise ValueError("Gemini returned an empty embedding.")
                results[index] = embedding.values
            return results
        except Exception as e:
            logger.error(f"Batch embedding error: {e}")
            raise

    @staticmethod
    def build_product_text(
        name: str,
        product_type: str,
        base_price: float,
        category_name: Optional[str] = None,
        colors: Optional[List[str]] = None,
        sizes: Optional[List[str]] = None,
        stud_types: Optional[List[str]] = None,
        silo: Optional[str] = None,
        sleeve_type: Optional[str] = None,
        description: Optional[str] = None,
    ) -> str:
        """Xây dựng text mô tả sản phẩm để đưa vào embedding."""
        parts = [f"Tên sản phẩm: {name}"]
        parts.append(f"Loại: {product_type}")
        if category_name:
            parts.append(f"Danh mục: {category_name}")
        parts.append(f"Giá: {int(base_price):,} VNĐ".replace(",", "."))
        if colors:
            parts.append(f"Màu sắc: {', '.join(colors)}")
        if sizes:
            parts.append(f"Kích cỡ: {', '.join(sizes)}")
        if stud_types:
            parts.append(f"Loại đinh: {', '.join(stud_types)}")
        if silo:
            parts.append(f"Dòng sản phẩm: {silo}")
        if sleeve_type:
            label = "Tay ngắn" if sleeve_type == "SHORT" else "Tay dài"
            parts.append(f"Kiểu tay: {label}")
        if description:
            # lấy 500 ký tự đầu của mô tả
            parts.append(f"Mô tả: {description[:500]}")
        return ". ".join(parts)