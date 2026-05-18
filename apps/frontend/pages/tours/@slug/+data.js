import api from '../../../src/api/client.js'

export async function data(pageContext) {
  try {
    const res = await api.get(`/api/tours/${pageContext.routeParams.slug}`)
    return { tour: res.data.data }
  } catch {
    return { tour: null }
  }
}
