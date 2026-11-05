import { CalendarDays, ChevronDown } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import Button from '@/components/ui/Button'
import { cn } from '@/lib/utils'

const pad = (n) => String(n).padStart(2, '0')
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n)

/** "2025-04-01" → "01 Apr 2025" */
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
// eslint-disable-next-line react-refresh/only-export-components
export const fmtDate = (value) => {
  if (!value) return ''
  const [y, m, d] = value.split('-').map(Number)
  return `${String(d).padStart(2, '0')} ${MONTHS[m - 1]} ${y}`
}

function presets() {
  const t = new Date()
  const today = new Date(t.getFullYear(), t.getMonth(), t.getDate())
  return [
    ['This month', new Date(today.getFullYear(), today.getMonth(), 1), new Date(today.getFullYear(), today.getMonth() + 1, 0)],
    ['Last month', new Date(today.getFullYear(), today.getMonth() - 1, 1), new Date(today.getFullYear(), today.getMonth(), 0)],
    ['Last 7 days', addDays(today, -6), today],
    ['Last 30 days', addDays(today, -29), today],
    ['Last 90 days', addDays(today, -89), today],
    ['This year', new Date(today.getFullYear(), 0, 1), new Date(today.getFullYear(), 11, 31)],
  ].map(([label, from, to]) => ({ label, from: iso(from), to: iso(to) }))
}

/** value: { from, to } (YYYY-MM-DD). onChange receives the new range. */
export default function DateRangePicker({ value, onChange, className, placeholder = 'All time' }) {
  const [open, setOpen] = useState(false)
  const [draft, setDraft] = useState(value)
  const ref = useRef(null)

  useEffect(() => {
    if (!open) return undefined
    const onDown = (e) => ref.current && !ref.current.contains(e.target) && setOpen(false)
    const onKey = (e) => e.key === 'Escape' && setOpen(false)
    document.addEventListener('mousedown', onDown)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onDown)
      document.removeEventListener('keydown', onKey)
    }
  }, [open])

  const apply = (range) => {
    onChange(range)
    setOpen(false)
  }
  const valid = draft.from && draft.to && draft.from <= draft.to

  return (
    <div ref={ref} className={cn('relative', className)}>
      <button
        type="button"
        onClick={() => { setDraft(value); setOpen(!open) }}
        aria-expanded={open}
        className="flex h-[39px] w-full items-center gap-3 rounded-md border border-slate-200 bg-white px-4 text-[12.5px] font-medium text-slate-700 hover:border-primary sm:w-auto sm:min-w-[223px]"
      >
        <CalendarDays className="size-[18px] text-slate-500" strokeWidth={1.75} />
        <span className="flex-1 text-left whitespace-nowrap">{value.from ? `${fmtDate(value.from)} - ${fmtDate(value.to)}` : placeholder}</span>
        <ChevronDown className="size-4 text-slate-500" />
      </button>
      {open && (
        <div className="absolute right-0 z-40 mt-2 flex w-[300px] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_18px_40px_-12px_rgb(17_12_46_/_0.25)] sm:w-[440px] sm:flex-row">
          <ul className="border-b border-slate-100 p-2 sm:w-40 sm:border-r sm:border-b-0">
            {presets().map((p) => (
              <li key={p.label}>
                <button
                  type="button"
                  onClick={() => apply({ from: p.from, to: p.to })}
                  className={cn('w-full rounded-md px-3 py-2 text-left text-sm', p.from === value.from && p.to === value.to ? 'bg-primary-50 font-medium text-primary' : 'text-slate-600 hover:bg-slate-50')}
                >
                  {p.label}
                </button>
              </li>
            ))}
          </ul>
          <div className="flex-1 space-y-3 p-4">
            <p className="text-sm font-semibold text-slate-800">Custom range</p>
            <label className="block text-xs text-slate-500">From
              <input type="date" value={draft.from} max={draft.to} onChange={(e) => setDraft({ ...draft, from: e.target.value })} className="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-700 focus:border-primary focus:outline-none" />
            </label>
            <label className="block text-xs text-slate-500">To
              <input type="date" value={draft.to} min={draft.from} onChange={(e) => setDraft({ ...draft, to: e.target.value })} className="mt-1 h-10 w-full rounded-lg border border-slate-200 px-3 text-sm text-slate-700 focus:border-primary focus:outline-none" />
            </label>
            <Button size="sm" className="w-full" disabled={!valid} onClick={() => apply(draft)}>Apply</Button>
          </div>
        </div>
      )}
    </div>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const thisMonth = () => {
  const p = presets()[0]
  return { from: p.from, to: p.to }
}
