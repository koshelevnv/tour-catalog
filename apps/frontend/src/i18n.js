import { reactive } from 'vue'

function _flatten(obj, prefix = '') {
  const res = {}
  for (const [k, v] of Object.entries(obj)) {
    const key = prefix ? `${prefix}.${k}` : k
    if (typeof v === 'function') continue
    if (v && typeof v === 'object') Object.assign(res, _flatten(v, key))
    else if (typeof v === 'string') res[key] = v
  }
  return res
}

const _raw = {
  nav: {
    brand: 'Каталог туров',
    tours: 'Каталог туров',
    admin: 'Администратор',
  },

  breadcrumbs: {
    home: 'Главная',
    catalog: 'Каталог туров',
    admin: 'Панель администратора',
    tours: 'Туры',
    newTour: 'Новый тур',
  },

  search: {
    placeholder: 'Куда поедем?',
    ariaLabel: 'Найти',
    badge: 'Поиск по турам',
    resultsFor: 'Результаты по запросу:',
    backToAll: '← Все туры',
    showAll: '→ Показать все результаты',
    days: 'дн.',
  },

  catalog: {
    title: 'Каталог туров',
    filters: 'Фильтры',
    allTypes: 'Все',
    type: 'Тип тура',
    priceFrom: 'Цена, ₽ от',
    priceTo: 'Цена, ₽ до',
    priceLabel: 'Цена (₽)',
    daysFrom: 'Дней от',
    daysTo: 'Дней до',
    daysLabel: 'Длительность (дней)',
    from: 'от',
    to: 'до',
    departureDates: 'Дата отправления',
    apply: 'Применить',
    reset: 'Сбросить',
    foundLabel: 'Найдено туров:',
    empty: 'Туры не найдены.',
    allShown: 'Показаны все туры',
    sortLabel: 'Сортировка',
    sortPriceAsc: 'Сначала дешевые',
    sortPriceDesc: 'Сначала дорогие',
    sortDurationAsc: 'Длительность: меньше',
    sortDurationDesc: 'Длительность: больше',
    sortDateDesc: 'Сначала новые',
    sortDateAsc: 'Сначала старые',
    sortTitleAsc: 'По алфавиту А→Я',
    sortTitleDesc: 'По алфавиту Я→А',
  },

  tour: {
    noPhoto: 'Нет фото',
    priceFrom: 'от',
    back: '← Назад к турам',
    description: 'Описание',
    noDescription: 'Описание отсутствует.',
    datesAndPrices: 'Даты и цены',
    noDates: 'Нет доступных дат.',
    route: 'Маршрут',
    noPhotos: 'Фотографии отсутствуют',
    days: { one: 'день', few: 'дня', many: 'дней' },
  },

  map: {
    loading: 'Загрузка карты…',
    error: 'Карта недоступна',
    errorManual: 'Карта недоступна — введите координаты вручную',
    routeModes: {
      pedestrian: 'Пешком',
      driving: 'Авто',
      straight: 'Прямая',
    },
    noticeFallbackDriving: 'Пешком недоступно — показан автомаршрут',
    noticeFallbackStraight: 'Маршрут недоступен — показана прямая линия',
  },

  admin: {
    panel: 'Панель управления',
    navTours: 'Туры',
    navNewTour: '+ Новый тур',
    navTourTypes: 'Типы туров',
    navSettings: 'Настройки',
    logout: 'Выход',

    settings: {
      title: 'Настройки',
      save: 'Сохранить',
      saving: 'Сохранение…',
      saved: 'Настройки сохранены',
      error: 'Ошибка при сохранении',

      mapsKey: {
        title: 'API Яндекс Карт',
        label: 'Ключ API',
        placeholder: 'Вставьте ключ API от Яндекс.Карт',
      },

      llm: {
        title: 'ИИ / LLM',
        provider: 'Провайдер',
        providerAnthropic: 'Anthropic (Claude)',
        providerOpenRouter: 'OpenRouter',
        anthropicKey: 'Ключ Anthropic API',
        anthropicKeyPlaceholder: 'sk-ant-…',
        openrouterKey: 'Ключ OpenRouter API',
        openrouterKeyPlaceholder: 'sk-or-…',
        openrouterModel: 'Модель OpenRouter',
        openrouterModelPlaceholder: 'openai/gpt-4o-mini',
        openrouterModelHint: 'Примеры (платные): openai/gpt-4o-mini, anthropic/claude-3-haiku. Бесплатные (медленно): meta-llama/llama-3.1-8b-instruct:free, qwen/qwen-2.5-7b-instruct:free',
      },

      account: {
        title: 'Аккаунт администратора',
        email: 'Email',
        password: 'Новый пароль',
        passwordPlaceholder: 'Оставьте пустым, чтобы не менять',
        passwordHint: 'Минимум 8 символов',
      },

      tourTypes: {
        title: 'Типы туров',
        description: 'Создание, редактирование и удаление типов туров вынесено в отдельный раздел.',
        link: 'Перейти к управлению типами →',
      },

      seo: {
        title: 'SEO',
        metaTitle: 'Название сайта',
        metaTitleHint: 'Используется в заголовке вкладки и тегах Open Graph',
        metaDescription: 'Мета-описание',
        metaDescriptionHint: 'До 160 символов — показывается в результатах поиска',
        ogImage: 'OG-изображение',
        ogImageHint: 'Картинка для предпросмотра в соцсетях (1200×630 px, JPEG/PNG/WebP, до 5 МБ)',
      },

      systemPrompt: {
        title: 'Системный промпт для ИИ',
        label: 'Инструкция',
        placeholder: 'Например: Пиши описания в стиле приключенческого блога. Избегай шаблонных фраз.',
        hint: 'Полный текст системного промпта для генерации туров. Список типов туров и ближайшие даты подставляются автоматически в конец промпта.',
        charCount: (n) => `${n} / 5000`,
      },

      display: {
        title: 'Отображение туров',
        home: 'Главная страница',
        catalog: 'Каталог туров (/tours)',
        search: 'Страница поиска',
        perPage: 'Количество туров',
        loadMode: 'Режим загрузки',
        modeInfinite: 'Автоподгрузка (бесконечная прокрутка)',
        modeLoadMore: 'Кнопка «Загрузить ещё»',
        modePagination: 'Постраничная пагинация',
        loadMore: 'Загрузить ещё',
      },
    },

    login: {
      title: 'Вход в панель',
      email: 'Email',
      password: 'Пароль',
      submit: 'Войти',
      submitting: 'Вход…',
      error: 'Неверный логин или пароль',
    },

    tourList: {
      title: 'Туры',
      newTour: '+ Новый тур',
      loading: 'Загрузка…',
      colTitle: 'Название',
      colType: 'Тип',
      colDays: 'Дней',
      colPrice: 'Цена от',
      edit: 'Редактировать',
      delete: 'Удалить',
      empty: 'Туров ещё нет.',
      createFirst: 'Создать первый',
      confirmDelete: (name) => `Удалить тур "${name}"?`,
    },

    tourEdit: {
      title: 'Редактировать тур',
      loading: 'Загрузка…',
      notFound: 'Тур не найден',
    },

    tourCreate: {
      title: 'Новый тур',
    },

    form: {
      generate: {
        title: 'Генерация через ИИ',
        badge: 'Бонус',
        hint: 'Опишите тур — форма заполнится автоматически',
        placeholder: 'Тур по Алтаю, 7 дней, активный',
        submit: 'Сгенерировать',
        submitting: 'Генерация…',
        success: 'Форма заполнена — проверьте и сохраните',
        error: 'Ошибка генерации',
      },

      basic: {
        sectionTitle: 'Основная информация',
        name: 'Название',
        namePlaceholder: 'Алтайские горы: треккинг к Белухе',
        slug: 'Slug',
        slugPlaceholder: 'auto-generated',
        type: 'Тип',
        duration: 'Длительность (дней)',
        description: 'Описание',
        descriptionPlaceholder: 'Подробное описание тура…',
      },

      photos: {
        sectionTitle: 'Фотографии',
        required: 'Необходимо добавить фото',
        delete: 'Удалить',
        undo: 'Отменить',
        remove: 'Убрать',
        add: 'Добавить',
        newBadge: 'New',
      },

      variants: {
        sectionTitle: 'Даты и цены',
        add: '+ Добавить',
        empty: 'Нет вариантов. Добавьте хотя бы одну дату с ценой.',
        pricePlaceholder: 'Цена (₽)',
      },

      waypoints: {
        sectionTitle: 'Маршрут на карте',
        hint: 'Кликните на карту, чтобы добавить точку маршрута.',
        labelPlaceholder: 'Подпись (необязательно)',
        addManual: '+ Добавить точку вручную',
      },

      actions: {
        saveAndExit: 'Сохранить и выйти',
        apply: 'Применить',
        create: 'Создать тур',
        saving: 'Сохранение…',
        cancel: 'Отмена',
        saved: 'Изменения сохранены',
      },

      errors: {
        title: 'Укажите название тура',
        type: 'Выберите тип тура',
        duration: 'Укажите длительность',
        photo: 'Добавьте хотя бы одну фотографию',
        save: 'Ошибка при сохранении',
      },
    },
  },
}

export const defaultTranslations = Object.freeze(_flatten(_raw))
export const t = reactive(_raw)

export function pluralDays(n) {
  const mod10 = n % 10
  const mod100 = n % 100
  if (mod10 === 1 && mod100 !== 11) return t.tour.days.one
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return t.tour.days.few
  return t.tour.days.many
}

export function formatDuration(min, max) {
  if (!min && !max) return null
  const effectiveMin = min ?? max
  const effectiveMax = max ?? min
  if (effectiveMin === effectiveMax) return `${effectiveMin} ${pluralDays(effectiveMin)}`
  return `от ${effectiveMin} до ${effectiveMax} ${t.tour.days.many}`
}
