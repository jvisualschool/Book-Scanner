#!/bin/bash

# BOOKSCAN 프로젝트 배포 스크립트
# 사용법: ./deploy.sh

# 색상 정의
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== BOOKSCAN 배포 스크립트 ===${NC}"

# server_config.json 파일 읽기
if [ ! -f "server_config.json" ]; then
    echo -e "${RED}❌ server_config.json 파일을 찾을 수 없습니다.${NC}"
    exit 1
fi

# jq를 사용하여 JSON 파싱 (jq가 없으면 설치 안내)
if ! command -v jq &> /dev/null; then
    echo -e "${RED}❌ jq가 설치되어 있지 않습니다.${NC}"
    echo "다음 명령어로 설치하세요: brew install jq"
    exit 1
fi

# 설정 파일에서 정보 읽기
SERVER_IP=$(jq -r '.server_ip' server_config.json)
SSH_USER=$(jq -r '.ssh_user' server_config.json)
SSH_KEY=$(jq -r '.ssh_key_path' server_config.json)
REMOTE_PATH=$(jq -r '.remote_project_path' server_config.json)

echo -e "${BLUE}📡 서버 정보:${NC}"
echo "  - IP: $SERVER_IP"
echo "  - 사용자: $SSH_USER"
echo "  - 원격 경로: $REMOTE_PATH"
echo ""

# SSH 키 경로 확장 (~를 실제 경로로)
SSH_KEY="${SSH_KEY/#\~/$HOME}"

# 배포할 파일 목록 (프로덕션 파일만)
FILES=(
    "index.html"
    "api_books.php"
    "api_reset.php"
    "api_retry_enrich.php"
    "api_vision.php"
    "db_connect.php"
    "config.php"
    "common.php"
    "init_db.sql"
)

# 테스트 파일은 프로덕션 배포에서 제외
# "test_api.php"
# "test_books_api.php"

echo -e "${BLUE}📦 배포할 파일:${NC}"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "  ${GREEN}✓${NC} $file"
    else
        echo -e "  ${RED}✗${NC} $file (파일 없음)"
    fi
done
echo ""

# 사용자 확인
read -p "배포를 진행하시겠습니까? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}배포가 취소되었습니다.${NC}"
    exit 1
fi

# 파일 배포
echo -e "${BLUE}🚀 배포 시작...${NC}"
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${BLUE}📤 $file 업로드 중...${NC}"
        scp -i "$SSH_KEY" "$file" "$SSH_USER@$SERVER_IP:$REMOTE_PATH"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ $file 업로드 완료${NC}"
        else
            echo -e "${RED}✗ $file 업로드 실패${NC}"
        fi
    fi
done

echo ""
echo -e "${GREEN}✅ 배포가 완료되었습니다!${NC}"
echo -e "${BLUE}🌐 웹사이트: https://jvibeschool.org/BOOKSCAN/${NC}"
