import { ChevronRight, Home } from 'lucide-react'
import { Link } from 'react-router-dom'
import { cn } from '@/lib/utils'

/** items: [{ label, to? }] — the last item is the current page. */
export default function Breadcrumb({ items = [], light = false, className }) {
  if (!items.length) return null

  return (
    <nav aria-label="Breadcrumb" className={className}>
      <ol className={cn('flex flex-wrap items-center gap-1.5 text-sm', light ? 'text-white/75' : 'text-slate-500')}>
        <li>
          <Link to="/" className={cn('flex items-center', light ? 'hover:text-white' : 'hover:text-primary')} aria-label="Home">
            <Home className="size-4" />
          </Link>
        </li>
        {items.map((item, i) => {
          const last = i === items.length - 1
          return (
            <li key={`${item.label}-${i}`} className="flex items-center gap-1.5">
              <ChevronRight className="size-3.5 opacity-60" />
              {item.to && !last ? (
                <Link to={item.to} className={light ? 'hover:text-white' : 'hover:text-primary'}>{item.label}</Link>
              ) : (
                <span aria-current={last ? 'page' : undefined} className={cn('font-medium', light ? 'text-white' : 'text-slate-800')}>
                  {item.label}
                </span>
              )}
            </li>
          )
        })}
      </ol>
    </nav>
  )
}
