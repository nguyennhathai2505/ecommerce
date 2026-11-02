from sqlalchemy import Column, BigInteger, Integer, String, Float, DateTime, Index
from sqlalchemy.sql import func
from app.database import Base


class UserInteraction(Base):
    __tablename__ = "user_interactions"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    user_id = Column(BigInteger, nullable=False, index=True)
    product_id = Column(BigInteger, nullable=False, index=True)
    interaction_type = Column(String(30), nullable=False)  # view, click, add_to_cart, purchase, wishlist, rating
    weight = Column(Float, default=1.0)
    created_at = Column(DateTime, server_default=func.now(), index=True)

    __table_args__ = (
        Index("idx_user_product", "user_id", "product_id"),
        Index("idx_interaction_type", "interaction_type"),
    )