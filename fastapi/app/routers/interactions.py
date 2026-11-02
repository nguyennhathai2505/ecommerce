from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.exc import SQLAlchemyError
from sqlalchemy.orm import Session

from app.database import get_db
from app.models.user_interaction import UserInteraction
from app.routers.dependencies import get_cache_service
from app.schemas.interaction import InteractionCreate, InteractionResponse
from app.services.cache_service import CacheService
from app.utils.logger import logger

router = APIRouter(prefix="/api/v1/interactions", tags=["interactions"])


@router.post("", response_model=InteractionResponse, status_code=201)
async def create_interaction(
    request: InteractionCreate,
    db: Session = Depends(get_db),
    cache: CacheService = Depends(get_cache_service),
) -> InteractionResponse:
    interaction = UserInteraction(
        user_id=request.user_id,
        product_id=request.product_id,
        interaction_type=request.interaction_type,
        weight=request.weight,
    )

    try:
        db.add(interaction)
        db.commit()
        db.refresh(interaction)
    except SQLAlchemyError as exc:
        db.rollback()
        logger.exception("Interaction database write failed")
        raise HTTPException(
            status_code=500,
            detail="Interaction could not be saved.",
        ) from exc

    await cache.delete_pattern(f"recommendations:personalized:{request.user_id}:*")
    return InteractionResponse(
        success=True,
        message="Interaction recorded successfully.",
        interaction_id=interaction.id,
    )