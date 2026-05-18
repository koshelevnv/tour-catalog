# Tour Catalog

Каталог туров с семантическим поиском, картой маршрута и AI-генерацией туров.

## Стек

| Слой | Технология |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | Vue 3 + Vike (SSR) + Vite |
| Стили | Tailwind 4 + shadcn-vue |
| БД | PostgreSQL 16 + pgvector |
| Embeddings | FastAPI + all-MiniLM-L6-v2 (HuggingFace) |
| Карта | Yandex Maps API 2.1 |
| LLM | Anthropic / OpenRouter (настраивается в админке) |
| Инфраструктура | Docker Compose |

## Быстрый старт

Требования: Docker + Docker Compose. Работает на Linux (рекомендуется), macOS, Windows.

```bash
git clone https://github.com/koshelevnv/tour-catalog.git
cd tour-catalog
docker compose up -d
```

Первый запуск занимает 3–5 минут (загрузка образов, установка зависимостей, миграции).  
База данных мигрирует и заполняется тестовыми данными автоматически (20 туров).

| Сервис | URL |
|---|---|
| Сайт | http://localhost:5173 |
| API | http://localhost:8000 |
| Embeddings | http://localhost:8001 |

## Доступ в админку

```
http://localhost:5173/admin/login

Email:    admin@example.com
Пароль:   admin_secret
```

## Возможности

- **Главная** — бесконечная прокрутка, фильтры по типу/цене/длительности/дате (слайдеры диапазона), сортировка, семантический поиск
- **Каталог /tours** — боковой фильтр со слайдерами, сортировка, постраничная пагинация
- **Страница тура** — фотогалерея с лайтбоксом, варианты дат и цен, карта маршрута (Yandex Maps)
- **Карта** — нумерованные точки, маршрут по реальным дорогам (`ymaps.route`), режимы Пешком / Авто / Прямая
- **Поиск** — семантический через pgvector + текстовый ILIKE (объединённые результаты) + AJAX autocomplete
- **Светлая и тёмная тема** — переключатель в шапке, тёмная по умолчанию, предпочтение сохраняется в браузере
- **Сортировка** — по цене, длительности, дате публикации, алфавиту; сохраняется в URL
- **Админка** — создание/редактирование/удаление туров, управление типами, настройки API-ключей, редактор текстов интерфейса
- **AI-генерация** — генерация тура по текстовому описанию (Anthropic или OpenRouter), с выбором каких полей перезаписывать
- **Rich text** — форматированный редактор описания (Tiptap)

## Настройка API-ключей

Ключи задаются через **Настройки** в админ-панели (`/admin/settings`) — без редактирования файлов:

- **Яндекс.Карты** — для отображения карты маршрута
- **Anthropic / OpenRouter** — для AI-генерации туров

## Полезные команды

```bash
# Статус контейнеров
docker compose ps

# Логи
docker compose logs -f backend

# Сброс базы данных
docker compose exec backend php artisan migrate:fresh --seed

# Генерация эмбеддингов для всех туров
docker compose exec backend php artisan tours:generate-embeddings

# Остановка
docker compose down
```
