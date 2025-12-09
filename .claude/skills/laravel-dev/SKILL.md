---
name: laravel-dev
description: Laravel 12, Filament 3, Livewire 3, Sail 기반 풀스택 개발 및 Proxmox 배포 지원. Laravel 프로젝트 생성, Filament 관리자 페이지 구축, Docker Sail 환경 설정, 프로덕션 서버 배포 시 사용하세요.
---

# Laravel Development Skill

## Tech Stack

| Category | Technology | Version |
|----------|------------|---------|
| Framework | Laravel | 12.x |
| Admin Panel | Filament | 3.x |
| UI Framework | Livewire | 3.x |
| CSS | Tailwind CSS | 3.x |
| Database | MariaDB | 10.11 |
| Cache | Redis | latest |
| Mail | Mailpit | latest |
| Container | Docker + Laravel Sail | - |
| Server | Proxmox Ubuntu | 24.04 |
| PHP | PHP | 8.3 |

---

## Laravel Sail Commands

### 기본 명령어
```bash
# 컨테이너 시작/중지
./vendor/bin/sail up -d
./vendor/bin/sail down

# Artisan 명령어
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan make:model Post -mfc
./vendor/bin/sail artisan make:filament-resource Post

# NPM
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build

# Composer
./vendor/bin/sail composer require package/name

# Tinker
./vendor/bin/sail artisan tinker
```

### Sail Alias 설정 (선택)
```bash
alias sail='./vendor/bin/sail'
```

---

## Filament 3 Development

### Resource 생성
```bash
./vendor/bin/sail artisan make:filament-resource Post --generate
./vendor/bin/sail artisan make:filament-resource User --soft-deletes
```

### Widget 생성
```bash
./vendor/bin/sail artisan make:filament-widget StatsOverview --stats-overview
./vendor/bin/sail artisan make:filament-widget LatestOrders --table
```

### Page 생성
```bash
./vendor/bin/sail artisan make:filament-page Settings
./vendor/bin/sail artisan make:filament-page Dashboard --type=dashboard
```

### Filament 구조
```
app/Filament/
├── Resources/
│   ├── PostResource.php
│   └── PostResource/
│       └── Pages/
│           ├── CreatePost.php
│           ├── EditPost.php
│           └── ListPosts.php
├── Widgets/
│   └── StatsOverview.php
└── Pages/
    └── Dashboard.php
```

---

## Livewire 3 Development

### 컴포넌트 생성
```bash
./vendor/bin/sail artisan make:livewire Counter
./vendor/bin/sail artisan make:livewire Forms/ContactForm
```

### Livewire 기본 패턴
```php
<?php

namespace App\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
```

### Blade에서 사용
```blade
<livewire:counter />

{{-- 또는 --}}
@livewire('counter')
```

---

## Code Standards

### URL 생성 규칙
```php
// ❌ 하드코딩 금지
<a href="http://example.com/courses">

// ✅ route() 헬퍼 사용
<a href="{{ route('courses.index') }}">

// ✅ url() 헬퍼 사용
<a href="{{ url('/courses') }}">
```

### Asset 경로
```blade
{{-- ❌ 직접 경로 --}}
<link href="/css/app.css">

{{-- ✅ Vite 사용 --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### HTTPS 강제 설정 (프로덕션 필수)
```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

---

## Database

### MariaDB 사용 (MySQL 대체)
MySQL 8.4는 x86-64-v2 CPU 명령어 필수 → 가상화 환경(Proxmox)에서 오류 발생
→ MariaDB 10.11로 대체

```yaml
# compose.yaml
mysql:
    image: 'mariadb:10.11'  # MySQL 8.4 대신
```

### Migration 명령어
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan migrate:rollback
```

---

## Project Structure

```
laravel-project/
├── app/
│   ├── Filament/          # Filament Resources, Pages, Widgets
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Livewire/          # Livewire Components
│   ├── Models/
│   └── Providers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── components/
│       ├── layouts/
│       └── livewire/
├── routes/
│   ├── web.php
│   └── api.php
├── storage/
├── tests/
├── compose.yaml           # Docker Compose (Sail)
├── .env
└── .env.example
```

---

## References

배포 및 서버 설정은 다음 문서 참조:
- [DEPLOYMENT.md](DEPLOYMENT.md) - 프로덕션 배포 가이드
- [LOCAL-VS-PRODUCTION.md](LOCAL-VS-PRODUCTION.md) - 환경별 설정 차이
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - 문제 해결 가이드
