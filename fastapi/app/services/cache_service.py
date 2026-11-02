import json
from typing import Optional, Any
import redis.asyncio as aioredis
from app.config import get_settings
from app.utils.logger import logger

settings = get_settings()


class CacheService:
    """Cache Redis cho kết quả recommendation."""

    def __init__(self):
        self.redis: Optional[aioredis.Redis] = None

    async def connect(self):
        try:
            self.redis = await aioredis.from_url(
                settings.redis_url, encoding="utf-8", decode_responses=True
            )
            await self.redis.ping()
            logger.info("Redis connected")
        except Exception as e:
            logger.error(f"Redis connect error: {e}")

    async def close(self):
        if self.redis:
            await self.redis.close()

    async def get(self, key: str) -> Optional[Any]:
        if not self.redis:
            return None
        try:
            val = await self.redis.get(key)
            if val:
                return json.loads(val)
            return None
        except Exception as e:
            logger.error(f"Redis get error: {e}")
            return None

    async def set(self, key: str, value: Any, ttl: int = 3600) -> None:
        if not self.redis:
            return
        try:
            await self.redis.set(key, json.dumps(value, ensure_ascii=False), ex=ttl)
        except Exception as e:
            logger.error(f"Redis set error: {e}")

    async def delete(self, key: str) -> None:
        if not self.redis:
            return
        try:
            await self.redis.delete(key)
        except Exception as e:
            logger.error(f"Redis delete error: {e}")

    async def delete_pattern(self, pattern: str) -> None:
        if not self.redis:
            return
        try:
            async for key in self.redis.scan_iter(match=pattern):
                await self.redis.delete(key)
        except Exception as e:
            logger.error(f"Redis delete_pattern error: {e}")