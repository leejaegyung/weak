from fastapi import APIRouter, Depends
from auth import get_current_user

router = APIRouter(prefix="/api/meta", tags=["meta"])

POSITIONS = ["사원", "주임", "대리", "과장", "차장", "부장", "팀장", "이사", "관리자"]


@router.get("/positions")
def get_positions(user=Depends(get_current_user)):
    return POSITIONS
