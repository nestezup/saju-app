# Laravel 로컬 개발 vs 프로덕션 배포 가이드

## 핵심 차이점 요약

| 항목 | 로컬 (macOS) | 프로덕션 (Linux) |
|------|-------------|-----------------|
| 권한 | 자동 처리 | UID 1000 맞춰야 함 |
| HTTPS | 불필요 | 필수 (Mixed Content) |
| APP_ENV | local | production |
| APP_DEBUG | true | false |
| DB | SQLite/MySQL 둘 다 OK | MariaDB 권장 |
| Docker | Docker Desktop | 네이티브 Docker |

---

## 1. 환경별 .env 설정

### 로컬 개발 (.env)
```env
APP_NAME="My App"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# 로컬에서는 설정 불필요 (Docker Desktop이 처리)
# WWWUSER=
# WWWGROUP=
```

### 프로덕션 (.env)
```env
APP_NAME="My App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=강력한비밀번호

# 컨테이너를 root로 실행
WWWUSER=0
WWWGROUP=0

# HTTPS 강제 (추가 보험)
ASSET_URL=https://your-domain.com
```

---

## 2. Docker 권한 차이

### macOS/Windows (Docker Desktop)
- 파일 시스템 가상화 레이어가 권한을 자동 처리
- `./vendor/bin/sail up -d` 바로 실행 가능
- UID/GID 걱정 불필요

### Linux (네이티브 Docker)
- 컨테이너 UID와 호스트 UID가 직접 매핑
- UID 불일치 시 권한 오류 발생
- Sail 기본 UID 1000 ≠ 호스트 사용자 UID → 충돌

### 해결책: root로 컨테이너 실행
```env
# .env에 추가
WWWUSER=0
WWWGROUP=0
```

### 권한 설정 순서 (Linux)
```bash
# 1. 권한 먼저
sudo chown -R root:root .
sudo chmod -R 755 .
sudo chmod -R 777 storage bootstrap/cache

# 2. 그 다음 Sail 실행
./vendor/bin/sail up -d
```

---

## 3. HTTPS 설정

### 왜 필요한가?
- 프로덕션에서 리버스 프록시(Caddy/Nginx) 뒤에서 Laravel은 HTTP로 인식
- 모든 URL이 `http://`로 생성 → Mixed Content 에러

### AppServiceProvider.php
```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

### Middleware (선택사항)
```php
// app/Http/Middleware/ForceHttps.php
public function handle($request, Closure $next)
{
    if (!$request->secure() && app()->environment('production')) {
        return redirect()->secure($request->getRequestUri());
    }
    return $next($request);
}
```

---

## 4. URL 생성 규칙

### 하드코딩 금지
```php
// ❌ 절대 하지 마세요
<a href="http://example.com/courses">
<img src="http://example.com/images/logo.png">
```

### route() 헬퍼 사용
```php
// ✅ 권장
<a href="{{ route('courses.index') }}">
<a href="{{ route('courses.show', $course) }}">
```

### url() 헬퍼 사용
```php
// ✅ 가능
<a href="{{ url('/courses') }}">
<a href="{{ url('/courses/' . $course->id) }}">
```

### asset() 헬퍼
```php
// ✅ 정적 파일
<img src="{{ asset('images/logo.png') }}">
```

---

## 5. Asset 빌드 차이

### 로컬 개발
```bash
./vendor/bin/sail npm run dev  # Vite dev server (HMR)
```

### 프로덕션
```bash
./vendor/bin/sail npm run build  # 최적화된 빌드
```

### Blade 템플릿
```blade
{{-- ❌ 직접 경로 --}}
<link href="/css/app.css">
<script src="/js/app.js"></script>

{{-- ✅ Vite 사용 --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

---

## 6. 캐시 설정

### 로컬 개발
```bash
# 캐시 비활성화 (디버깅 용이)
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear
```

### 프로덕션
```bash
# 캐시 활성화 (성능 최적화)
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
```

---

## 7. 데이터베이스 설정

### 로컬: MySQL 또는 SQLite
```env
# MySQL (Sail 기본)
DB_CONNECTION=mysql
DB_HOST=mysql

# SQLite (간편한 테스트)
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
```

### 프로덕션: MariaDB 권장
```yaml
# compose.yaml
mysql:
    image: 'mariadb:10.11'
```

MySQL 8.4 CPU 오류:
```
Fatal glibc error: CPU does not support x86-64-v2
```
→ Proxmox 가상화 환경에서 발생
→ MariaDB로 해결

---

## 8. 로깅 설정

### 로컬
```env
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### 프로덕션
```env
LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

## 9. 배포 전 체크리스트

### 코드 확인
- [ ] `URL::forceScheme('https')` 추가됨
- [ ] 모든 URL이 `route()` 또는 `url()` 사용
- [ ] 하드코딩된 URL/도메인 없음
- [ ] `.env.example` 업데이트됨
- [ ] 민감한 정보 `.env`에만 존재

### 서버 설정
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] WWWUSER=0, WWWGROUP=0
- [ ] MariaDB 사용
- [ ] 권한 설정 완료

### 빌드 & 최적화
- [ ] `npm run build` 실행
- [ ] `config:cache` 실행
- [ ] `route:cache` 실행
- [ ] `view:cache` 실행

---

## 10. 비유로 이해하기

### Docker 권한 문제
**"손님(컨테이너)이 내 집(호스트)에 들어왔는데 방문을 못 열어요"**

- macOS/Windows = "호텔" → 마스터키(가상화 레이어)로 모든 방 접근 가능
- Linux = "쉐어하우스" → 각 사람(UID)마다 자기 방 열쇠만 있음

**해결책:**
1. 손님에게 주인 열쇠를 줌 (`WWWUSER=0`) ← 권장
2. 모든 방을 공용으로 바꿈 (`chmod 777`) ← 보안 위험
3. 손님 열쇠를 주인 열쇠로 바꿈 (`chown 1000:1000`) ← 복잡
