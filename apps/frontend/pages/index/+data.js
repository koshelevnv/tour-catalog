import api from '../../src/api/client.js'

const DEFAULT_DATA = {
  tours: [], tourTypes: [], pagination: null,
  filters: { type: '', duration_min: '', duration_max: '', price_min: '', price_max: '', date_from: '', date_to: '', sort: '' },
  searchQuery: '', isSearch: false, perPage: 12, loadMode: 'infinite',
}

export async function data(pageContext) {
  const search = pageContext.urlParsed?.search ?? {}
  const q = search.q ?? ''

  let typesRes, settingsRes, metaRes
  try {
    ;[typesRes, settingsRes, metaRes] = await Promise.all([
      api.get('/api/tour-types'),
      api.get('/api/settings'),
      api.get('/api/tours/meta'),
    ])
  } catch {
    return DEFAULT_DATA
  }
  const filterMeta = metaRes.data

  const perPage = parseInt(settingsRes.data.home_per_page) || 12
  const loadMode = settingsRes.data.home_load_mode || 'infinite'
  const searchPerPage = parseInt(settingsRes.data.search_per_page) || 12
  const searchLoadMode = settingsRes.data.search_load_mode || 'pagination'

  if (q) {
    const searchParams = { q, per_page: searchPerPage }
    if (search.type)         searchParams.type         = search.type
    if (search.duration_min) searchParams.duration_min = search.duration_min
    if (search.duration_max) searchParams.duration_max = search.duration_max
    if (search.price_min)    searchParams.price_min    = search.price_min
    if (search.price_max)    searchParams.price_max    = search.price_max
    if (search.date_from)    searchParams.date_from    = search.date_from
    if (search.date_to)      searchParams.date_to      = search.date_to
    if (search.page)         searchParams.page         = search.page
    if (search.sort)         searchParams.sort         = search.sort

    try {
      const searchRes = await api.get('/api/tours/search', { params: searchParams })
      return {
        tours: searchRes.data.data,
        tourTypes: typesRes.data.data,
        pagination: searchRes.data.meta ?? null,
        filters: {
          type:         search.type         ?? '',
          duration_min: search.duration_min ?? '',
          duration_max: search.duration_max ?? '',
          price_min:    search.price_min    ?? '',
          price_max:    search.price_max    ?? '',
          date_from:    search.date_from    ?? '',
          date_to:      search.date_to      ?? '',
          sort:         search.sort         ?? '',
        },
        filterMeta,
        searchQuery: q,
        isSearch: true,
        perPage: searchPerPage,
        loadMode: searchLoadMode,
      }
    } catch {
      return { ...DEFAULT_DATA, tourTypes: typesRes.data.data, filterMeta, searchQuery: q, isSearch: true, perPage: searchPerPage, loadMode: searchLoadMode }
    }
  }

  const params = { per_page: perPage }
  if (search.type)         params.type         = search.type
  if (search.duration_min) params.duration_min = search.duration_min
  if (search.duration_max) params.duration_max = search.duration_max
  if (search.price_min)    params.price_min    = search.price_min
  if (search.price_max)    params.price_max    = search.price_max
  if (search.date_from)    params.date_from    = search.date_from
  if (search.date_to)      params.date_to      = search.date_to
  if (search.page)         params.page         = search.page
  if (search.sort)         params.sort         = search.sort

  try {
    const toursRes = await api.get('/api/tours', { params })
    return {
      tours: toursRes.data.data,
      tourTypes: typesRes.data.data,
      pagination: toursRes.data.meta,
      filters: {
        type:         search.type         ?? '',
        duration_min: search.duration_min ?? '',
        duration_max: search.duration_max ?? '',
        price_min:    search.price_min    ?? '',
        price_max:    search.price_max    ?? '',
        date_from:    search.date_from    ?? '',
        date_to:      search.date_to      ?? '',
        sort:         search.sort         ?? '',
      },
      filterMeta,
      searchQuery: '',
      isSearch: false,
      perPage,
      loadMode,
    }
  } catch {
    return { ...DEFAULT_DATA, tourTypes: typesRes.data.data, filterMeta, perPage, loadMode }
  }
}
