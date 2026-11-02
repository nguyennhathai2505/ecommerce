from sqlalchemy import Column, BigInteger, String, DECIMAL, JSON, DateTime
from sqlalchemy.sql import func
from app.database import Base


class ProductFeature(Base):
    __tablename__ = "product_features"

    id = Column(BigInteger, primary_key=True, autoincrement=True)
    product_id = Column(BigInteger, unique=True, nullable=False, index=True)
    category_id = Column(BigInteger, nullable=True)
    name = Column(String(255), nullable=False)
    slug = Column(String(255), nullable=True)
    product_type = Column(String(20), nullable=False)  # SHOE, CLOTH, ACCESSORY
    base_price = Column(DECIMAL(12, 2), nullable=False)
    description = Column(String(2000), nullable=True)
    colors = Column(JSON, nullable=True)
    sizes = Column(JSON, nullable=True)
    stud_types = Column(JSON, nullable=True)
    silo = Column(String(100), nullable=True)
    sleeve_type = Column(String(20), nullable=True)
    text_for_embedding = Column(String(4000), nullable=True)  # text dùng để tạo embedding
    updated_at = Column(DateTime, server_default=func.now(), onupdate=func.now())