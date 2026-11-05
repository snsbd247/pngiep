import { iconNames } from 'lucide-react/dynamic'
import { ChevronDown, X } from 'lucide-react'
import { useMemo, useState } from 'react'
import AppIcon from '@/components/ui/AppIcon'
import Modal from '@/components/ui/Modal'
import SearchBar from '@/components/ui/SearchBar'
import { cn } from '@/lib/utils'

/** Common choices shown before the admin searches the full icon set. */
const SUGGESTED = [
  'graduation-cap', 'school', 'landmark', 'earth', 'globe', 'plane', 'users', 'user-round', 'book-open', 'file-text', 'file-check',
  'stamp', 'award', 'trophy', 'star', 'shield-check', 'handshake', 'headset', 'house', 'briefcase-business', 'chart-column-increasing',
  'target', 'eye', 'message-circle-more', 'calendar-check', 'clock', 'map-pin', 'phone', 'mail', 'layers', 'flask-conical', 'sprout',
  'lightbulb', 'heart-handshake', 'badge-check', 'wallet', 'circle-dollar-sign', 'building-2', 'compass', 'rocket',
]

/** Searchable picker over all Lucide icons; stores the kebab-case icon name. */
export default function IconPicker({ value, onChange, invalid }) {
  const [open, setOpen] = useState(false)
  const [term, setTerm] = useState('')

  const results = useMemo(() => {
    if (!term) return SUGGESTED
    const t = term.toLowerCase().replace(/\s+/g, '-')
    return iconNames.filter((n) => n.includes(t)).slice(0, 120)
  }, [term])

  return (
    <>
      <div className="flex gap-2">
        <button
          type="button"
          onClick={() => setOpen(true)}
          className={cn('flex h-11 flex-1 items-center gap-3 rounded-xl border bg-white px-3 text-left text-sm', invalid ? 'border-rose-400' : 'border-slate-200 hover:border-primary-300')}
        >
          <span className="grid size-7 place-items-center rounded-lg bg-primary-50 text-primary">{value ? <AppIcon name={value} className="size-4" /> : null}</span>
          <span className={cn('flex-1 truncate', !value && 'text-slate-400')}>{value || 'Choose an icon'}</span>
          <ChevronDown className="size-4 text-slate-400" />
        </button>
        {value && (
          <button type="button" onClick={() => onChange(null)} className="grid size-11 place-items-center rounded-xl border border-slate-200 text-slate-400 hover:text-rose-600" aria-label="Clear icon">
            <X className="size-4" />
          </button>
        )}
      </div>
      <Modal open={open} onClose={() => setOpen(false)} title="Choose an icon" size="lg">
        <SearchBar value={term} onSearch={setTerm} delay={150} placeholder={`Search ${iconNames.length} icons…`} className="mb-4" />
        <div className="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8">
          {results.map((name) => (
            <button
              key={name}
              type="button"
              title={name}
              onClick={() => {
                onChange(name)
                setOpen(false)
              }}
              className={cn('flex flex-col items-center gap-1 rounded-xl border p-2 text-[10px] text-slate-500 transition', value === name ? 'border-primary bg-primary-50 text-primary' : 'border-slate-100 hover:border-primary-200')}
            >
              <AppIcon name={name} className="size-6" />
              <span className="w-full truncate">{name}</span>
            </button>
          ))}
        </div>
        {!results.length && <p className="py-8 text-center text-sm text-slate-500">No icons match “{term}”.</p>}
      </Modal>
    </>
  )
}
