# Tour Catalog — Backend (Laravel 11)

REST API для каталога туров. Часть монорепо `new_app`.

## Запуск

Запускается через Docker Compose из корня монорепо:

```powershell
cd c:\Users\N\Desktop\new_app
docker compose up -d
```

Сервис доступен на `http://localhost:8000`.

## Основные компоненты

- **`routes/api.php`** — все API-маршруты
- **`app/Http/Controllers/Api/`** — публичные контроллеры (туры, типы, поиск)
- **`app/Http/Controllers/Api/Admin/`** — admin CRUD (auth required)
- **`app/Http/Resources/`** — API Resources (TourResource, TourDetailResource и др.)
- **`app/Models/`** — Tour, TourType, TourPhoto, TourVariant, TourWaypoint
- **`app/Jobs/GenerateTourEmbedding.php`** — генерация vector-embedding через FastAPI
- **`app/Console/Commands/GenerateTourEmbeddings.php`** — bulk команда `php artisan tours:generate-embeddings`

## Полная документация

Смотри `PROJECT_MAP.md` в корне монорепо.
