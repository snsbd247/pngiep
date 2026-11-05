import Pagination from '@/components/ui/Pagination'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { cn } from '@/lib/utils'

/**
 * Generic admin table driven by a React Query result of the API envelope ({ data, meta }).
 * columns: [{ key, header, render?(row), className? }]
 * On small screens rows collapse into stacked cards, so tables never overflow the viewport.
 */
export default function DataTable({ columns, query, onPageChange, emptyTitle = 'No records found', emptyDescription, rowKey = 'id' }) {
  if (query.isPending) return <LoadingSkeleton variant="table" />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />

  const rows = query.data?.data ?? []
  if (!rows.length) return <EmptyState title={emptyTitle} description={emptyDescription} />

  const cell = (col, row) => (col.render ? col.render(row) : row[col.key] ?? '—')

  return (
    <div className={cn('transition-opacity', query.isFetching && 'opacity-60')}>
      <div className="hidden overflow-x-auto rounded-2xl border border-slate-200/80 bg-white shadow-card md:block">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold tracking-wide text-slate-500 uppercase">
            <tr>
              {columns.map((col) => (
                <th key={col.key} scope="col" className={cn('px-5 py-3.5 whitespace-nowrap', col.className)}>{col.header}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((row) => (
              <tr key={row[rowKey]} className="hover:bg-slate-50/60">
                {columns.map((col) => (
                  <td key={col.key} className={cn('px-5 py-3.5 align-middle text-slate-700', col.className)}>{cell(col, row)}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <ul className="space-y-3 md:hidden">
        {rows.map((row) => (
          <li key={row[rowKey]} className="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-card">
            <dl className="space-y-2">
              {columns.map((col) => (
                <div key={col.key} className="flex items-start justify-between gap-4 text-sm">
                  {col.header && <dt className="shrink-0 text-xs font-semibold text-slate-400 uppercase">{col.header}</dt>}
                  <dd className="min-w-0 text-right text-slate-700">{cell(col, row)}</dd>
                </div>
              ))}
            </dl>
          </li>
        ))}
      </ul>

      {onPageChange && <Pagination className="mt-5" meta={query.data?.meta} onPageChange={onPageChange} />}
    </div>
  )
}
