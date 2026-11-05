import { AlertTriangle, Inbox, RotateCcw } from 'lucide-react'
import { cn } from '@/lib/utils'
import Button from './Button'

/** Shimmering placeholder block. */
export function Skeleton({ className }) {
  return (
    <div className={cn('relative overflow-hidden rounded-lg bg-slate-200/70', className)}>
      <div className="absolute inset-0 -translate-x-full animate-[shimmer_1.6s_infinite] bg-gradient-to-r from-transparent via-white/60 to-transparent" />
    </div>
  )
}

/** Preset skeleton layouts used by API-driven pages. */
export function LoadingSkeleton({ variant = 'cards', count = 6, className }) {
  if (variant === 'table') {
    return (
      <div className={cn('space-y-3', className)} aria-busy="true" aria-label="Loading">
        {Array.from({ length: count }).map((_, i) => (
          <Skeleton key={i} className="h-12 w-full" />
        ))}
      </div>
    )
  }

  if (variant === 'page') {
    return (
      <div className={cn('space-y-6', className)} aria-busy="true" aria-label="Loading">
        <Skeleton className="h-64 w-full rounded-3xl" />
        <div className="container-page space-y-4">
          <Skeleton className="h-8 w-1/2" />
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-5/6" />
          <Skeleton className="h-4 w-2/3" />
        </div>
      </div>
    )
  }

  return (
    <div className={cn('grid gap-6 sm:grid-cols-2 lg:grid-cols-3', className)} aria-busy="true" aria-label="Loading">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="overflow-hidden rounded-[var(--radius-card)] border border-slate-100 bg-white p-4">
          <Skeleton className="aspect-[4/3] w-full rounded-xl" />
          <Skeleton className="mt-4 h-5 w-3/4" />
          <Skeleton className="mt-2 h-4 w-1/2" />
        </div>
      ))}
    </div>
  )
}

export function EmptyState({ title = 'Nothing here yet', description, icon: Icon = Inbox, action, className }) {
  return (
    <div className={cn('flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-14 text-center', className)}>
      <span className="mb-4 grid size-14 place-items-center rounded-2xl bg-primary-50 text-primary">
        <Icon className="size-7" />
      </span>
      <h3 className="text-lg font-semibold">{title}</h3>
      {description && <p className="mt-1 max-w-md text-sm text-slate-500">{description}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  )
}

export function ErrorState({ title = 'Something went wrong', error, onRetry, className }) {
  return (
    <div role="alert" className={cn('flex flex-col items-center justify-center rounded-2xl border border-rose-100 bg-rose-50/50 px-6 py-14 text-center', className)}>
      <span className="mb-4 grid size-14 place-items-center rounded-2xl bg-rose-100 text-rose-600">
        <AlertTriangle className="size-7" />
      </span>
      <h3 className="text-lg font-semibold">{title}</h3>
      <p className="mt-1 max-w-md text-sm text-slate-500">{error?.message || 'We could not load this content.'}</p>
      {onRetry && (
        <Button variant="outline" size="sm" className="mt-5" onClick={() => onRetry()}>
          <RotateCcw className="size-4" /> Try again
        </Button>
      )}
    </div>
  )
}

/**
 * Renders loading / error / empty / content for a React Query result.
 * <QueryState query={q} isEmpty={(d) => !d.length}>{(data) => …}</QueryState>
 */
export function QueryState({ query, children, skeleton, isEmpty, empty }) {
  if (query.isPending) return skeleton ?? <LoadingSkeleton />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />
  if (isEmpty?.(query.data)) return empty ?? <EmptyState />
  return children(query.data)
}
