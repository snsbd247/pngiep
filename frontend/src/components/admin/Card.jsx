import { cn } from '@/lib/utils'

export default function Card({ id, title, description, actions, className, bodyClassName, children }) {
  return (
    <section id={id} className={cn('scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white shadow-card', className)}>
      {(title || actions) && (
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
          <div>
            {title && <h2 className="text-base font-semibold">{title}</h2>}
            {description && <p className="mt-0.5 text-sm text-slate-500">{description}</p>}
          </div>
          {actions}
        </div>
      )}
      <div className={cn('p-5', bodyClassName)}>{children}</div>
    </section>
  )
}
