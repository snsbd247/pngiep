import { ChevronDown, Search, SlidersHorizontal, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import { cn, formatMoney } from '@/lib/utils'

function CheckboxGroup({ group, selected = [], onChange }) {
  const [open, setOpen] = useState(true)
  const [showAll, setShowAll] = useState(false)
  const options = group.options ?? []
  if (!options.length) return null
  const visible = showAll ? options : options.slice(0, 5)

  const toggle = (value) => onChange(selected.includes(value) ? selected.filter((v) => v !== value) : [...selected, value])

  return (
    <fieldset className="border-t border-slate-100 py-4">
      <legend className="w-full">
        <button type="button" onClick={() => setOpen((o) => !o)} className="flex w-full items-center justify-between text-sm font-semibold text-ink" aria-expanded={open}>
          {group.title}
          <ChevronDown className={cn('size-4 text-slate-400 transition-transform', open && 'rotate-180')} />
        </button>
      </legend>
      {open && (
        <div className="mt-3 space-y-2.5">
          {visible.map((o) => (
            <label key={o.value} className="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 hover:text-ink">
              <input type="checkbox" className="size-4 rounded border-slate-300 accent-primary" checked={selected.includes(o.value)} onChange={() => toggle(o.value)} />
              {o.country_code && <Flag code={o.country_code} className="h-3 w-4" />}
              <span className="flex-1">{o.label}</span>
              {o.count !== undefined && <span className="text-xs text-slate-400">({o.count})</span>}
            </label>
          ))}
          {options.length > 5 && (
            <button type="button" onClick={() => setShowAll((s) => !s)} className="text-xs font-semibold text-primary">
              {showAll ? '− Show less' : `+ Show more (${options.length - 5})`}
            </button>
          )}
        </div>
      )}
    </fieldset>
  )
}

function RangeFilter({ title, max, value, currency, onChange }) {
  const [draft, setDraft] = useState(value ?? max)
  useEffect(() => setDraft(value ?? max), [value, max])
  if (!max) return null

  return (
    <fieldset className="border-t border-slate-100 py-4">
      <legend className="text-sm font-semibold text-ink">{title}</legend>
      <input
        type="range"
        min={0}
        max={max}
        step={Math.max(100, Math.round(max / 100 / 100) * 100)}
        value={draft}
        onChange={(e) => setDraft(Number(e.target.value))}
        onPointerUp={() => onChange(draft >= max ? '' : draft)}
        onKeyUp={() => onChange(draft >= max ? '' : draft)}
        className="mt-4 w-full accent-primary"
        aria-label={title}
      />
      <div className="mt-1 flex justify-between text-xs text-slate-500">
        <span>{formatMoney(0, currency)}</span>
        <span className="font-semibold text-ink">{draft >= max ? `${formatMoney(max, currency)}+` : `≤ ${formatMoney(draft, currency)}`}</span>
      </div>
    </fieldset>
  )
}

/**
 * Filter panel for directory lists. On mobile it opens as a drawer.
 * groups: [{ key, title, options: [{ value, label, count?, country_code? }] }]
 */
export default function FilterSidebar({ title, search, onSearch, searchPlaceholder, groups = [], params, onChange, onClear, range, footer }) {
  const [open, setOpen] = useState(false)
  const [term, setTerm] = useState(search ?? '')
  useEffect(() => setTerm(search ?? ''), [search])
  const activeCount = groups.reduce((n, g) => n + (params[g.key]?.length ?? 0), 0) + (range?.value ? 1 : 0)

  const panel = (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card">
      {onSearch && (
        <form
          role="search"
          onSubmit={(e) => {
            e.preventDefault()
            onSearch(term.trim())
          }}
          className="mb-5"
        >
          {title && <h2 className="mb-3 text-base font-semibold">{title}</h2>}
          <div className="flex gap-2">
            <input
              type="search"
              value={term}
              onChange={(e) => setTerm(e.target.value)}
              placeholder={searchPlaceholder}
              aria-label={searchPlaceholder}
              className="h-11 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm focus:border-primary focus:ring-4 focus:ring-primary-100 focus:outline-none"
            />
            <Button type="submit" size="icon" className="size-11" aria-label="Search"><Search className="size-4" /></Button>
          </div>
        </form>
      )}
      <div className="flex items-center justify-between pb-2">
        <h2 className="text-base font-semibold">Filter By</h2>
        {activeCount > 0 && <button type="button" onClick={onClear} className="text-xs font-semibold text-primary">Clear All</button>}
      </div>
      {groups.map((g) => (
        <CheckboxGroup key={g.key} group={g} selected={params[g.key]} onChange={(values) => onChange({ [g.key]: values })} />
      ))}
      {range && <RangeFilter {...range} />}
      {footer && <div className="mt-2 space-y-2 border-t border-slate-100 pt-4">{footer}</div>}
    </div>
  )

  return (
    <>
      <Button variant="outline" className="w-full lg:hidden" onClick={() => setOpen(true)}>
        <SlidersHorizontal className="size-4" /> Filters {activeCount > 0 && `(${activeCount})`}
      </Button>
      <div className="hidden lg:block">{panel}</div>
      {open && (
        <div className="fixed inset-0 z-[80] lg:hidden" role="dialog" aria-modal="true" aria-label="Filters">
          <div className="absolute inset-0 bg-slate-900/50" onClick={() => setOpen(false)} />
          <div className="absolute inset-y-0 left-0 w-full max-w-sm overflow-y-auto bg-slate-50 p-4">
            <div className="mb-3 flex items-center justify-between">
              <span className="font-semibold">Filters</span>
              <button type="button" onClick={() => setOpen(false)} className="grid size-10 place-items-center rounded-full hover:bg-slate-200" aria-label="Close filters"><X className="size-5" /></button>
            </div>
            {panel}
            <Button className="mt-4 w-full" onClick={() => setOpen(false)}>Show results</Button>
          </div>
        </div>
      )}
    </>
  )
}
