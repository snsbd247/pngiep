import { CheckCircle2, ExternalLink, MapPin } from 'lucide-react'
import { cn } from '@/lib/utils'

export function SidebarCard({ title, children, className }) {
  return (
    <section className={cn('rounded-2xl border border-slate-100 bg-lavender p-5', className)}>
      {title && <h2 className="mb-4 text-base font-semibold">{title}</h2>}
      {children}
    </section>
  )
}

/** rows: [{ icon: LucideIcon, label, value, href? }] (rows without a value are skipped). */
export function QuickInfo({ title, rows }) {
  const visible = rows.filter((r) => r.value)
  if (!visible.length) return null
  return (
    <SidebarCard title={title}>
      <dl className="space-y-3.5">
        {visible.map(({ icon: Icon, label, value, href }) => (
          <div key={label} className="flex gap-3">
            <span className="grid size-8 shrink-0 place-items-center rounded-lg bg-white text-primary shadow-sm"><Icon className="size-4" /></span>
            <div className="min-w-0">
              <dt className="text-xs text-slate-500">{label}</dt>
              <dd className="text-sm font-medium break-words text-ink">
                {href ? (
                  <a href={href} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-primary hover:underline">
                    {value} <ExternalLink className="size-3" />
                  </a>
                ) : (
                  value
                )}
              </dd>
            </div>
          </div>
        ))}
      </dl>
    </SidebarCard>
  )
}

export function CheckList({ title, items = [] }) {
  if (!items.length) return null
  return (
    <SidebarCard title={title}>
      <ul className="space-y-2.5">
        {items.map((item) => (
          <li key={item} className="flex items-start gap-2 text-sm text-slate-700">
            <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-primary" /> {item}
          </li>
        ))}
      </ul>
    </SidebarCard>
  )
}

/** Google Maps embed by place name (no API key required). */
export function LocationMap({ title, query }) {
  if (!query) return null
  const src = `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed`
  return (
    <SidebarCard title={title}>
      <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <iframe title={query} src={src} loading="lazy" referrerPolicy="no-referrer-when-downgrade" className="h-48 w-full" />
      </div>
      <p className="mt-3 flex items-center justify-center gap-1.5 text-sm text-slate-600"><MapPin className="size-4 text-primary" /> {query}</p>
    </SidebarCard>
  )
}
