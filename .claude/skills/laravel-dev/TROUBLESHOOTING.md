# Laravel Sail 트러블슈팅 가이드

## 1. 스타일/CSS가 적용 안 됨

### 증상
- 페이지는 로드되지만 스타일이 없음
- 브라우저 콘솔에 Mixed Content 오류
- `http://` 리소스가 `https://` 페이지에서 차단됨

### 해결 순서

**Step 1: APP_ENV 확인**
```bash
# .env
APP_ENV=production
```

**Step 2: HTTPS 강제 설정**
```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

**Step 3: 에셋 빌드**
```bash
./vendor/bin/sail npm run build
```

**Step 4: 캐시 클리어**
```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
```

**Step 5: 브라우저 캐시 클리어**
- Ctrl+Shift+R (또는 Cmd+Shift+R)

---

## 2. Permission denied 오류

### 증상
```
file_put_contents(/var/www/html/storage/logs/laravel.log):
Failed to open stream: Permission denied
```

### 원인
- Linux에서 컨테이너 UID와 호스트 UID 불일치
- storage/, bootstrap/cache/ 쓰기 권한 없음

### 해결

**방법 1: root로 컨테이너 실행 (권장)**
```env
# .env
WWWUSER=0
WWWGROUP=0
```

**방법 2: 권한 직접 설정**
```bash
sudo chown -R 1000:1000 .
sudo chmod -R 777 storage bootstrap/cache
```

**중요: 권한 설정 후 컨테이너 재시작**
```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

---

## 3. MySQL CPU 오류 (Proxmox)

### 증상
```
Fatal glibc error: CPU does not support x86-64-v2
```

### 원인
- MySQL 8.4는 x86-64-v2 CPU 명령어 필수
- Proxmox 가상화 환경에서 호스트 CPU 플래그가 전달되지 않음
- MySQL 8.0.34 이후 버전도 동일

### 해결

**compose.yaml 수정**
```yaml
mysql:
    image: 'mariadb:10.11'  # MySQL 대신 MariaDB
```

또는 sed 명령어:
```bash
sed -i "s/mysql:8.4/mariadb:10.11/g" compose.yaml
sed -i "s/mysql:8.0/mariadb:10.11/g" compose.yaml
```

**컨테이너 재생성**
```bash
./vendor/bin/sail down -v  # 볼륨도 삭제
./vendor/bin/sail up -d
```

---

## 4. MySQL 연결 안 됨

### 증상
```
SQLSTATE[HY000] [2002] Connection refused
```

### 해결 순서

**Step 1: 컨테이너 상태 확인**
```bash
./vendor/bin/sail ps
```

**Step 2: MySQL 로그 확인**
```bash
docker logs $(docker ps -qf "name=mysql")
```

**Step 3: MySQL 초기화 대기**
```bash
# MySQL 초기화에 20-30초 소요
sleep 25
./vendor/bin/sail artisan migrate
```

**Step 4: .env DB 설정 확인**
```env
DB_CONNECTION=mysql
DB_HOST=mysql        # localhost가 아님!
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

---

## 5. Vite manifest not found

### 증상
```
Vite manifest not found at: /var/www/html/public/build/manifest.json
```

### 원인
- `npm run build`가 실행되지 않음
- node_modules가 없음

### 해결
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

---

## 6. Sail 명령어가 없음

### 증상
```
-bash: ./vendor/bin/sail: No such file or directory
```

### 원인
- vendor/ 폴더가 없음 (Git에서 제외됨)
- Composer 의존성 미설치

### 해결
```bash
docker run --rm \
  -v $(pwd):/var/www/html \
  -w /var/www/html \
  composer:latest install --ignore-platform-reqs
```

---

## 7. 포트 충돌

### 증상
```
Error starting userland proxy: listen tcp4 0.0.0.0:80: bind: address already in use
```

### 해결

**Step 1: 사용 중인 포트 확인**
```bash
sudo netstat -tulpn | grep :80
```

**Step 2: .env에서 포트 변경**
```env
APP_PORT=8080
```

**Step 3: 컨테이너 재시작**
```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

---

## 8. Filament 로그인 안 됨

### 증상
- 관리자 패널 접근 불가
- 로그인 후 리다이렉트 루프

### 해결

**Step 1: Filament 사용자 생성**
```bash
./vendor/bin/sail artisan make:filament-user
```

**Step 2: 캐시 클리어**
```bash
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
```

**Step 3: 세션 드라이버 확인**
```env
SESSION_DRIVER=database  # 또는 file
```

---

## 9. Livewire 컴포넌트 안 보임

### 증상
- `<livewire:component-name />` 렌더링 안 됨
- JavaScript 오류

### 해결

**Step 1: Livewire 스크립트 확인**
```blade
{{-- layouts/app.blade.php --}}
@livewireStyles
...
@livewireScripts
```

**Step 2: 캐시 클리어**
```bash
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan livewire:discover
```

**Step 3: 에셋 재빌드**
```bash
./vendor/bin/sail npm run build
```

---

## 10. 컨테이너 디버깅

### 실시간 로그 확인
```bash
./vendor/bin/sail logs -f
```

### 특정 서비스 로그
```bash
./vendor/bin/sail logs mysql
./vendor/bin/sail logs redis
./vendor/bin/sail logs laravel.test
```

### Laravel 로그 확인
```bash
tail -f storage/logs/laravel.log
```

### 컨테이너 내부 접속
```bash
./vendor/bin/sail shell
./vendor/bin/sail root-shell
```

### 컨테이너 상태 확인
```bash
./vendor/bin/sail ps
docker stats
```

---

## 11. 완전 초기화 (최후의 수단)

모든 것이 꼬였을 때:

```bash
# 1. 모든 컨테이너와 볼륨 삭제
./vendor/bin/sail down -v

# 2. Docker 캐시 정리
docker system prune -a

# 3. vendor 삭제 후 재설치
rm -rf vendor node_modules
docker run --rm \
  -v $(pwd):/var/www/html \
  -w /var/www/html \
  composer:latest install --ignore-platform-reqs

# 4. 권한 재설정
sudo chown -R root:root .
sudo chmod -R 755 .
sudo chmod -R 777 storage bootstrap/cache

# 5. 새로 시작
./vendor/bin/sail up -d
sleep 25
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

---

## 빠른 참조 명령어

| 문제 | 명령어 |
|------|--------|
| 캐시 전체 클리어 | `sail artisan optimize:clear` |
| 컨테이너 재시작 | `sail down && sail up -d` |
| 로그 확인 | `sail logs -f` |
| DB 초기화 | `sail artisan migrate:fresh --seed` |
| 에셋 재빌드 | `sail npm run build` |
| 컨테이너 접속 | `sail shell` |
