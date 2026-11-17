from pydantic_settings import BaseSettings, SettingsConfigDict
from functools import lru_cache
from pathlib import Path
from sqlalchemy.engine import URL
from urllib.parse import quote

ENV_DIR = Path(__file__).resolve().parents[1]


class Settings(BaseSettings):
    APP_NAME: str
    APP_ENV: str
    APP_HOST: str
    APP_PORT: int
    API_ROOT_PATH: str
    DEBUG: bool

    # Database
    DB_HOST: str
    DB_PORT: int
    DB_NAME: str
    DB_USER: str
    DB_PASSWORD: str

    # Redis
    REDIS_HOST: str
    REDIS_PORT: int
    REDIS_DB: int
    REDIS_USERNAME: str
    REDIS_PASSWORD: str
    REDIS_SSL: bool

    # RabbitMQ
    RABBITMQ_HOST: str
    RABBITMQ_PORT: int
    RABBITMQ_USER: str
    RABBITMQ_PASSWORD: str
    RABBITMQ_QUEUE: str

    # Qdrant
    QDRANT_HOST: str
    QDRANT_PORT: int
    QDRANT_COLLECTION: str
    QDRANT_VECTOR_SIZE: int
    QDRANT_HTTPS: bool
    QDRANT_API_KEY: str

    # Gemini
    GEMINI_API_KEY: str
    GEMINI_EMBEDDING_MODEL: str
    GEMINI_CHAT_MODEL: str

    # Laravel
    LARAVEL_API_URL: str
    LARAVEL_API_TOKEN: str

    @property
    def database_url(self) -> URL:
        return URL.create(
            drivername="mysql+pymysql",
            username=self.DB_USER,
            password=self.DB_PASSWORD,
            host=self.DB_HOST,
            port=self.DB_PORT,
            database=self.DB_NAME,
        )

    @property
    def redis_url(self) -> str:
        scheme = "rediss" if self.REDIS_SSL else "redis"
        credentials = ""
        if self.REDIS_PASSWORD:
            username = quote(self.REDIS_USERNAME, safe="") if self.REDIS_USERNAME else ""
            password = quote(self.REDIS_PASSWORD, safe="")
            credentials = f"{username}:{password}@"
        return (
            f"{scheme}://{credentials}{self.REDIS_HOST}:"
            f"{self.REDIS_PORT}/{self.REDIS_DB}"
        )

    @property
    def rabbitmq_url(self) -> str:
        return f"amqp://{self.RABBITMQ_USER}:{self.RABBITMQ_PASSWORD}@{self.RABBITMQ_HOST}:{self.RABBITMQ_PORT}/"

    model_config = SettingsConfigDict(
        env_file=(ENV_DIR / ".env.example", ENV_DIR / ".env"),
        env_file_encoding="utf-8",
        extra="ignore",
    )


@lru_cache
def get_settings() -> Settings:
    return Settings()