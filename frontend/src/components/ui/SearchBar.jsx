import { Search, X } from 'lucide-react'
import { useEffect, useState } from 'react'
import { cn } from '@/lib/utils'

/** Debounced search input. onSearch fires `delay` ms after typing stops. */
export default function SearchBar({ value = '', onSearch, placeholder = 'Search…', delay = 400, className, size = 'md' }) {
  const [term, setTerm] = useState(value)

  useEffect(() => setTerm(value), [value])

  useEffect(() => {
    if (term === value) return undefined
    const t = setTimeout(() => onSearch(term.trim()), delay)
    return () => clearTimeout(t)
  }, [term, value, delay, onSearch])

  return (
    <div className={cn('relative', className)}>
      <Search className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-slate-400" />
      <input
        type="search"
        value={term}
        onChange={(e) => setTerm(e.target.value)}
        placeholder={placeholder}
        aria-label={placeholder}
        className={cn(
          'w-full rounded-full border border-slate-200 bg-white pr-10 pl-11 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-100 focus:outline-none [&::-webkit-search-cancel-button]:hidden',
          size === 'lg' ? 'h-14' : 'h-11',
        )}
      />
      {term && (
        <button type="button" onClick={() => { setTerm(''); onSearch('') }} className="absolute top-1/2 right-3 -translate-y-1/2 rounded-full p-1 text-slate-400 hover:text-slate-600" aria-label="Clear search">
          <X className="size-4" />
        </button>
      )}
    </div>
  )
}
