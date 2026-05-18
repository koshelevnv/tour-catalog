# Tour Catalog — Frontend (Vue 3 + Vike SSR)

SSR-фронтенд для каталога туров. Часть монорепо `new_app`.

## Запуск

Запускается через Docker Compose из корня монорепо:

```powershell
cd c:\Users\N\Desktop\new_app
docker compose up -d
```

Сайт доступен на `http://localhost:5173`.

## Структура

```
pages/          — Vike SSR страницы (роутинг по структуре папок)
  index/        — главная / (infinite scroll каталог)
  tours/        — /tours (каталог с пагинацией)
  tours/@slug/  — /tours/:slug (детальная страница тура)
  admin/        — /admin/* (панель администратора)
src/
  components/   — Vue компоненты
    TourFilters.vue     — единый компонент фильтров (variant=bar|sidebar)
    TourMap.vue         — Yandex Maps 2.1 с маршрутом
    TourCard.vue        — карточка тура в листинге
    TheHeader.vue       — шапка с поиском и autocomplete
    admin/TourForm.vue  — форма создания/редактирования тура
  api/          — Axios клиент и функции admin API
  i18n.js       — все UI-строки на русском
assets/
  main.css      — Tailwind 4 + shadcn-vue + глобальные стили
```

## Полная документация

Смотри `PROJECT_MAP.md` в корне монорепо.
