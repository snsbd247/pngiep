import { http } from '@/lib/api'

export const siteService = {
  /** Public settings + menus in one call. */
  get: () => http.get('/site').then((res) => res.data),
}
