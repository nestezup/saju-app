# Laravel Sail Proxmox 배포 가이드

## 서버 요구사항

- Ubuntu 24.04 (Proxmox VM)
- Docker & Docker Compose v2
- GitHub CLI (gh)

---

## 1. 서버 초기 설정

### Docker 설치
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y docker.io docker-compose-v2 gh
sudo systemctl enable docker
sudo systemctl start docker
```

### GitHub 인증
```bash
gh auth login
```

---

## 2. 프로젝트 클론 및 설정

### 저장소 클론
```bash
gh repo clone username/project-name
cd project-name
```

### Vendor 폴더 생성 (Composer 없이)
```bash
docker run --rm \
  -v $(pwd):/var/www/html \
  -w /var/www/html \
  composer:latest install --ignore-platform-reqs --no-dev
```

> **중요**: `vendor/` 폴더가 없으면 `./vendor/bin/sail`을 실행할 수 없음

---

## 3. 환경 설정

### .env 파일 생성
```bash
cp .env.example .env
```

### 프로덕션 .env 설정
```env
APP_NAME="Your App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=강력한비밀번호

# 컨테이너를 root로 실행 (권한 문제 해결)
WWWUSER=0
WWWGROUP=0

# HTTPS 강제
ASSET_URL=https://your-domain.com
```

---

## 4. MySQL → MariaDB 변경

### compose.yaml 수정
```yaml
services:
  mysql:
    image: 'mariadb:10.11'  # MySQL 8.4 대신
    ports:
      - '${FORWARD_DB_PORT:-3306}:3306'
    environment:
      MYSQL_ROOT_PASSWORD: '${DB_PASSWORD}'
      MYSQL_ROOT_HOST: '%'
      MYSQL_DATABASE: '${DB_DATABASE}'
      MYSQL_USER: '${DB_USERNAME}'
      MYSQL_PASSWORD: '${DB_PASSWORD}'
      MYSQL_ALLOW_EMPTY_PASSWORD: 'yes'
    volumes:
      - 'sail-mysql:/var/lib/mysql'
    networks:
      - sail
    healthcheck:
      test:
        - CMD
        - mysqladmin
        - ping
        - '-p${DB_PASSWORD}'
      retries: 3
      timeout: 5s
```

> **왜 MariaDB?**
> MySQL 8.4는 x86-64-v2 CPU 명령어 필수 → Proxmox 가상화 환경에서 오류 발생

---

## 5. 권한 설정

### 올바른 순서 (중요!)
```bash
# 1. 권한 먼저 설정
sudo chown -R root:root .
sudo chmod -R 755 .
sudo chmod -R 777 storage bootstrap/cache

# 2. 그 다음 Sail 실행
./vendor/bin/sail up -d
```

> **주의**: Sail 실행 후 권한 변경하면 컨테이너 재시작 필요

---

## 6. Laravel 초기화

### 컨테이너 시작 후 대기
```bash
./vendor/bin/sail up -d
sleep 25  # MySQL 초기화 대기
```

### Laravel 설정
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --force --seed
```

### 프론트엔드 빌드
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### 캐시 최적화
```bash
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
```

---

## 7. 전체 배포 스크립트

### deploy.sh
```bash
#!/bin/bash
set -e

echo "🚀 Laravel Deployment Script"
echo "=================================="

# 1. Composer 의존성 설치
echo "🎼 Installing Composer dependencies..."
docker run --rm \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    composer:latest install --ignore-platform-reqs --no-dev

# 2. 환경 파일 확인
if [ ! -f .env ]; then
    echo "⚙️  Setting up .env..."
    cp .env.example .env
    echo "WWWUSER=0" >> .env
    echo "WWWGROUP=0" >> .env
fi

# 3. MySQL → MariaDB 변경
echo "🔄 Ensuring MariaDB..."
sed -i "s/mysql:8.4/mariadb:10.11/g" compose.yaml
sed -i "s/mysql:8.0/mariadb:10.11/g" compose.yaml

# 4. 권한 설정
echo "🔐 Setting permissions..."
sudo chown -R root:root .
sudo chmod -R 755 .
sudo chmod -R 777 storage bootstrap/cache

# 5. 컨테이너 시작
echo "🐳 Starting Docker containers..."
./vendor/bin/sail down || true
./vendor/bin/sail up -d
echo "⏳ Waiting for database..."
sleep 25

# 6. Laravel 설정
echo "🔑 Generating application key..."
./vendor/bin/sail artisan key:generate --force

echo "📊 Running migrations..."
./vendor/bin/sail artisan migrate --force --seed

# 7. 프론트엔드 빌드
echo "🎨 Building frontend assets..."
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 8. 캐시 최적화
echo "🧹 Optimizing..."
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache

echo "✅ Deployment complete!"
```

### 실행
```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 8. 배포 체크리스트

### 코드 작성 완료 후
- [ ] `URL::forceScheme('https')` 추가됨
- [ ] 모든 URL이 `route()` 또는 `url()` 사용
- [ ] 하드코딩된 도메인 없음
- [ ] `.env.example` 업데이트됨

### 서버 배포 시
- [ ] `.env` 설정 (APP_ENV=production)
- [ ] compose.yaml MySQL → MariaDB 변경
- [ ] WWWUSER=0, WWWGROUP=0 설정
- [ ] 권한 설정 완료
- [ ] `npm run build` 실행
- [ ] 캐시 최적화 완료

### 배포 후 확인
- [ ] HTTPS 접속 정상
- [ ] 스타일/JS 로드 정상
- [ ] 로그인/회원가입 동작
- [ ] 이미지 업로드 동작

---

## 9. Proxmox 관련 팁

### CPU 타입 설정
MySQL 8.x CPU 오류 방지를 위해:
- VM 종료 → Hardware → Processors → Type: "host" 선택
- 또는 MariaDB 사용 (권장)

### 포트 확인
```bash
sudo netstat -tulpn | grep :80
```
80 포트 사용 중이면 `.env`에서 `APP_PORT` 변경

---

## 10. 로그 확인

```bash
# 실시간 로그
./vendor/bin/sail logs -f

# MySQL 로그만
./vendor/bin/sail logs mysql

# Laravel 로그
tail -f storage/logs/laravel.log
```
