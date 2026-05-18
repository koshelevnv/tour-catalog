import api from './client.js'

export const login = (email, password) =>
  api.post('/api/admin/login', { email, password })

export const logout = () =>
  api.post('/api/admin/logout')

export const getTourTypes = () =>
  api.get('/api/tour-types')

export const getTours = (params = {}) =>
  api.get('/api/tours', { params })

export const getAdminTour = (slug) =>
  api.get(`/api/admin/tours/${slug}`)

export const createTour = (data) =>
  api.post('/api/admin/tours', data)

export const updateTour = (id, data) =>
  api.put(`/api/admin/tours/${id}`, data)

export const deleteTour = (id) =>
  api.delete(`/api/admin/tours/${id}`)

export const uploadPhoto = (tourId, file) => {
  const form = new FormData()
  form.append('photo', file)
  return api.post(`/api/admin/tours/${tourId}/photos`, form)
}

export const deletePhoto = (tourId, photoId) =>
  api.delete(`/api/admin/tours/${tourId}/photos/${photoId}`)

export const reorderPhotos = (tourId, photos) =>
  api.put(`/api/admin/tours/${tourId}/photos/reorder`, { photos })

export const syncWaypoints = (tourId, waypoints) =>
  api.put(`/api/admin/tours/${tourId}/waypoints`, { waypoints })

export const createVariant = (data) =>
  api.post('/api/admin/tour-variants', data)

export const updateVariant = (id, data) =>
  api.put(`/api/admin/tour-variants/${id}`, data)

export const deleteVariant = (id) =>
  api.delete(`/api/admin/tour-variants/${id}`)

export const generateTour = (prompt) =>
  api.post('/api/admin/tours/generate', { prompt })

export const createTourType = (data) =>
  api.post('/api/admin/tour-types', data)

export const updateTourType = (id, data) =>
  api.put(`/api/admin/tour-types/${id}`, data)

export const deleteTourType = (id) =>
  api.delete(`/api/admin/tour-types/${id}`)

export const getSettings = () =>
  api.get('/api/admin/settings')

export const updateSettings = (data) =>
  api.put('/api/admin/settings', data)

export const uploadOgImage = (file) => {
  const form = new FormData()
  form.append('image', file)
  return api.post('/api/admin/settings/og-image', form)
}

export const getTranslations = () =>
  api.get('/api/translations')

export const saveTranslations = (translations) =>
  api.put('/api/admin/translations', { translations })
