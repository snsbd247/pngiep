import { cn } from '@/lib/utils'

const TONES = {
  primary: 'bg-primary-50 text-primary-700 ring-primary-200',
  success: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
  warning: 'bg-amber-50 text-amber-700 ring-amber-200',
  danger: 'bg-rose-50 text-rose-700 ring-rose-200',
  info: 'bg-sky-50 text-sky-700 ring-sky-200',
  neutral: 'bg-slate-100 text-slate-600 ring-slate-200',
}

/** Maps common status values to tones so every module colours statuses consistently. */
const STATUS_TONES = {
  published: 'success', active: 'success', confirmed: 'success', completed: 'success', converted: 'success', replied: 'success', subscribed: 'success',
  draft: 'neutral', inactive: 'neutral', archived: 'neutral', closed: 'neutral',
  pending: 'warning', follow_up: 'warning', documents_pending: 'warning', rescheduled: 'warning', under_review: 'warning',
  new: 'info', contacted: 'info', interested: 'info', submitted: 'info', processing: 'info', read: 'info',
  cancelled: 'danger', rejected: 'danger', no_show: 'danger',
}

export default function Badge({ tone, status, className, children }) {
  const resolved = tone ?? STATUS_TONES[status] ?? 'neutral'
  return (
    <span className={cn('inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', TONES[resolved], className)}>
      {children ?? String(status ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())}
    </span>
  )
}
