# Tour Catalog — Plan

## Context

This is a **test task for Full-stack AI Engineer position**.

Grading:
- **50%** — AI workflow: how fully Claude Code / AI agents cover development and testing
- **35%** — Code quality: best practices, architecture, brevity
- **15%** — Usability: attention to detail, production-readiness

Deliverables:
- GitHub repo, share access with `ver1000000`
- 5-minute video demo of the AI setup and workflow
- All code via Claude Code sessions (this IS the AI workflow demonstration)

> Docker is mandatory, not optional.

---

## Key Requirements

- Весь интерфейс — на **русском языке**
- Главная страница — **бесконечный листинг** с автоподгрузкой (Intersection Observer)
- Навигация по **категориям** туров на главной (горизонтальные чипы с иконками)
- Туры управляются через **панель администратора** (один суперпользователь)
- Карта: точки нумеруются **по порядку**, маршрут строится по реальным дорогам/тропам (`ymaps.route`, режим `pedestrian`)
- Семантический поиск через строку в хедере

---

## Tech Stack

| Aspect | Choice |
|---|---|
| Backend | Laravel 11 |
| Frontend | Vue + Vike (SSR) + Vite |
| Styling | Tailwind 4 + shadcn-vue |
| DB | PostgreSQL + pgvector |
| Embeddings model | all-MiniLM-L6-v2 (HuggingFace) |
| Embeddings service | FastAPI microservice |
| Infrastructure | Docker (monorepo) |

## Monorepo Structure

```
/
├── apps/
│   ├── backend/          # Laravel 11
│   └── frontend/         # Vue + Vike + Vite
├── services/
│   └── embeddings/       # FastAPI + sentence-transformers
├── docker/
│   └── nginx/default.conf
└── docker-compose.yml
```

## DB Schema

- `users` — id, name, email, password, timestamps
- `tour_types` — id, name, slug
- `tours` — id, type_id, title, slug, description, duration_days, embedding vector(384)
- `tour_photos` — id, tour_id, path, order
- `tour_variants` — id, tour_id, date, price
- `tour_waypoints` — id, tour_id, lat, lng, order, label

---

## Stages

### Stage 0 — GitHub Repo Setup
**Status:** `[ ]` По команде пользователя (делать последним)

**What:**
- `git init` в `c:\Users\N\Desktop\new_app\`
- Создать публичный репозиторий `tour-catalog` на GitHub
- Первый коммит + push
- Добавить коллаборатора `ver1000000`

**Test:** Репо видно на GitHub, коллаборатор приглашён

---

### Stage 1 — Docker + Monorepo Skeleton
**Status:** `[x]` Done

**Result:**

Docker (запускать из `new_app\`):
```
docker compose up -d
```

| Service | Image | Port |
|---|---|---|
| db | pgvector/pgvector:pg16 | 5432 |
| redis | redis:7-alpine | 6379 |
| backend | apps/backend/Dockerfile | 8000 |
| frontend | apps/frontend/Dockerfile | 5173 |
| embeddings | services/embeddings/Dockerfile | 8001 |

---

### Stage 2 — DB Migrations (Laravel)
**Status:** `[x]` Done

Migrations (все применены):
- `users` — Laravel default
- `tour_types` — id, name, slug
- `tours` — id, type_id, title, slug, description, duration_days, embedding vector(384)
- `tour_photos` — id, tour_id, path, order
- `tour_variants` — id, tour_id, date, price
- `tour_waypoints` — id, tour_id, lat, lng, order, label

Models: `TourType`, `Tour`, `TourPhoto`, `TourVariant`, `TourWaypoint` — с relationship methods

Seeder: 5 типов туров, 10 туров, варианты, точки маршрута

---

### Stage 3 — REST API: Tour Catalog (Laravel)
**Status:** `[x]` Done

Endpoints:
- `GET /api/tour-types` → TourTypeResource collection
- `GET /api/tours` → paginated (12/page), filters: `?type=slug&duration_min&duration_max&price_min&price_max&date_from&date_to`
- `GET /api/tours/search?q=...` → семантический поиск (cosine similarity через pgvector) + fallback на ILIKE
- `GET /api/tours/suggest?q=...` → быстрые текстовые подсказки (ILIKE по title/description, до 6 результатов)
- `GET /api/tours/{slug}` → TourDetailResource (photos, variants, waypoints)

---

### Stage 4 — REST API: Admin Auth + Tour CRUD (Laravel)
**Status:** `[x]` Done

Auth (один суперпользователь, Sanctum):
- `POST /api/admin/login` → `{token, user}`
- `POST /api/admin/logout` (auth required)

Tour CRUD (auth required):
- `POST /api/admin/tours` — создать тур
- `GET /api/admin/tours/{slug}` — тур для редактирования
- `PUT /api/admin/tours/{id}` — обновить
- `DELETE /api/admin/tours/{id}` → 204
- `POST /api/admin/tours/{id}/photos` — загрузить фото
- `DELETE /api/admin/tours/{tourId}/photos/{photoId}` → 204
- `PUT /api/admin/tours/{id}/waypoints` — заменить все точки маршрута
- `POST /api/admin/tour-variants` — создать дату+цену
- `PUT /api/admin/tour-variants/{id}` — обновить
- `DELETE /api/admin/tour-variants/{id}` → 204
- `POST /api/admin/tours/generate` — LLM генерация тура

---

### Stage 5 — Frontend: Base Layout + Routing
**Status:** `[x]` Done

shadcn-vue тема (CSS variables → Tailwind 4 `@theme inline`):
- Colors: background, foreground, primary, secondary, muted, accent, border, card
- Font: Inter (Google Fonts) → `--font-sans`

Роуты:
| Route | File |
|---|---|
| `/` | `pages/index/+Page.vue` |
| `/tours` | `pages/tours/+Page.vue` |
| `/tours/:slug` | `pages/tours/@slug/+Page.vue` |
| `/admin` | `pages/admin/+Page.vue` |
| `/admin/login` | `pages/admin/login/+Page.vue` |
| `/admin/tours` | `pages/admin/tours/+Page.vue` |
| `/admin/tours/create` | `pages/admin/tours/create/+Page.vue` |
| `/admin/tours/:slug/edit` | `pages/admin/tours/@slug/edit/+Page.vue` |
| `/admin/tour-types` | `pages/admin/tour-types/+Page.vue` |

Компоненты: `TheHeader.vue`, `TheFooter.vue`, `TourCard.vue`, `TourFilters.vue`, `TourPagination.vue`, `TourMap.vue`

---

### Stage 6 — Frontend: Главная страница — бесконечный листинг
**Status:** `[x]` Done

`/` — каталог туров с бесконечной прокруткой:
- Горизонтальный ряд чипов категорий (иконка + название, «Все» по умолчанию)
- Горизонтальная панель фильтров: Цена (диапазон), Длительность, Даты
- Счётчик «Найдено туров: N»
- Сетка карточек 4 col desktop / 2 col tablet / 1 mobile
- `IntersectionObserver` на sentinel-div → `GET /api/tours?page=N+1&...` → append карточек
- В режиме поиска (`?q=`): скрыты фильтры/категории, бейдж «Поиск по турам»

`/tours` — каталог с боковым фильтром и постраничной пагинацией (серверная):
- Боковая панель: тип (select), цена, длительность, дата отправления
- `TourPagination.vue` с ellipsis

Фильтры реализованы единым компонентом `TourFilters.vue` с `variant="bar"` (главная) и `variant="sidebar"` (/tours).

---

### Stage 7 — Frontend: Tour Detail Page
**Status:** `[x]` Done

- `pages/tours/@slug/+data.js` — SSR data fetch
- `pages/tours/@slug/+Page.vue` — полная страница тура

Features:
- SEO: `<Head>` (title, description, og:*)
- Фотогалерея: плиточный грид (Airbnb-стиль) + лайтбокс с keyboard nav (←→Esc) + thumbnail strip
- Заголовок, бейдж типа, длительность, цена от
- Блок описания
- Sidebar с вариантами дат+цен (sticky на desktop)
- Карта маршрута (`TourMap.vue`)
- Lightbox использует `<Teleport to="#teleported">` (не `body`) для корректной SSR-гидрации

---

### Stage 8 — Yandex Maps (нумерованные точки, реальный маршрут)
**Status:** `[x]` Done

**`TourMap.vue`:**

- Маркеры: `islands#blueStretchyIcon` с цифрой (`order`) и tooltip (`label`)
- Маршрут: `ymaps.route()` с `routingMode: 'pedestrian'` — строится по реальным дорогам без добавления редактируемой панели на карту
- При недоступности маршрута — автоматический fallback: `pedestrian → driving → straight` с уведомлением в левом нижнем углу
- Переключатель режимов (`bottom-10 right-2`): Пешком / Авто / Прямая
- `loading=false` + `nextTick()` вызывается ДО `new ymaps.Map()` — элемент имеет реальные размеры при инициализации
- `setBounds` с margin 60px

---

### Stage 9 — Embeddings Service + Semantic Search
**Status:** `[x]` Done

FastAPI (`services/embeddings/`):
- `main.py` — `POST /embed` → `{embedding: [float * 384]}` (all-MiniLM-L6-v2)
- `Dockerfile` — CPU-only torch, port 8001

Laravel:
- `app/Jobs/GenerateTourEmbedding.php` — вызывает `/embed`, сохраняет вектор
- `Tour::booted()` — dispatches job при создании / изменении title/description
- `SearchController.php` — cosine similarity через pgvector; fallback на ILIKE
- `TourController::suggest()` — быстрые подсказки (ILIKE, до 6 туров с фото)
- `php artisan tours:generate-embeddings` — bulk генерация

Frontend:
- `TheHeader.vue` — AJAX autocomplete: debounce 250 мс, dropdown с фото и длительностью, стрелки ↑↓, Escape; submit → `/?q=...`

---

### Stage 10 — Frontend: Панель администратора
**Status:** `[x]` Done

Pages:
- `/admin/login` — форма входа
- `/admin` — редирект → /admin/tours или /admin/login
- `/admin/tours` — таблица туров: название, тип, дней, цена от; Редактировать / Удалить
- `/admin/tours/create` — создать тур
- `/admin/tours/:slug/edit` — редактировать тур
- `/admin/tour-types` — управление типами туров (CRUD)
- `TourForm.vue` — форма тура: базовые поля, фото, даты+цены, точки маршрута, LLM-генерация

---

### Stage 11 (Bonus) — LLM Tour Generation
**Status:** `[x]` Done

- `POST /api/admin/tours/generate` → Anthropic API (claude-haiku-4-5-20251001)
- System prompt включает типы туров из БД
- Возвращает JSON: title, description, duration_days, type_id, waypoints[], variants[]
- `TourForm.vue` — секция «Генерация через ИИ» вверху формы

---

### Stage 12 — UX Fixes & Admin Settings
**Status:** `[x]` Done (12.1–12.10)

#### 12.1 — DateRangePicker в вариантах формы тура ✓
- В форме редактирования/создания тура (`TourForm.vue`) нативный `<input type="date">` заменён на `DateRangePicker`
- Каждый вариант позволяет выбрать диапазон дат (начало — окончание тура) через единый popup-календарь

#### 12.2 — Единый стиль календаря ✓
- Нативный `<input type="date">` (чёрный в тёмной ОС) заменён на кастомный `DateRangePicker` с единым светлым стилем (bg-card + CSS-переменные темы)
- `DateRangePicker` получил props `allowPast` и `placeholder` для гибкого использования

#### 12.3 — Длительность тура по настройке из «Дата и цены» ✓
- Каждый вариант теперь хранит `date_to`; при выборе диапазона вычисляется `duration_days = dateTo - dateFrom + 1`
- Поле «Длительность» в секции «Основная информация» автоматически обновляется
- Рядом с полем появляется inline-уведомление «Длительность обновлена по датам варианта» (исчезает через 4 с)

#### 12.4 — Добавление нового Типа прямо из формы тура ✓
- В `TourForm.vue` в поле «Тип тура» добавить кнопку `+` рядом с select
- При клике — inline-поле для ввода названия нового типа + кнопки «Сохранить» / «Отмена»
- `title` на кнопке `+`: «Добавить новый тип»
- После сохранения новый тип автоматически выбирается в select

#### 12.5 — Раздел настроек в панели администратора ✓
- Роут `/admin/settings`, страница `pages/admin/settings/+Page.vue`
- Таблица `settings` (key/value, primary key = key)
- `GET /api/settings` (public) — возвращает `yandex_maps_key` для фронтенда
- `GET/PUT /api/admin/settings` (auth) — все настройки + смена email/пароля суперпользователя
- Блоки: API Яндекс Карт, Учётная запись, ссылка на типы туров, Текстовые переменные
- Ключ Яндекс.Карт загружается из settings API; `src/utils/ymaps.js` — единая точка загрузки SDK

#### 12.6 — Навигация: добавить /tours в меню ✓
- `TheHeader.vue` — ссылка «Каталог туров» → `/tours`

#### 12.7 — Хлебные крошки: полный путь от главной ✓
- Единый компонент `AppBreadcrumbs.vue`
- Страница тура: Главная → Каталог туров → [Название]
- Админка (список, создание, редактирование): Главная → Панель администратора → Туры → …

#### 12.8 — Редактируемая иконка типа тура ✓
- Миграция: добавлен столбец `icon` (TEXT nullable) в `tour_types`
- Бэкенд: model / resource / controller принимают и отдают `icon`
- `/admin/tour-types`: textarea для SVG-кода + live-превью иконки при создании и редактировании; в списке иконка отображается рядом с названием
- `TourFilters.vue`: hardcoded словарь `typeIcons` удалён, иконка берётся из `tt.icon` (fallback — `allIcon`)

#### 12.9 — Убрать блок «Длительность» из формы редактирования тура ✓
- Из `TourForm.vue` удалён readonly-блок «Длительность» и связанные computed-свойства
- `duration_days` по-прежнему вычисляется из вариантов и сохраняется в БД при сохранении формы

#### 12.10 — Drag-and-drop сортировка фото в форме тура ✓
- В `TourForm.vue` секция фотографий становится drag-and-drop сеткой
- Первое фото = обложка тура (уже используется в каталоге как `cover`)
- Порядок (`order`) сохраняется на бэкенд сразу после перетаскивания (отдельный API-запрос)
- Touch-поддержка обязательна — использовать `vuedraggable` (на базе SortableJS с Pointer Events API), не нативный HTML5 drag
- Бэкенд: новый эндпоинт `PUT /api/admin/tours/{id}/photos/reorder` — принимает массив `[{id, order}]`

---

### Stage 13 — LLM Provider Settings
**Status:** `[x]` Done

#### 13.1 — ANTHROPIC_API_KEY в настройках ✓
- Ключ `anthropic_api_key` хранится в таблице `settings` (не в `.env`)
- `SettingsController`: включён в `SETTING_KEYS` и валидацию `PUT`
- `TourGenerationController`: берёт ключ из `settings`, fallback → `ANTHROPIC_API_KEY` из `.env`
- Страница `/admin/settings`: блок «ИИ / LLM» — поле ввода ключа Anthropic

#### 13.2 — Поддержка OpenRouter ✓
- Ключи в `settings`: `llm_provider` (`anthropic` | `openrouter`), `openrouter_api_key`, `openrouter_model`
- `TourGenerationController`: маршрутизирует запрос к нужному провайдеру
  - `anthropic` → `https://api.anthropic.com/v1/messages`
  - `openrouter` → `https://openrouter.ai/api/v1/chat/completions` (OpenAI-совместимый формат)
  - Модель по умолчанию: `anthropic/claude-haiku-4-5`; переопределяется полем `openrouter_model`
- Страница `/admin/settings`: radio «Провайдер», поля ключа и модели для OpenRouter (показываются условно)

---

### Stage 14 — Редактор текстов интерфейса
**Status:** `[x]` Done

#### 14.1 — Backend: хранение и отдача переводов
- Переводы хранятся в таблице `settings` с префиксом `lang.` (ключ `lang.nav.brand` → значение `«Мой сайт»`)
- `GET /api/translations` (public) — возвращает плоский объект всех переопределённых ключей
- `PUT /api/admin/translations` (auth) — принимает плоский объект `{ "key": "value" }`, пустое значение = удалить переопределение (вернуть дефолт)

#### 14.2 — Frontend: реактивный i18n + применение переводов из БД
- `t` в `i18n.js` обёрнут в `reactive()` → при мутации все компоненты перерисовываются
- `defaultTranslations` — замороженный слепок дефолтов, экспортируется из `i18n.js`
- `src/utils/translations.js` — `loadTranslations()`: запрашивает `GET /api/translations` и глубоко применяет к `t`
- `pages/+Layout.vue` — вызывает `loadTranslations()` в `onMounted` (SSR рендерит дефолты, клиент применяет после гидрации)

#### 14.3 — Admin: редактор на странице настроек
- Новая секция «Тексты интерфейса» в `/admin/settings`
- Сгруппированы по неймспейсу: Навигация, Хлебные крошки, Поиск, Каталог, Страница тура, Карта, Панель администратора
- Поиск по ключу и тексту в реальном времени
- Каждая строка: ключ (моно) + дефолтное значение (серое, как placeholder) + инпут для переопределения
- Отдельная кнопка «Сохранить тексты» (не мешает основной форме настроек)
- Пустой инпут = удалить переопределение = вернуть дефолт

---

### Stage 15 — UX Polish & Dev Performance
**Status:** `[x]` Done

- **Пустая страница настроек** — добавлена секция `account` в `admin.settings` в `i18n.js`
- **CORS** — `config/cors.php` обновлён на паттерн `localhost:*` вместо хардкода порта 5173
- **Фронтенд на хосте** — добавлен `.env.local` с `VITE_API_URL_SSR=http://localhost:8000`; запуск `npm run dev` в `apps/frontend/` без Docker
- **Бейдж «Бонус»** — убран из блока «Генерация через ИИ» в `TourForm.vue`
- **Кнопка удаления фото** — символ `×` заменён на SVG-иконку
- **Поле ввода ИИ** — `<input>` заменён на `<textarea>` с авторесайзом по высоте
- **Секции текстов в настройках** — свёрнуты по умолчанию

---

## Remaining Work

| # | Задача | Приоритет |
|---|---|---|
| ~~12.1~~ | ~~DateRangePicker в вариантах формы тура~~ | ✓ Done |
| ~~12.2~~ | ~~Единый стиль календаря (убрать чёрный фон)~~ | ✓ Done |
| ~~12.3~~ | ~~Длительность из «Дата и цены» + уведомление~~ | ✓ Done |
| ~~12.4~~ | ~~Добавление нового Типа из формы тура (кнопка +)~~ | ✓ Done |
| ~~12.5~~ | ~~Раздел настроек в админке (API, логин, переменные)~~ | ✓ Done |
| ~~12.6~~ | ~~Ссылка «Каталог туров» в навигации~~ | ✓ Done |
| ~~12.7~~ | ~~Хлебные крошки — полный путь от главной~~ | ✓ Done |
| ~~12.8~~ | ~~Редактируемая иконка типа тура~~ | ✓ Done |
| ~~12.9~~ | ~~Убрать блок «Длительность» из формы тура~~ | ✓ Done |
| ~~12.10~~ | ~~Drag-and-drop сортировка фото в форме тура~~ | ✓ Done |
| ~~13.1~~ | ~~ANTHROPIC_API_KEY в настройках (не в .env)~~ | ✓ Done |
| ~~13.2~~ | ~~Поддержка OpenRouter как LLM-провайдера~~ | ✓ Done |
| ~~14~~ | ~~Редактор текстов интерфейса в настройках~~ | ✓ Done |
| ~~15~~ | ~~UX polish + dev performance (frontend на хосте, CORS, UI-правки)~~ | ✓ Done |
| ~~16~~ | ~~Слайдеры диапазона в фильтрах (RangeSlider.vue, /api/tours/meta)~~ | ✓ Done |
| ~~17~~ | ~~Сортировка туров (цена/длительность/дата/алфавит) на главной и /tours~~ | ✓ Done |
| ~~18~~ | ~~Светлая тема + кнопка переключения в хедере~~ | ✓ Done |
| 0 | GitHub repo + коллаборатор ver1000000 | По команде |
| — | 5-мин видео: Claude Code workflow + demo | По команде |

---

## Final Deliverables Checklist

- [ ] GitHub repo public, collaborator `ver1000000` added
- [x] `docker compose up` запускает всё
- [x] Страница тура — фотогалерея, описание, варианты дат, карта
- [x] Главная страница — infinite scroll с фильтрами и навигацией по категориям
- [x] /tours — каталог с боковым фильтром и пагинацией
- [x] Поиск: семантический (pgvector) + fallback на ILIKE + AJAX autocomplete
- [x] Панель администратора: вход, список туров, создание/редактирование/удаление
- [x] Карта маршрута: нумерованные точки, реальный маршрут, режим пешком/авто/прямая
- [x] Управление типами туров (/admin/tour-types)
- [x] Раздел настроек (/admin/settings): API-ключ Яндекс.Карт, учётная запись, текстовые переменные
- [x] Ссылка «Каталог туров» в хедере
- [x] Хлебные крошки (AppBreadcrumbs.vue) на всех страницах
- [x] Единый компонент фильтров (bar + sidebar варианты) со слайдерами диапазона
- [x] Сортировка туров: цена / длительность / дата / алфавит на главной и /tours
- [x] Светлая и тёмная тема с кнопкой переключения, тёмная по умолчанию
- [x] (Bonus) LLM-генерация тура
- [x] Весь интерфейс на русском языке
- [ ] 5-мин видео: Claude Code workflow + demo приложения
