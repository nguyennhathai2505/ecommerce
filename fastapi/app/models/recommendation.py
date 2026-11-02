from sqlalchemy import Column, BigInteger, String, Float, DateTime, JSON
from sqlalchemy.sql import func
from app.database import Base


class Recommendation(Base):
    __tablename__ = "recommendations"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    user_id = Column(BigInteger, nullable=True, index=True)
    product_id = Column(BigInteger, nullable=False, index=True)
    recommendation_type = Column(String(30), nullable=False)  # similar, personalized, trending, also_bought
    score = Column(Float, default=0.0)
    reason = Column(String(500), nullable=True)
    metadata_json = Column(JSON, nullable=True)
    created_at = Column(DateTime, server_default=func.now())
    expires_at = Column(DateTime, nullable=True)