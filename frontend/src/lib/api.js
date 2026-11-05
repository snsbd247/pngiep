import axios from 'axios'
import { APP_BASE } from './base'

/**
 * Single Axios instance for the whole app.
 * - Same-origin cookies + XSRF header for Laravel Sanctum SPA auth.
 * - Every error is normalised to ApiError so components never parse Axios internals.
 * - Successful calls resolve to the API envelope: { success, message, data, meta }.
 */
export const http = axios.create({
  baseURL: `${APP_BASE}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  timeout: 30000,
})

export class ApiError extends Error {
  constructor(message, { status = 0, errors = null } = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    /** Laravel validation errors: { field: [messages] } */
    this.errors = errors
  }

  get isValidation() {
    return this.status === 422 && !!this.errors
  }

  get isNotFound() {
    return this.status === 404
  }
}

let csrfPromise = null

/** Fetch the CSRF cookie once per page load (or after it expires). */
export function ensureCsrfCookie(force = false) {
  if (!csrfPromise || force) {
    csrfPromise = axios.get(`${APP_BASE}/sanctum/csrf-cookie`, { withCredentials: true }).catch((err) => {
      csrfPromise = null
      throw err
    })
  }
  return csrfPromise
}

const unauthorizedListeners = new Set()

/** Subscribe to 401 responses (used by AuthProvider to clear the session). */
export function onUnauthorized(listener) {
  unauthorizedListeners.add(listener)
  return () => unauthorizedListeners.delete(listener)
}

http.interceptors.request.use(async (config) => {
  const method = (config.method || 'get').toLowerCase()
  if (!['get', 'head', 'options'].includes(method)) {
    await ensureCsrfCookie()
  }
  return config
})

http.interceptors.response.use(
  (response) => response.data,
  async (error) => {
    const { response, config } = error

    // CSRF token expired: refresh once and retry.
    if (response?.status === 419 && config && !config.__retried) {
      config.__retried = true
      await ensureCsrfCookie(true)
      return http.request(config)
    }

    if (response?.status === 401) {
      unauthorizedListeners.forEach((fn) => fn())
    }

    const body = response?.data
    const message =
      body?.message ||
      (error.code === 'ECONNABORTED'
        ? 'The request timed out. Please try again.'
        : !response
          ? 'Network error. Please check your connection.'
          : 'Something went wrong. Please try again.')

    return Promise.reject(new ApiError(message, { status: response?.status ?? 0, errors: body?.errors ?? null }))
  },
)

/** Strip empty values so query strings stay clean. */
export function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''),
  )
}

/** Build multipart form data, supporting arrays (key[]) and Files. */
export function toFormData(values) {
  const fd = new FormData()
  Object.entries(values).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (Array.isArray(value)) value.forEach((v) => fd.append(`${key}[]`, v))
    else if (typeof value === 'boolean') fd.append(key, value ? '1' : '0')
    else fd.append(key, value)
  })
  return fd
}
