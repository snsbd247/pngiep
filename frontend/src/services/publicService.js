import { cleanParams, http } from '@/lib/api'

export const publicService = {
  homepage: () => http.get('/homepage').then((res) => res.data),
  page: (slug) => http.get(`/pages/${slug}`).then((res) => res.data),
  faqs: (pageKey) => http.get('/faqs', { params: cleanParams({ page_key: pageKey }) }).then((res) => res.data),
  subscribe: (payload) => http.post('/newsletter', payload),
  enquire: (payload) => http.post('/enquiries', payload),
  search: (q) => http.get('/search', { params: { q } }),
}

/**
 * Directory lists resolve to the full envelope ({ data, meta }) so pages get
 * pagination and filter facets; details resolve to the record.
 * Array params are sent as key[]=a&key[]=b (Laravel-style).
 */
const list = (path) => (params) => http.get(path, { params, paramsSerializer: { indexes: false } })
const detail = (path) => (slug) => http.get(`${path}/${slug}`).then((res) => res.data)

export const directoryService = {
  destinations: list('/destinations'),
  destination: detail('/destinations'),
  universities: list('/universities'),
  university: detail('/universities'),
  courses: list('/courses'),
  course: detail('/courses'),
  services: () => http.get('/services').then((res) => res.data),
  service: detail('/services'),
}

export const contentService = {
  stories: list('/success-stories'),
  story: detail('/success-stories'),
  submitStory: (payload) => http.post('/success-stories', payload),
  photos: list('/gallery/photos'),
  album: (slug) => http.get(`/gallery/albums/${slug}`).then((res) => res.data),
  videos: list('/gallery/videos'),
  blogs: list('/blogs'),
  /** Resolves to the full envelope: data = post, meta = sidebar (categories, popular posts). */
  blog: (slug) => http.get(`/blogs/${slug}`),
}

export const bookingService = {
  /** Services, counsellors, destinations and booking rules for the wizard. */
  options: () => http.get('/appointments/options').then((res) => res.data),
  /** { 'YYYY-MM-DD': freeSlotCount } for a month. */
  availability: (counsellor, month) => http.get('/appointments/availability', { params: { counsellor, month } }).then((res) => res.data),
  slots: (counsellor, date) => http.get('/appointments/slots', { params: { counsellor, date } }).then((res) => res.data),
  book: (payload) => http.post('/appointments', payload),
  manage: (token) => http.get(`/appointments/manage/${token}`).then((res) => res.data),
  cancel: (token, reason) => http.post(`/appointments/manage/${token}/cancel`, { reason }),
  reschedule: (token, payload) => http.post(`/appointments/manage/${token}/reschedule`, payload),
}

export const inquiryService = {
  contact: (payload) => http.post('/contact', payload),
  /** multipart: FormData with documents[i][type] / documents[i][file]. */
  apply: (formData, onUploadProgress) => http.post('/applications', formData, { onUploadProgress, timeout: 120000 }),
}
