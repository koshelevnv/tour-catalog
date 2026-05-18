# Tour Catalog — Project Instructions

## Project Overview

Full-stack tour catalog web application. Test task for Full-stack AI Engineer position.

**Grading:** 50% AI workflow · 35% code quality · 15% usability

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Frontend | Vue 3 + Vike (SSR) + Vite |
| Styling | Tailwind 4 + shadcn-vue |
| DB | PostgreSQL 16 + pgvector |
| Embeddings | FastAPI + all-MiniLM-L6-v2 |
| Infrastructure | Docker Compose on Ubuntu Server VM |

## Environment

| Item | Value |
|---|---|
| Ubuntu server IP | 192.168.2.133 |
| SSH | `ssh user@192.168.2.133` (password: `user`) |
| Project path (server) | `/home/user/tour-catalog/` |
| Project path (Windows) | `Z:\` (network share) |
| Site | http://192.168.2.133:5173 |
| API | http://192.168.2.133:8000 |
| Admin | http://192.168.2.133:5173/admin/login |

## Repo Layout

```
apps/backend/        — Laravel 11
apps/frontend/       — Vue + Vike + Vite
services/embeddings/ — FastAPI microservice
docker-compose.yml
PLAN.md              — implementation stages with status
PROJECT_MAP.md       — full architecture reference
STATUS.md            — current status + recent fixes
GUIDE.md             — user-facing guide
```

## Dev Commands

Сервер доступен по SSH: `ssh user@192.168.2.133` (пароль: `user`).

SSH-ключ (`~/.ssh/id_ed25519`) добавлен в `authorized_keys` на сервере — подключение работает **без пароля** напрямую из Bash:

```bash
ssh user@192.168.2.133 'команда'
```

Все Docker-команды выполняются **на Ubuntu-сервере** (через SSH из Bash или терминал на VM).
Файлы проекта доступны напрямую через `Z:\` (сетевая папка).

```bash
# Подключиться к серверу
ssh user@192.168.2.133   # password: user

# Из /home/user/tour-catalog/ на сервере:
docker compose up -d          # запустить всё
docker compose ps             # статус контейнеров
docker compose down           # остановить
docker compose restart frontend   # перезапустить frontend
docker compose restart backend    # перезапустить backend
```

### Перезапуск после изменений

Файлы редактируются через `Z:\`. После изменений:

| Что изменилось | Команда на сервере |
|---|---|
| `apps/frontend/**` (`.vue`, `.js`, `.css`) | Vite подхватывает автоматически (HMR) |
| `apps/frontend/vite.config.js` | `docker compose restart frontend` |
| `apps/backend/.env` или `config/**` | `docker compose restart backend` + `php artisan config:clear` |
| `apps/backend/database/migrations/**` | `docker exec tour-catalog-backend-1 php artisan migrate` |
| `services/embeddings/**` | `docker compose restart embeddings` |
| `docker-compose.yml` | `docker compose up -d` |

Сброс базы с нуля:
```bash
docker exec tour-catalog-backend-1 php artisan migrate:fresh --seed
```

## Coding Standards

- All UI text in **Russian**
- No comments unless the WHY is non-obvious
- No `Co-Authored-By` in commits, no AI mentions in code or commits
- Prefer editing existing files over creating new ones
- No backwards-compatibility hacks for removed code

## Architecture Rules

- Auth via Laravel Sanctum (Bearer tokens in `localStorage.admin_token`)
- Public API: `/api/tours`, `/api/tours/meta`, `/api/tour-types`, `/api/tours/search`, `/api/tours/suggest`, `/api/tours/{slug}`
- Admin API: `/api/admin/login`, `/api/admin/tours`, `/api/admin/tour-types`, `/api/admin/tour-variants`, etc.
- Embeddings: FastAPI on port 8001 (Docker: `embeddings:8001`), endpoint `POST /embed`
- Photos stored in `storage/app/public/tours/{id}/`, served via `/storage/` symlink
- Waypoints have sequential `order` field (1, 2, 3…); map uses `ymaps.route()` (not MultiRoute)
- Teleport in Vue pages must use `to="#teleported"` (not `to="body"`) — vike-vue SSR only injects `ssrContext.teleports["#teleported"]`
- **Vite proxy** (since 2026-05-17): `/api` и `/storage` проксируются Vite dev-сервером → `http://backend:8000`. Браузер использует относительные URL, `VITE_API_URL` = пусто
- **Dark theme only**: `:root` имеет тёмные CSS-переменные, `dark` класс на `<html>` и корневом `<div>`
- **CSS variables**: переменные темы используют `oklch()` — писать `var(--primary)`, а не `hsl(var(--primary))`

## Key Files

- `apps/backend/routes/api.php` — all API routes
- `apps/backend/app/Http/Controllers/Api/` — public controllers
- `apps/backend/config/cors.php` — CORS (allowed_origins: ['*'])
- `apps/frontend/vite.config.js` — proxy: /api + /storage → backend:8000
- `apps/frontend/pages/` — Vike SSR pages
- `apps/frontend/src/components/` — Vue components
- `apps/frontend/src/components/RangeSlider.vue` — dual range slider (min/max thumbs, fill between, labels)
- `apps/frontend/src/components/TourFilters.vue` — unified filter component (variant="bar"|"sidebar"), uses RangeSlider for price/duration; filterMeta prop sets slider bounds
- `apps/frontend/src/components/TourMap.vue` — Yandex Maps component (ymaps.route)
- `apps/frontend/src/components/admin/TourForm.vue` — tour create/edit form (AI generation with per-field checkboxes)
- `apps/frontend/src/components/admin/RichTextEditor.vue` — Tiptap-based rich text editor (description field)
- `apps/frontend/src/i18n.js` — all UI strings
- `apps/frontend/assets/main.css` — dark theme by default in :root, Tailwind base, .prose-description
- `apps/frontend/src/api/` — API client modules (browser uses relative URLs via Vite proxy)

## Current Status

See `PLAN.md` for full stage breakdown, `STATUS.md` for recent fixes.

All stages complete: 1–15. Only Stage 0 (GitHub repo + collaborator ver1000000) is pending — done on user command.
