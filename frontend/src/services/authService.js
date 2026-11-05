import { http } from '@/lib/api'

export const authService = {
  /** Resolves to the signed-in user or null (never 401). */
  session: () => http.get('/admin/auth/session').then((res) => res.data),
  me: () => http.get('/admin/auth/me').then((res) => res.data),
  login: (credentials) => http.post('/admin/auth/login', credentials).then((res) => res.data),
  logout: () => http.post('/admin/auth/logout'),
  forgotPassword: (email) => http.post('/admin/auth/forgot-password', { email }),
  resetPassword: (payload) => http.post('/admin/auth/reset-password', payload),
  updateProfile: (payload) => http.put('/admin/profile', payload),
}
