import json
from typing import List, Dict, Any
from openai import AsyncOpenAI
from tenacity import retry, stop_after_attempt, wait_exponential
from app.config import get_settings
from app.utils.logger import logger

settings = get_settings()


class LLMService:
    """Gọi LLM để rerank / chọn lọc kết quả cuối cùng từ danh sách ứng viên."""

    def __init__(self):
        self.client = AsyncOpenAI(api_key=settings.OPENAI_API_KEY)
        self.model = settings.OPENAI_CHAT_MODEL

    @retry(
        stop=stop_after_attempt(2),
        wait=wait_exponential(multiplier=1, min=2, max=8),
    )
    async def rerank_products(
        self,
        context: str,
        candidates: List[Dict[str, Any]],
        top_k: int = 5,
    ) -> List[Dict[str, Any]]:
        """
        Gửi danh sách ứng viên (từ Qdrant) cho LLM để chọn ra top_k phù hợp nhất.

        Args:
            context: ngữ cảnh (ví dụ: "Sản phẩm hiện tại: Giày Nike Mercurial size 40")
            candidates: danh sách sản phẩm ứng viên (payload từ Qdrant)
            top_k: số sản phẩm cuối cùng cần giữ lại
        """
        if not candidates:
            return []

        # Tạo mô tả ngắn gọn cho từng ứng viên
        candidates_text = []
        for idx, c in enumerate(candidates):
            p = c.get("payload", {})
            candidates_text.append(
                f"[{idx}] ID={c['product_id']} | "
                f"{p.get('name', 'N/A')} | "
                f"Loại={p.get('product_type', 'N/A')} | "
                f"Giá={p.get('base_price', 0)} | "
                f"Màu={p.get('colors', [])} | "
                f"Size={p.get('sizes', [])} | "
                f"Score={c.get('score', 0):.3f}"
            )
        candidates_block = "\n".join(candidates_text)

        prompt = f"""Bạn là trợ lý gợi ý sản phẩm thời trang nam. Dựa vào ngữ cảnh và danh sách ứng viên, hãy chọn ra {top_k} sản phẩm PHÙ HỢP NHẤT.

NGỮ CẢNH:
{context}

DANH SÁCH ỨNG VIÊN:
{candidates_block}

Yêu cầu:
- Chọn đúng {top_k} sản phẩm (nếu ít hơn thì chọn hết).
- Ưu tiên sản phẩm cùng loại, cùng phong cách, mức giá tương đồng.
- Trả về ĐÚNG định dạng JSON array, không có markdown:
[
  {{"index": 0, "reason": "Lý do ngắn gọn"}},
  ...
]"""

        try:
            response = await self.client.chat.completions.create(
                model=self.model,
                messages=[
                    {"role": "system", "content": "Bạn là chuyên gia gợi ý thời trang, chỉ trả về JSON array hợp lệ."},
                    {"role": "user", "content": prompt},
                ],
                temperature=0.3,
                response_format={"type": "json_object"},
            )

            content = response.choices[0].message.content or "{}"
            try:
                parsed = json.loads(content)
                # Hỗ trợ cả dạng {"items": [...]} và {"result": [...]}
                if isinstance(parsed, dict):
                    for key in ("items", "result", "results", "data"):
                        if key in parsed and isinstance(parsed[key], list):
                            parsed = parsed[key]
                            break
                    else:
                        parsed = []
            except json.JSONDecodeError:
                logger.error(f"LLM JSON parse error: {content}")
                parsed = []

            results = []
            for item in parsed:
                idx = item.get("index")
                if idx is None or not (0 <= idx < len(candidates)):
                    continue
                candidate = candidates[idx]
                results.append(
                    {
                        "product_id": candidate["product_id"],
                        "score": float(candidate.get("score", 0.0)),
                        "reason": item.get("reason", ""),
                        "metadata": candidate.get("payload", {}),
                    }
                )

            # Nếu LLM trả ít hơn top_k, bổ sung từ ứng viên đầu
            if len(results) < top_k:
                used_ids = {r["product_id"] for r in results}
                for c in candidates:
                    if c["product_id"] in used_ids:
                        continue
                    results.append(
                        {
                            "product_id": c["product_id"],
                            "score": float(c.get("score", 0.0)),
                            "reason": "Fallback theo thứ tự similarity",
                            "metadata": c.get("payload", {}),
                        }
                    )
                    if len(results) >= top_k:
                        break

            return results[:top_k]

        except Exception as e:
            logger.error(f"LLM rerank error: {e}")
            # Fallback: trả top_k ứng viên đầu tiên
            return [
                {
                    "product_id": c["product_id"],
                    "score": float(c.get("score", 0.0)),
                    "reason": "Fallback (LLM error)",
                    "metadata": c.get("payload", {}),
                }
                for c in candidates[:top_k]
            ]