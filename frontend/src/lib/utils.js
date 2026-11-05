import { clsx } from 'clsx'
import { withBase } from './base'

export function cn(...inputs) {
  return clsx(inputs)
}

export function isExternalUrl(url = '') {
  return /^(https?:)?\/\//i.test(url) || url.startsWith('mailto:') || url.startsWith('tel:')
}

/** Resolve a stored file path (e.g. "media/x.webp") or absolute URL to a usable src. */
export function assetUrl(path) {
  if (!path) return null
  if (isExternalUrl(path) || path.startsWith('blob:') || path.startsWith('data:')) return path
  if (path.startsWith('/')) return withBase(path)
  return withBase(`/storage/${path}`)
}

export function formatDate(value, options = { day: 'numeric', month: 'short', year: 'numeric' }) {
  if (!value) return ''
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '' : new Intl.DateTimeFormat(undefined, options).format(date)
}

export function formatDateTime(value) {
  return formatDate(value, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

export function formatBytes(bytes = 0) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1)
  return `${(bytes / 1024 ** i).toFixed(i ? 1 : 0)} ${units[i]}`
}

export function headline(value = '') {
  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

export function telHref(phone = '') {
  return `tel:${String(phone).replace(/[^\d+]/g, '')}`
}

export function whatsappHref(phone = '') {
  return `https://wa.me/${String(phone).replace(/\D/g, '')}`
}

/** Replace {year} tokens in admin-entered text. */
export function interpolate(text = '') {
  return String(text ?? '').replaceAll('{year}', String(new Date().getFullYear()))
}

/** Extract the video ID from youtube.com/watch, youtu.be, /shorts/ and /embed/ URLs. */
export function youtubeId(url = '') {
  const match = String(url ?? '').match(/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|shorts\/|embed\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/)
  return match ? match[1] : null
}

export function youtubeThumbnail(id, quality = 'hqdefault') {
  return id ? `https://i.ytimg.com/vi/${id}/${quality}.jpg` : null
}

/** Privacy-enhanced embed URL (no cookies until play). */
export function youtubeEmbedUrl(id) {
  return `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0`
}

/** "£28,000" from (28000, 'GBP'); falls back to a plain number without a currency. */
export function formatMoney(amount, currency) {
  if (amount === null || amount === undefined || amount === '') return ''
  const value = Number(amount)
  if (!currency) return value.toLocaleString()
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency, maximumFractionDigits: 0 }).format(value)
  } catch {
    return `${currency} ${value.toLocaleString()}`
  }
}

export function formatMoneyRange(min, max, currency) {
  if (!min && !max) return ''
  if (min && max && Number(min) !== Number(max)) return `${formatMoney(min, currency)} – ${formatMoney(max, currency)}`
  return formatMoney(min || max, currency)
}

/** Compact numbers for stats: 40000 → "40,000+". */
export function formatCount(n, plus = true) {
  if (n === null || n === undefined) return ''
  return `${Number(n).toLocaleString()}${plus ? '+' : ''}`
}
