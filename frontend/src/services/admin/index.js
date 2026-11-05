import { cleanParams, http, toFormData } from '@/lib/api'
import { createCrudService } from './crud'

export const dashboardService = {
  /** params: { from, to } (YYYY-MM-DD); defaults to the current month. */
  get: (params) => http.get('/admin/dashboard', { params: cleanParams(params) }).then((res) => res.data),
  /** CSV of the period, resolved as a Blob. */
  report: (params) => http.get('/admin/dashboard/report', { params: cleanParams(params), responseType: 'blob', timeout: 120000 }),
}

export const adminSearchService = {
  search: (q) => http.get('/admin/search', { params: { q } }).then((res) => res.data),
}

export const metaService = {
  get: () => http.get('/admin/meta').then((res) => res.data),
}

export const settingsService = {
  get: () => http.get('/admin/settings').then((res) => res.data),
  update: (settings) => http.put('/admin/settings', { settings }),
  testMail: (email) => http.post('/admin/settings/test-mail', { email }),
}

export const mediaService = {
  ...createCrudService('/admin/media'),
  upload: (files, folder, onUploadProgress) =>
    http.post('/admin/media', toFormData({ files, folder }), { onUploadProgress }),
  bulkRemove: (ids) => http.delete('/admin/media/bulk', { data: { ids } }),
}

export const userService = createCrudService('/admin/users')

export const roleService = {
  list: () => http.get('/admin/roles').then((res) => res.data),
  options: () => http.get('/admin/roles/options').then((res) => res.data),
  update: (id, payload) => http.put(`/admin/roles/${id}`, payload),
}

export const activityLogService = {
  list: (params) => http.get('/admin/activity-logs', { params: cleanParams(params) }),
}

export const lookupService = {
  /** Option lists for dropdowns: lookupService.get(['pages', 'statistic_groups']) */
  get: (types) => http.get('/admin/lookups', { params: { types: types.join(',') } }).then((res) => res.data),
}

export const sectionSchemaService = {
  get: () => http.get('/admin/meta/sections').then((res) => res.data),
}

export const menuService = {
  ...createCrudService('/admin/menu-items'),
  menus: () => http.get('/admin/menus').then((res) => res.data),
  updateMenu: (id, payload) => http.put(`/admin/menus/${id}`, payload),
  reorderItems: (menuId, items) => http.post('/admin/menu-items/reorder', { menu_id: menuId, items }),
}

/** CRUD services for content modules, keyed by API path segment. */
export const contentServices = Object.fromEntries(
  ['homepage-sections', 'hero-slides', 'statistics', 'team-members', 'timeline-items', 'pages', 'services', 'partners', 'testimonials', 'faqs', 'destinations', 'universities', 'courses', 'success-stories', 'gallery-albums', 'gallery-videos', 'gallery-categories', 'blogs', 'blog-categories', 'counsellors', 'email-templates', 'page-categories', 'destination-categories', 'story-categories', 'partner-categories', 'faq-categories', 'degree-types', 'intakes', 'blog-tags'].map((key) => [
    key,
    createCrudService(`/admin/${key}`),
  ]),
)

const notes = (path) => (id, body) => http.post(`${path}/${id}/notes`, { body })

/** Business / CRM (Phase 5). Lists resolve to the envelope ({ data, meta }). */
export const appointmentAdminService = {
  list: (params) => http.get('/admin/appointments', { params: cleanParams(params) }),
  get: (id) => http.get(`/admin/appointments/${id}`).then((res) => res.data),
  setStatus: (id, status) => http.put(`/admin/appointments/${id}/status`, { status }),
  cancel: (id, reason) => http.post(`/admin/appointments/${id}/cancel`, { reason }),
  reschedule: (id, payload) => http.post(`/admin/appointments/${id}/reschedule`, payload),
  slots: (params) => http.get('/admin/appointments/slots', { params: cleanParams(params) }).then((res) => res.data),
  addNote: notes('/admin/appointments'),
}

export const leadService = {
  list: (params) => http.get('/admin/leads', { params: cleanParams(params) }),
  get: (id) => http.get(`/admin/leads/${id}`).then((res) => res.data),
  update: (id, payload) => http.put(`/admin/leads/${id}`, payload),
  remove: (id) => http.delete(`/admin/leads/${id}`),
  addNote: notes('/admin/leads'),
}

export const applicationService = {
  list: (params) => http.get('/admin/applications', { params: cleanParams(params) }),
  get: (id) => http.get(`/admin/applications/${id}`).then((res) => res.data),
  update: (id, payload) => http.put(`/admin/applications/${id}`, payload),
  remove: (id) => http.delete(`/admin/applications/${id}`),
  addNote: notes('/admin/applications'),
  /** Private file, streamed through the authenticated API (never a public URL). Resolves to a Blob. */
  download: (id, documentId) => http.get(`/admin/applications/${id}/documents/${documentId}`, { responseType: 'blob', timeout: 120000 }),
}

export const messageService = {
  list: (params) => http.get('/admin/messages', { params: cleanParams(params) }),
  get: (id) => http.get(`/admin/messages/${id}`).then((res) => res.data),
  setStatus: (id, status) => http.put(`/admin/messages/${id}`, { status }),
  reply: (id, reply) => http.post(`/admin/messages/${id}/reply`, { reply }),
  remove: (id) => http.delete(`/admin/messages/${id}`),
}

export const newsletterService = {
  list: (params) => http.get('/admin/newsletter', { params: cleanParams(params) }),
  remove: (id) => http.delete(`/admin/newsletter/${id}`),
  export: () => http.get('/admin/newsletter/export', { responseType: 'blob' }),
}

export const notificationService = {
  summary: () => http.get('/admin/notifications/summary').then((res) => res.data),
  list: (params) => http.get('/admin/notifications', { params: cleanParams(params) }),
  markRead: (id) => http.post(`/admin/notifications/${id}/read`),
  markAllRead: () => http.post('/admin/notifications/read-all'),
  remove: (id) => http.delete(`/admin/notifications/${id}`),
  preferences: () => http.get('/admin/notifications/preferences').then((res) => res.data),
  savePreferences: (preferences) => http.put('/admin/notifications/preferences', { preferences }),
}

export const profileService = {
  logins: () => http.get('/admin/profile/logins'),
}

export const leadSourceService = {
  get: (params) => http.get('/admin/leads/sources', { params: cleanParams(params) }),
}

export const counsellorAvailabilityService = {
  get: () => http.get('/admin/counsellors/availability'),
}

export const systemService = {
  info: () => http.get('/admin/system').then((res) => res.data),
  clearCache: () => http.post('/admin/system/cache-clear'),
  backup: (files = false) => http.post('/admin/system/backups', { files }, { timeout: 300000 }),
  backups: () => http.get('/admin/system/backups').then((res) => res.data),
  download: (file) => http.get(`/admin/system/backups/${encodeURIComponent(file)}`, { responseType: 'blob', timeout: 300000 }),
}
