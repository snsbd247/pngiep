import { cleanParams, http } from '@/lib/api'

/**
 * Standard REST service for an admin resource, e.g. createCrudService('/admin/users').
 * list() resolves to the full envelope ({ data, meta }) so tables get pagination info.
 */
export function createCrudService(basePath) {
  return {
    list: (params) => http.get(basePath, { params: cleanParams(params) }),
    get: (id) => http.get(`${basePath}/${id}`).then((res) => res.data),
    create: (payload) => http.post(basePath, payload),
    update: (id, payload) => http.put(`${basePath}/${id}`, payload),
    remove: (id) => http.delete(`${basePath}/${id}`),
    /** Persist drag & drop order: ids in display order. */
    reorder: (ids) => http.post(`${basePath}/reorder`, { ids }),
  }
}
