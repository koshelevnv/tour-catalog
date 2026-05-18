import api from '../../src/api/client.js'

export async function data(pageContext) {
  const search = pageContext.urlParsed?.search ?? {}
  const q = search.q ?? ''

  const [typesRes, settingsRes, metaRes] = await Promise.all([
    api.get('/api/tour-types'),
    api.get('/api/settings'),
    api.get('/api/tours/meta'),
  ])
  const filterMeta = metaRes.data

  const perPage = parseInt(settingsRes.data.catalog_per_page) || 12
  const loadMode = settingsRes.data.catalog_load_mode || 'pagination'

  if (q) {
    const searchRes = await api.get('/api/tours/search', { params: { q } })
    return {
      tours: searchRes.data.data,
      pagination: null,
      tourTypes: typesRes.data.data,
      filters: { type: '', duration_min: '', duration_max: '', price_min: '', price_max: '' },
      filterMeta,
      searchQuery: q,
      isSearch: true,
      perPage,
      loadMode,
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

  const toursRes = await api.get('/api/tours', { params })

  return {
    tours: toursRes.data.data,
    pagination: toursRes.data.meta,
    tourTypes: typesRes.data.data,
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
}
