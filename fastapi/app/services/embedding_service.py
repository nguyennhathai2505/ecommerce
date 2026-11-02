from typing import List, Optional
from openai import AsyncOpenAI
from tenacity import retry, stop_after_attempt, wait_exponential
from app.config import get_settings
from app.utils.logger import logger

settings = get_settings()


class EmbeddingService:
    """Sinh embedding vector cho text bằng OpenAI hoặc API bên thứ 3 tương thích."""

    def __init__(self):
        self.client = AsyncOpenAI(api_key=settings.OPENAI_API_KEY)
        self.model = settings.OPENAI_EMBEDDING_MODEL

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
            response = await self.client.embeddings.create(
                model=self.model,
                input=text.strip()[:8000],
            )
            vector = response.data[0].embedding
            logger.info(f"Embedding generated, dim={len(vector)}")
            return vector
        except Exception as e:
            logger.error(f"Embedding error: {e}")
            raise

    async def embed_texts(self, texts: List[str]) -> List[List[float]]:
        """Sinh embedding cho nhiều text một lúc (batch)."""
        if not texts:
            return []
        try:
            response = await self.client.embeddings.create(
                model=self.model,
                input=[t.strip()[:8000] if t else "" for t in texts],
            )
            return [d.embedding for d in response.data]
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