# Tour Catalog — Карта проекта

Каталог туров по России с семантическим поиском, картой маршрута и AI-генерацией туров.

## Стек

| Слой | Технология |
|---|---|
| Backend | Laravel 11 (PHP), REST API |
| Frontend | Vue 3 + Vike (SSR) + Vite |
| Стили | Tailwind 4 + shadcn-vue |
| БД | PostgreSQL 16 + pgvector |
| Embeddings | FastAPI + all-MiniLM-L6-v2 |
| Карта | Yandex Maps API 2.1 |
| LLM | Anthropic claude-haiku-4-5-20251001 или OpenRouter (переключается в /admin/settings) |
| Инфраструктура | Docker Compose |

---

## Структура монорепо

```
new_app/
├── apps/
│   ├── backend/          — Laravel 11 (API)
│   └── frontend/         — Vue + Vike (SSR)
├── services/
│   └── embeddings/       — FastAPI микросервис
├── docker-compose.yml
├── PLAN.md               — этапы разработки со статусами
├── STATUS.md             — текущий статус и последние правки
├── GUIDE.md              — руководство пользователя
└── PROJECT_MAP.md        — этот файл
```

---

## Docker (docker-compose.yml)

| Сервис | Образ/Dockerfile | Порт | Назначение |
|---|---|---|---|
| `db` | pgvector/pgvector:pg16 | 5432 | PostgreSQL с расширением vector |
| `redis` | redis:7-alpine | 6379 | Redis (очереди, кэш) |
| `backend` | apps/backend/Dockerfile | 8000 | Laravel API |
| `frontend` | apps/frontend/Dockerfile | 5173 | Vue SSR сервер |
| `embeddings` | services/embeddings/Dockerfile | 8001 | FastAPI embeddings |

**Переменные backend в docker-compose:** `DB_HOST=db`, `REDIS_HOST=redis`, `EMBEDDINGS_URL=http://embeddings:8001`

---

## Backend (apps/backend/)

### Роуты — routes/api.php

```
GET  /api/tour-types              — список всех типов туров
GET  /api/tours                   — каталог с фильтрами (пагинация 12/стр)
GET  /api/tours/meta              — мин/макс значения duration и price по всем турам
GET  /api/tours/search            — семантический поиск по вектору
GET  /api/tours/suggest           — быстрые текстовые подсказки (до 6 туров)
GET  /api/tours/{slug}            — детальная страница тура

POST /api/admin/login             — получить Bearer-токен
POST /api/admin/logout            — отозвать токен (auth required)

POST   /api/admin/tour-types      — создать тип тура (auth required)
PUT    /api/admin/tour-types/{id} — обновить тип тура (auth required)
DELETE /api/admin/tour-types/{id} — удалить тип тура (auth required)

POST /api/admin/tours/generate    — AI-генерация тура (auth required)
GET  /api/admin/tours/{slug}      — тур для редактирования (auth required)
POST /api/admin/tours             — создать тур (auth required)
PUT  /api/admin/tours/{id}        — обновить тур (auth required)
DELETE /api/admin/tours/{id}      — удалить тур (auth required)

POST   /api/admin/tours/{id}/photos                  — загрузить фото (auth required)
DELETE /api/admin/tours/{tourId}/photos/{photoId}    — удалить фото (auth required)

PUT /api/admin/tours/{id}/waypoints   — синхронизировать точки маршрута (auth required)

POST   /api/admin/tour-variants       — создать вариант (дата+цена) (auth required)
PUT    /api/admin/tour-variants/{id}  — обновить вариант (auth required)
DELETE /api/admin/tour-variants/{id}  — удалить вариант (auth required)

PUT /api/admin/tours/{id}/photos/reorder  — изменить порядок фото [{id, order}] (auth required)

GET /api/settings                    — публичные настройки (yandex_maps_key)
GET /api/admin/settings              — все настройки (auth required)
PUT /api/admin/settings              — сохранить настройки (auth required)
GET /api/translations                — публичные переводы (переопределения из settings)
PUT /api/admin/translations          — сохранить переводы (auth required)
```

**ВАЖНО:** маршрут `/api/tours/search` и `/api/tours/suggest` объявлены ДО `/api/tours/{slug}`, иначе Laravel захватит "search"/"suggest" как slug-параметр.

### bootstrap/app.php

Регистрирует маршруты и middleware. Добавляет `HandleCors` в начало API-цепочки.

### config/cors.php

`allowed_origins: ['*']` — разрешает CORS от любого источника. Нужно для работы с Ubuntu-сервера (IP 192.168.2.133), где фронт и бэкенд на разных портах.

### config/services.php

```php
'embeddings' => ['url' => env('EMBEDDINGS_URL', 'http://localhost:8001')]
'anthropic'  => ['key' => env('ANTHROPIC_API_KEY')]
```

---

### Модели (app/Models/)

#### Tour.php
Поля: `type_id`, `title`, `slug`, `description`, `duration_days`, `embedding` (vector(384)).

**Хук `booted()`:** при создании или изменении `title`/`description` автоматически диспетчирует Job `GenerateTourEmbedding` (через `afterCommit`).

Связи:
- `type()` — `BelongsTo(TourType)` по `type_id`
- `photos()` — `HasMany(TourPhoto)` отсортированы по `order`
- `variants()` — `HasMany(TourVariant)` отсортированы по `date`
- `waypoints()` — `HasMany(TourWaypoint)` отсортированы по `order`

#### TourType.php
Поля: `name`, `slug`. Связь: `tours()` — `HasMany(Tour)`.

#### TourPhoto.php
Поля: `tour_id`, `path` (путь в storage/public), `order`.

#### TourVariant.php
Поля: `tour_id`, `date` (cast → Carbon), `price`.

#### TourWaypoint.php
Поля: `tour_id`, `lat`, `lng`, `order`, `label`.

---

### Контроллеры — публичный API

#### TourTypeController.php
`index()` — возвращает **все** типы туров (`orderBy('name')`).
`store/update/destroy` — CRUD (auth required).

#### TourController.php
`meta()` — возвращает `{min_duration, max_duration, min_price, max_price}` — агрегаты по `tours.duration_days` и `tour_variants.price`. Используется фронтендом для инициализации слайдеров фильтра.

`index(Request $request)` — список туров с фильтрами:
- `?type=slug` — фильтр по slug типа (через `whereHas`)
- `?duration_min=N&duration_max=N` — по длительности
- `?price_min=N&price_max=N` — по цене (через `whereHas` на variants)
- `?date_from=Y-m-d&date_to=Y-m-d` — по дате вариантов (через `whereHas` на variants)
- `?sort=` — сортировка: `price_asc`, `price_desc` (subquery `MIN(tour_variants.price)`), `duration_asc`, `duration_desc`, `date_asc`, `date_desc`, `title_asc`, `title_desc`; по умолчанию `created_at DESC`
- Пагинация: 12/страница

`show(string $slug)` — один тур по slug со всеми связями, возвращает `TourDetailResource`.

`suggest(Request $request)` — быстрые подсказки для autocomplete: ILIKE по title/description, до 6 результатов с первым фото.

#### SearchController.php (invokable)
`__invoke(Request $request)` — семантический поиск с пагинацией и фильтрами:
1. POST на `{EMBEDDINGS_URL}/embed` с текстом → float[384]
2. Отбирает туры с cosine distance `< 0.75` (порог релевантности), до 30 штук; дополняет ILIKE-совпадениями которых нет в векторном топе
3. `whereIn($allIds)` → применяет фильтры (`applyFilters`) и сортировку (`applySort`); при наличии вектора — сортировка по умолчанию: `CASE WHEN embedding IS NOT NULL THEN distance ELSE 1 END`
4. Возвращает пагинированный `TourResource::collection` с `meta` (current_page, last_page, total)
5. Fallback на ILIKE + `applySort('date_desc')` при недоступности embeddings
6. Поддерживает те же фильтры что и `TourController::index()`: `type`, `duration_min/max`, `price_min/max`, `date_from/to`, `sort`, `page`, `per_page`

Private методы: `applyFilters(Builder $query, Request $request)`, `applySort(Builder $query, string $sort)`.

---

### Контроллеры — Admin API

#### AuthController.php
`login()` — email/password → Sanctum-токен. Учётные данные по умолчанию: `admin@example.com` / `admin_secret`.

#### AdminTourController.php
`show(slug)`, `store()`, `update(id)`, `destroy(id)` — CRUD туров.

#### AdminTourPhotoController.php
`store(tourId)` — загрузить фото (multipart, макс. 10 МБ). Путь: `storage/app/public/tours/{id}/`.
`destroy(tourId, photoId)` — удалить файл + запись.

#### AdminTourVariantController.php
`store/update/destroy` — CRUD вариантов (дата + цена).

#### AdminTourWaypointController.php
`sync(tourId)` — **полная замена** всех точек маршрута (DELETE + INSERT).

#### TourGenerationController.php
`generate()` — Anthropic API (claude-haiku-4-5-20251001, max_tokens: 1200).
Возвращает: `{title, description, duration_days, type_id, waypoints[], variants[]}`.

---

### API Resources

| Resource | Поля |
|---|---|
| TourTypeResource | id, name, slug |
| TourResource | id, title, slug, duration_days, type, cover (первое фото path), price_from |
| TourDetailResource | id, title, slug, description, duration_days, type, photos (paths[]), variants, waypoints |
| AdminTourDetailResource | как TourDetailResource + type_id, photos как [{id, path}] |
| TourVariantResource | id, date (Y-m-d), price |
| TourWaypointResource | id, lat, lng, order, label |

---

### Jobs

#### app/Jobs/GenerateTourEmbedding.php
Диспетчируется из `Tour::booted()`. Конкатенирует title+description → POST `/embed` → raw SQL UPDATE embedding.
При недоступности сервиса — логирует предупреждение, не ретраится.

### Artisan команды

`php artisan tours:generate-embeddings [--all]` — генерирует embedding для туров с NULL (без --all) или для всех (с --all).

---

## FastAPI сервис (services/embeddings/)

`POST /embed  { text: str } → { embedding: float[384] }`

Загружает `all-MiniLM-L6-v2` при старте (один раз). Python 3.11, PyTorch CPU-only, порт 8001.

---

## Frontend (apps/frontend/)

### vite.config.js
Плагины: `vike()`, `vue()`, `tailwindcss()`. Алиас `@` → `src/`. Порт 5173 (при занятости — следующий свободный). `usePolling: true` нужен только для Docker; на хосте polling не нужен но и не вредит.

### assets/main.css
`@import "tailwindcss"` + CSS-переменные shadcn-vue в двух наборах: `:root` — **светлая тема** (белый фон, тёмный текст), `.dark` — **тёмная тема** (тёмный фон, светлый текст).
Переключение темы работает через класс `dark` на `<html>`.

`@layer base`: глобальный `cursor: pointer` для `button, a, [role="button"], select, label, summary`.

### src/composables/useTheme.js
Управление темой. Хранит `isDark` (shared ref), переключает класс `dark` на `document.documentElement`, сохраняет выбор в `localStorage` (`theme: 'dark' | 'light'`). Тёмная тема — по умолчанию. Экспортирует `useTheme()` → `{ isDark, toggle, init }`.

### src/i18n.js
Все UI-строки в одном объекте `t` (обёрнут в `reactive()`). Экспортирует `t`, `defaultTranslations` (замороженный слепок для редактора текстов) и `pluralDays(n)`.
Секции: `nav`, `breadcrumbs`, `search`, `catalog`, `tour`, `map`, `admin` (включает `settings`, `login`, `tourList`, `tourEdit`, `tourCreate`, `form`).

---

### API клиент

#### src/api/client.js
Axios-инстанс с умным baseURL: SSR → `VITE_API_URL_SSR` / `http://backend:8000`; браузер → `VITE_API_URL` / `http://localhost:8000`.
Добавляет `Authorization: Bearer {token}` из `localStorage.admin_token`.

#### src/api/admin.js
Все функции для админки: login, logout, getTourTypes, getTours, getAdminTour, createTour, updateTour, deleteTour, uploadPhoto, deletePhoto, syncWaypoints, createVariant, updateVariant, deleteVariant, generateTour, createTourType, updateTourType, deleteTourType.

---

### Компоненты

#### src/components/TheHeader.vue
Sticky шапка. Поиск с debounce 250 мс → AJAX подсказки из `/api/tours/suggest` → dropdown; submit → `/?q=...`; клик по туру → Vike `navigate('/tours/{slug}')`.
Патчит `history.pushState/replaceState` для синхронизации поля поиска при SPA-навигации.
Кнопка переключения темы (солнце/луна) — вызывает `useTheme().toggle()`.

#### src/components/TourCard.vue
Карточка тура. Props: `tour` (TourResource). Показывает фото, тип, название, длительность, цену от.

#### src/components/RangeSlider.vue
Двойной ползунок диапазона. Props: `min`, `max`, `modelMin`, `modelMax`, `suffix`, `step`.
Emits: `update:modelMin`, `update:modelMax`.
Два `<input type="range">` наложены друг на друга (`position: absolute`); цветная заливка между ручками вычисляется как CSS-стиль. Подписи «От X / До Y» под слайдером.

#### src/components/TourFilters.vue
Единый компонент фильтров с двумя вариантами отображения.

Props:
- `tourTypes: Array` — список типов туров
- `filters: Object` — текущие значения фильтров (`type, duration_min, duration_max, price_min, price_max, date_from, date_to, sort`)
- `basePath: String` — базовый URL для навигации (`'/'` или `'/tours'`)
- `variant: String` — `'bar'` (горизонтальная полоса) или `'sidebar'` (вертикальная карточка)
- `totalCount: Number | null` — счётчик найденных туров (используется только в bar-варианте)
- `filterMeta: Object` — `{min_duration, max_duration, min_price, max_price}` из `/api/tours/meta`; задаёт границы слайдеров
- `extraParams: Object` — доп. параметры сохраняемые в URL при навигации (например `{ q: 'горы' }` для страницы поиска)

`variant="bar"` — горизонтальный ряд чипов категорий + фильтры в строку (главная `/`). Клик по чипу — немедленная навигация. Под фильтрами — строка: «Найдено туров: N» слева, select сортировки справа. Сортировка по умолчанию — `date_desc` (Сначала новые).

`variant="sidebar"` — вертикальная карточка с select для типа (страница `/tours`). Кнопки «Применить» / «Сбросить». Счётчик туров НЕ отображается (находится в основном контенте страницы).

Числовые поля длительности и цены заменены на `RangeSlider`. URL-параметры пишутся только если значение отличается от глобального минимума/максимума. Параметр `sort` включается в URL при любом отличном от пустой строки значении.

Watch на `props.filters` синхронизирует локальные ref (включая `sortOrder`) при клиентской навигации.

#### src/components/TourPagination.vue
Пагинация с ellipsis (±2 страницы от текущей). Props: `pagination`, `filters`, `basePath`, `extraParams`.
`pageUrl(page)` включает в URL все активные фильтры (`type`, `duration_min/max`, `price_min/max`, `date_from`, `date_to`, `sort`) + `extraParams` (например `?q=`). Без `extraParams` или `basePath` используются дефолты.

#### src/components/TourMap.vue
Карта маршрута. Props: `waypoints: Array [{lat, lng, order, label}]`.

**Инициализация:**
1. `loading = false` + `await nextTick()` ДО `new ymaps.Map()` — элемент имеет реальные размеры
2. Создаёт карту с `controls: ['zoomControl']`
3. Добавляет `Placemark` (номер + tooltip)
4. `setBounds` с margin 60px
5. `buildRoute(coords, mode)` — запрос маршрута

**`buildRoute(coords, mode)`:**
- `straight`: `ymaps.Polyline`
- иначе: `ymaps.route(coords, { routingMode: mode })` — строит маршрут БЕЗ добавления редактируемой панели в DOM карты
- Таймаут 10 сек + токен для отмены устаревших запросов
- При ошибке/таймауте: `autoFallback(pedestrian → driving → straight)`
- При fallback: показывает уведомление в `routeNotice` (нижний левый угол карты)

**UI:**
- Переключатель режимов: `bottom-10 right-2` (Пешком / Авто / Прямая)
- Уведомление о fallback: `bottom-2 left-2`

#### src/components/admin/TourForm.vue
Форма создания/редактирования тура. Props: `initialTour`. Emits: `saved(slug)`.

Секции: AI-генерация, основные поля, фото, варианты дат+цен, карта маршрута.

**AI-генерация:** `generateFields` (reactive) — чекбоксы для каждого блока: `description` (по умолчанию ✓), `title`, `type_id`, `waypoints`, `variants`. `generate()` применяет только отмеченные поля.

**Описание:** использует `RichTextEditor.vue` через `v-model="form.description"`. Хранит HTML.

**Фото:** drag-and-drop через `vuedraggable`. После перетаскивания — `PUT /api/admin/tours/{id}/photos/reorder`. Первое фото = обложка.

**Варианты:** `DateRangePicker.vue` для выбора диапазона дат; `date_to` вычисляется и сохраняется; `duration_days` обновляется автоматически.

Карта в форме: клик → добавить точку; watch на waypoints → перерисовка маркеров + маршрут с debounce 600 мс.

`save()`: updateTour/createTour → deletePhoto (помеченные) → uploadPhoto (новые) → variant CRUD → syncWaypoints → emit('saved').

#### src/components/admin/RichTextEditor.vue
Минималистичный rich text редактор на базе Tiptap (`@tiptap/vue-3` + `@tiptap/starter-kit`).
Тулбар: B, I, H2, H3, маркированный список, нумерованный список, цитата, разделитель.
Props: `modelValue`. Emits: `update:modelValue`. Поддерживает `v-model`.
Стили редактора встроены в компонент (`.prose-editor`).

#### src/components/AppBreadcrumbs.vue
Хлебные крошки. Используется на странице тура и в админке.

#### src/components/DateRangePicker.vue
Календарь для выбора диапазона дат. Props: `allowPast`, `placeholder`. Используется в вариантах тура.

---

### Pages (Vike SSR маршруты)

#### pages/+Layout.vue
`TheHeader → <main><slot/></main> → TheFooter`. В `onMounted` вызывает `useTheme().init()` (читает `localStorage`, выставляет класс `dark` на `<html>`) + `loadTranslations()` + `loadSeoSettings()`.

#### pages/index/+Page.vue
Главная страница (`/`) — два режима зависящих от `data.isSearch`:

**Режим поиска (`data.isSearch = true`)** — sidebar-лейаут аналогичный `/tours`:
- Заголовок + бейдж «Поиск», ссылка «Назад ко всем»
- `TourFilters variant="sidebar"` с `extra-params="{ q: data.searchQuery }"`
- Строка «Найдено туров: N» + select сортировки над сеткой
- `TourPagination` с `extra-params="{ q: data.searchQuery }"`
- Поддержка всех трёх `loadMode`: pagination / load_more / infinite

**Режим каталога (`data.isSearch = false`)** — оригинальный bar-лейаут:
- `TourFilters variant="bar"`
- `TourPagination`, sentinel для infinite scroll, кнопка «Загрузить ещё»

`loadMore()` универсален: при `data.isSearch` вызывает `/api/tours/search?q=...`, иначе `/api/tours`. Watch на `data.tours` синхронизирует состояние при клиентской навигации.

#### pages/index/+data.js
Параллельно запрашивает `/api/tour-types`, `/api/settings`, `/api/tours/meta`.
При `?q=` → `/api/tours/search` с `search_per_page` / `search_load_mode` из настроек; передаёт все URL-параметры (type, duration_min/max, price_min/max, date_from/to, sort, page).
Иначе → `/api/tours` с `home_per_page` / `home_load_mode` из настроек.
Возвращает `filterMeta` для слайдеров, `isSearch`, `searchQuery`, `loadMode`, `perPage`.

#### pages/tours/+Page.vue
Каталог `/tours` с боковым фильтром и пагинацией.
Использует `TourFilters variant="sidebar" base-path="/tours"`.
Над сеткой туров: строка «Найдено туров: N» (слева) + select сортировки (справа). Изменение сортировки — навигация (`window.location.href`) с сохранением активных фильтров.

#### pages/tours/+data.js
Аналог index/+data.js. Те же фильтры включая date_from/date_to и **sort**. Добавляет `page` параметр для серверной пагинации. Параллельно запрашивает `/api/tours/meta`, возвращает `filterMeta`.

#### pages/tours/@slug/+Page.vue
Детальная страница тура. SEO через `<Head>`.
Lightbox использует `<Teleport to="#teleported">` — vike-vue SSR поддерживает только `#teleported`, не `body`.

#### pages/admin/+Layout.vue
Auth guard + боковая навигация. Перенаправляет на `/admin/login` при отсутствии токена.

#### pages/admin/tour-types/+Page.vue
CRUD типов туров: список, создание, inline-редактирование, удаление. Поддерживает SVG-иконку с live-превью.

#### pages/admin/settings/+Page.vue
Настройки: API Яндекс.Карт, блок ИИ/LLM (Anthropic / OpenRouter с ключом и моделью), учётная запись (email + пароль), ссылка на типы туров, редактор текстов интерфейса (сгруппированный по неймспейсам, поиск по ключу/тексту, все блоки свёрнуты по умолчанию).
Раздел «Отображение туров»: Главная, Каталог туров, **Страница поиска** — каждый блок содержит `per_page` и `load_mode` (pagination / load_more / infinite).

---

## Схема БД

```sql
tour_types    id, name, slug, icon (TEXT nullable), timestamps
tours         id, type_id(FK), title, slug, description (HTML), duration_days, embedding vector(384), timestamps
tour_photos   id, tour_id(FK), path, order, timestamps
tour_variants id, tour_id(FK), date, date_to (nullable), price, timestamps
tour_waypoints id, tour_id(FK), lat, lng, order, label, timestamps
users         id, name, email, password, timestamps
personal_access_tokens  -- Sanctum
settings      key (PK), value  -- API-ключи, настройки LLM, переводы (prefix lang.)
```

Примечания:
- `tour_types.icon` — SVG-код иконки, редактируется в `/admin/tour-types`
- `tour_variants.date_to` — дата окончания тура; `duration_days` вычисляется из диапазона дат
- `tours.description` — хранит HTML (генерируется Tiptap-редактором)
- `settings` — ключи: `yandex_maps_key`, `anthropic_api_key`, `llm_provider`, `openrouter_api_key`, `openrouter_model`, `llm_system_prompt`, `meta_title`, `meta_description`, `og_image`, `home_per_page`, `home_load_mode`, `catalog_per_page`, `catalog_load_mode`, `search_per_page`, `search_load_mode`, `lang.*` (переводы)

Расширение pgvector: `CREATE EXTENSION IF NOT EXISTS vector;`

---

## Переменные окружения

### apps/backend/.env (ключевые)
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1 (в Docker: db)
DB_DATABASE=tour_catalog
DB_USERNAME=tour
DB_PASSWORD=secret
QUEUE_CONNECTION=sync
EMBEDDINGS_URL=http://localhost:8001 (в Docker: http://embeddings:8001)
ANTHROPIC_API_KEY=sk-ant-...
```

### apps/frontend/.env (ключевые)
```
VITE_API_URL=http://localhost:8000
VITE_API_URL_SSR=http://backend:8000  # только в Docker
VITE_YANDEX_MAPS_API_KEY=             # ключ Яндекс.Карт
```

### apps/frontend/.env.local (gitignored, для запуска на хосте)
```
VITE_API_URL_SSR=http://localhost:8000  # переопределяет SSR URL вне Docker
```

---

## Поток данных — семантический поиск

```
Пользователь вводит ≥2 символа → debounce 250 мс
→ GET /api/tours/suggest?q= → dropdown (до 6 туров)
  → клик: Vike navigate('/tours/{slug}')
→ Enter/submit: window.location.href = /?q={query}
→ SSR: index/+data.js → GET /api/tours/search?q= + фильтры из URL
→ SearchController:
    → POST embeddings:8001/embed → vector[384]
    → WHERE cosine_distance < 0.75 ORDER BY distance LIMIT 30  (релевантный топ)
    → UNION ILIKE-совпадения не вошедшие в топ
    → applyFilters + applySort → paginate(search_per_page)
    → meta {current_page, last_page, total}
→ isSearch=true → sidebar-лейаут с фильтрами, пагинацией, сортировкой
```

## Поток данных — создание/редактирование тура

```
TourForm.save()
→ createTour/updateTour
→ Tour::booted() → GenerateTourEmbedding::dispatch()
→ deletePhoto (помеченные) → uploadPhoto (новые)
→ variant CRUD
→ syncWaypoints (полная замена)
→ emit('saved') → Vike navigate('/tours/{slug}')
```

## Поток данных — AI-генерация

```
TourForm промпт + выбранные чекбоксы (generateFields)
→ POST /api/admin/tours/generate
→ TourGenerationController: маршрутизирует к Anthropic или OpenRouter (из settings)
→ JSON {title, description, duration_days, type_id, waypoints[], variants[]}
→ только отмеченные поля заменяются в форме (description — по умолчанию)
```

---

## Типичные места ошибок

| Симптом | Где искать |
|---|---|
| CORS ошибки | `config/cors.php`, `bootstrap/app.php` |
| Семантический поиск не работает | `SearchController.php`, порт 8001, `EMBEDDINGS_URL` |
| Пустая страница (белый экран) | Hydration mismatch — проверь `<Teleport>`: должен быть `to="#teleported"` |
| Карта не инициализируется | `VITE_YANDEX_MAPS_API_KEY`, видимость элемента до `new ymaps.Map()` |
| Карта: панель редактирования поверх карты | Используй `ymaps.route()`, не `ymaps.multiRouter.MultiRoute` |
| Фото не показываются | Storage symlink в Docker: `rm -f public/storage && php artisan storage:link` |
| Авторизация не проходит | `AuthController.php`, user в БД, `localStorage.admin_token` |
| CORS при запуске фронта на хосте | `config/cors.php` — паттерн должен покрывать используемый порт; перезапустить `docker compose restart backend` |
| Пустая страница настроек | `i18n.js` — проверить наличие всех секций в `admin.settings` (mapsKey, llm, account, tourTypes, textVars) |
| AI-генерация: 503 | `ANTHROPIC_API_KEY` не задан |
| SSR vs CSR baseURL | `src/api/client.js` — разный URL для сервера и браузера |
| Waypoints не сохраняются | `AdminTourWaypointController::sync()` — полная замена, не merge |
| slug конфликт | Уникальность slug в БД |
