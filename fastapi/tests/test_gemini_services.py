import unittest
from types import SimpleNamespace
from unittest.mock import AsyncMock, MagicMock, patch

from app.services.embedding_service import EmbeddingService
from app.services.llm_service import LLMService


class EmbeddingServiceTests(unittest.IsolatedAsyncioTestCase):
    async def test_embed_text_uses_gemini_and_qdrant_dimension(self):
        client = MagicMock()
        client.aio.models.embed_content = AsyncMock(
            return_value=SimpleNamespace(
                embeddings=[SimpleNamespace(values=[0.1, 0.2])]
            )
        )

        with (
            patch("app.services.embedding_service.genai.Client", return_value=client),
            patch("app.services.embedding_service.settings.QDRANT_VECTOR_SIZE", 2),
        ):
            service = EmbeddingService()
            vector = await service.embed_text("  product description  ")

        self.assertEqual(vector, [0.1, 0.2])
        request = client.aio.models.embed_content.await_args.kwargs
        self.assertEqual(request["contents"], "product description")
        self.assertEqual(request["config"].output_dimensionality, 2)
        self.assertEqual(request["config"].task_type, "RETRIEVAL_DOCUMENT")

    async def test_embed_texts_preserves_empty_entries(self):
        client = MagicMock()
        client.aio.models.embed_content = AsyncMock(
            return_value=SimpleNamespace(
                embeddings=[SimpleNamespace(values=[0.1, 0.2])]
            )
        )

        with (
            patch("app.services.embedding_service.genai.Client", return_value=client),
            patch("app.services.embedding_service.settings.QDRANT_VECTOR_SIZE", 2),
        ):
            service = EmbeddingService()
            vectors = await service.embed_texts(["", "product"])

        self.assertEqual(vectors, [[0.0, 0.0], [0.1, 0.2]])
        request = client.aio.models.embed_content.await_args.kwargs
        self.assertEqual(request["contents"], ["product"])


class LLMServiceTests(unittest.IsolatedAsyncioTestCase):
    async def test_rerank_products_uses_gemini_json_response(self):
        client = MagicMock()
        client.aio.models.generate_content = AsyncMock(
            return_value=SimpleNamespace(
                text='[{"index": 1, "reason": "Cùng phong cách"}]'
            )
        )
        candidates = [
            {"product_id": 10, "score": 0.9, "payload": {"name": "Áo A"}},
            {"product_id": 20, "score": 0.8, "payload": {"name": "Áo B"}},
        ]

        with patch("app.services.llm_service.genai.Client", return_value=client):
            service = LLMService()
            results = await service.rerank_products(
                context="Gợi ý áo tương tự",
                candidates=candidates,
                top_k=1,
            )

        self.assertEqual(results[0]["product_id"], 20)
        self.assertEqual(results[0]["reason"], "Cùng phong cách")
        request = client.aio.models.generate_content.await_args.kwargs
        self.assertEqual(request["model"], service.model)
        self.assertEqual(
            request["config"].response_mime_type,
            "application/json",
        )


if __name__ == "__main__":
    unittest.main()
