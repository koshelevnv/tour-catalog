# Project Status

## Environment (verified 2026-05-17)

| Item | Value |
|---|---|
| Ubuntu server IP | 192.168.2.133 |
| SSH | `ssh user@192.168.2.133` (password: `user`) |
| Project path (server) | `/home/user/tour-catalog/` |
| Project path (Windows) | `Z:\` (network share) |
| Site | http://192.168.2.133:5173 |
| API | http://192.168.2.133:8000 |

| Tool | Version |
|---|---|
| Docker | 29.4.3 |
| Docker Compose | v5.1.3 |
| Ubuntu | server VM (192.168.2.133) |

## Stages

| # | Stage | Status |
|---|---|---|
| 0 | GitHub repo + collaborator ver1000000 | По команде |
| 1 | Docker + monorepo skeleton | Done |
| 2 | DB migrations + models + seeder | Done |
| 3 | REST API — public catalog + semantic search | Done |
| 4 | REST API — admin auth + tour CRUD + LLM generate | Done |
| 5 | Frontend layout + routing + shadcn-vue theme | Done |
| 6 | Frontend — главная: infinite scroll + категории | Done |
| 7 | Frontend — tour detail page (lightbox, SSR, variants) | Done |
| 8 | Yandex Maps — нумерованные точки + реальный маршрут | Done |
| 9 | Embeddings service (FastAPI) + semantic search | Done |
| 10 | Frontend — admin panel (/admin) | Done |
| 11 | LLM tour generation (bonus) | Done |
| 12 | UX fixes (DateRangePicker, drag-and-drop фото, иконки типов, кнопка +тип) | Done |
| 13 | LLM provider settings (Anthropic + OpenRouter в /admin/settings) | Done |
| 14 | Редактор текстов интерфейса в настройках | Done |
| 15 | UX polish (CORS, .env.local, textarea ИИ, свёрнутые секции) | Done |
| 16 | Миграция на Ubuntu-сервер + bug fixes | Done |

## What's running

Всё работает на Ubuntu-сервере. Запускать команды через SSH или терминал VM.

```bash
ssh user@192.168.2.133   # password: user
cd /home/user/tour-catalog
docker compose up -d
```

| Service | URL |
|---|---|
| Frontend (Vue SSR) | http://192.168.2.133:5173 |
| Backend (Laravel) | http://192.168.2.133:8000 |
| Embeddings (FastAPI) | http://192.168.2.133:8001 |
| PostgreSQL | 192.168.2.133:5432 |
| Redis | 192.168.2.133:6379 |

## Recent changes (2026-05-18 — поиск + настройки)

- **Склонение дней** — `formatDuration` в `i18n.js`: для диапазона всегда `t.tour.days.many` ("дней"), а не `pluralDays(effectiveMax)` — устраняет "от 10 до 23 **дня**" → "от 10 до 23 **дней**".
- **Страница поиска — полный функционал** — результаты поиска (`/?q=`) теперь отображаются в sidebar-лейауте как `/tours`: боковой фильтр, счётчик найденных туров, сортировка, пагинация. `SearchController.php` расширен: принимает `type`, `duration_min/max`, `price_min/max`, `date_from/to`, `sort`, `page`, `per_page`; возвращает пагинированный ответ с `meta`. `TourFilters.vue` и `TourPagination.vue` получили prop `extraParams` для сохранения `?q=` в URL при навигации. `TourPagination` также исправлен: теперь передаёт `date_from`, `date_to`, `sort` в ссылки пагинации (раньше терялись).
- **Релевантность поиска** — `SearchController`: вместо возврата всех туров теперь отбираются только семантически близкие (cosine distance `< 0.75`, до 30 штук) + ILIKE-совпадения. Устраняет ситуацию когда поиск "ора" возвращал все 20 туров.
- **Настройки поиска в админке** — `/admin/settings` → «Сайт» → «Отображение туров»: добавлен блок «Страница поиска» с `search_per_page` (кол-во туров) и `search_load_mode` (пагинация / кнопка «ещё» / бесконечная прокрутка). Бэкенд: `SettingsController` — добавлены в `SETTING_KEYS`, `validate()`, `publicIndex()`. Фронтенд читает `search_per_page` и `search_load_mode` из API настроек.
- **Фикс: API-ключ в форме** — `SettingsController::index()` теперь подставляет `anthropic_api_key` из `config('services.anthropic.key')` (`.env`) если ключ не сохранён в БД — то же поведение что в `TourGenerationController`.

## Recent changes (2026-05-18 вечер)

- **Фото на диск** — `TourPhotoFactory` теперь скачивает изображения с picsum и сохраняет в `storage/app/public/tours/{id}/` через `afterCreating`. Фолбэк на внешний URL при недоступности сети.
- **Фото в форме тура** — `TourForm.vue`: исправлен URL миниатюр для демо-данных (проверка `startsWith('http')` как в `TourCard.vue`). Миниатюры отображаются в блоке загрузки фотографий.
- **Диапазон дней на карточках** — `formatDuration` в `i18n.js`: одно значение → `"5 дней"`, диапазон → `"от 7 до 12 дней"`. Сидер обновлён: 5 туров (Алтай, Камчатка, Путорана, Якутия, Командоры) получили варианты с разными `duration_days`. Цикл сидера поддерживает `duration_days` на уровне варианта.
- **git init** — инициализирован репозиторий на сервере, ветка `main`.

## Recent changes (2026-05-18)

- **Сортировка туров** — на главной и на `/tours` добавлена сортировка: Сначала новые (default), Сначала старые, Сначала дешевые, Сначала дорогие, Длительность меньше/больше, По алфавиту А→Я/Я→А. Бэкенд: `?sort=` в `TourController::index()` (subquery для цены через `MIN(tour_variants.price)`). Фронт: параметр `sort` передаётся через data-лоадеры и хранится в URL. На главной — select справа от «Найдено туров» (в bar-варианте `TourFilters`). На `/tours` — аналогичная строка count+sort над сеткой, count убран из sidebar-фильтра.
- **Тема светлая/тёмная** — добавлена светлая тема и кнопка переключения в хедере (иконка солнца/луны). Тёмная — по умолчанию. Предпочтение сохраняется в `localStorage`. Реализовано через `src/composables/useTheme.js` (toggle `dark` на `<html>`). `assets/main.css`: `:root` — светлая тема, `.dark` — тёмная.
- **Фикс фильтр-бара** — выравнивание элементов исправлено (`items-start` → `items-end`): слайдеры с подписями «От X До Y» теперь совпадают по нижней линии с датпикером и кнопками. Горизонтальный отступ увеличен (`gap-x-6`).
- **Фильтр — слайдеры диапазона** — числовые поля «Цена» и «Длительность» заменены на двойные ползунки (`RangeSlider.vue`). Минимум и максимум берутся из реальных данных через новый endpoint `GET /api/tours/meta`. Слайдеры работают в обоих вариантах фильтра (`bar` и `sidebar`). Исправлено использование CSS-переменных: `var(--primary)` вместо `hsl(var(--primary))` — тема использует `oklch()`, а не числа hsl.

## Recent changes (2026-05-17)

- **Миграция на Ubuntu-сервер** — проект перенесён с Docker Desktop (Windows) на Ubuntu VM (192.168.2.133). Файлы доступны по `Z:\` (сетевая папка).
- **Vite proxy** — в `vite.config.js` добавлен proxy для `/api` и `/storage` → `http://backend:8000`. Браузер теперь использует относительные URL вместо `http://localhost:8000`. Исправляет авторизацию и автоподгрузку при работе с удалённого хоста.
- **VITE_API_URL** — очищен до пустой строки в `apps/frontend/.env`; fallback в `client.js`, `TourCard.vue`, `TourForm.vue`, `@slug/+Page.vue` изменён с `http://localhost:8000` на `''`.
- **CORS** — `config/cors.php` → `allowed_origins: ['*']` (ранее только localhost-паттерн).
- **Тёмная тема по умолчанию** — CSS-переменные в `:root` заменены на тёмные значения; класс `dark` добавлен на корневой `<div>` в `+Layout.vue`; `document.documentElement.classList.add('dark')` в `onMounted`.
- **Поиск (исправление)** — `SearchController.php`: семантический поиск и текстовый ILIKE теперь выполняются всегда и объединяются. Ранее тур, найденный в suggest-dropdown, мог отсутствовать в "Показать все результаты" если вектор-топ-10 был занят другими турами.

## Previous fixes (2026-05-16)

- **Tiptap rich text editor** — поле «Описание» заменено на `RichTextEditor.vue`. Хранит HTML.
- **AI-генерация: чекбоксы полей** — `generateFields` (reactive), только Описание по умолчанию.
- **Пустая страница настроек** — добавлена секция `admin.settings.account` в `i18n.js`.
- **Пустая страница `/tours/{slug}`** — `<Teleport to="body">` → `<Teleport to="#teleported">`.
- **Карта: панель редактирования** — `ymaps.multiRouter.MultiRoute` → `ymaps.route()`.
- **Карта: маршрут не строился** — `loading=false` + `nextTick()` до `new ymaps.Map()`.
- **Единый компонент фильтров** — `TourFilters.vue` с `variant="bar"|"sidebar"`.
- **Drag-and-drop фото** — `vuedraggable`, `PUT /api/admin/tours/{id}/photos/reorder`.
- **Фото не отображались** — storage symlink в Dockerfile.

## Notes

- Working directory (Windows): `Z:\`
- Working directory (server): `/home/user/tour-catalog/`
- Full plan: `PLAN.md`
- Architecture map: `PROJECT_MAP.md`
- User guide: `GUIDE.md`
- Главный приоритет: Stage 0 — GitHub repo + коллаборатор ver1000000
