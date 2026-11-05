import { ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/lib/utils'

function pageList(current, last) {
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = new Set([1, last, current, current - 1, current + 1])
  if (current <= 3) [2, 3, 4].forEach((p) => pages.add(p))
  if (current >= last - 2) [last - 1, last - 2, last - 3].forEach((p) => pages.add(p))
  const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b)
  return sorted.flatMap((p, i) => (i > 0 && p - sorted[i - 1] > 1 ? ['…', p] : [p]))
}

/** Pagination driven by the API meta object ({ current_page, last_page, total, from, to }). */
export default function Pagination({ meta, onPageChange, className }) {
  if (!meta || meta.last_page <= 1) return null
  const { current_page: current, last_page: last } = meta

  const btn = 'grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm font-medium transition-colors'

  return (
    <nav className={cn('flex flex-col items-center justify-between gap-3 sm:flex-row', className)} aria-label="Pagination">
      <p className="text-sm text-slate-500">
        Showing <span className="font-semibold text-slate-700">{meta.from}</span>–<span className="font-semibold text-slate-700">{meta.to}</span> of{' '}
        <span className="font-semibold text-slate-700">{meta.total}</span>
      </p>
      <div className="flex items-center gap-1">
        <button type="button" className={cn(btn, 'text-slate-600 hover:bg-slate-100 disabled:opacity-40')} disabled={current <= 1} onClick={() => onPageChange(current - 1)} aria-label="Previous page">
          <ChevronLeft className="size-4" />
        </button>
        {pageList(current, last).map((p, i) =>
          p === '…' ? (
            <span key={`gap-${i}`} className="px-1 text-slate-400">…</span>
          ) : (
            <button
              key={p}
              type="button"
              onClick={() => onPageChange(p)}
              aria-current={p === current ? 'page' : undefined}
              className={cn(btn, p === current ? 'bg-primary text-white shadow-glow' : 'text-slate-600 hover:bg-primary-50 hover:text-primary')}
            >
              {p}
            </button>
          ),
        )}
        <button type="button" className={cn(btn, 'text-slate-600 hover:bg-slate-100 disabled:opacity-40')} disabled={current >= last} onClick={() => onPageChange(current + 1)} aria-label="Next page">
          <ChevronRight className="size-4" />
        </button>
      </div>
    </nav>
  )
}
