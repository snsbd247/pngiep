import { useQuery } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import DateRangePicker from '@/components/admin/dashboard/DateRangePicker'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { formatDateTime } from '@/lib/utils'
import { leadSourceService } from '@/services/admin'

/** Leads › Lead Sources: where enquiries come from and how well each source converts. */
export default function LeadSourcesPage() {
  const [range, setRange] = useState(null) // null = all time
  const query = useQuery({ queryKey: ['admin', 'leads', 'sources', range], queryFn: () => leadSourceService.get(range ?? {}), placeholderData: (p) => p })
  const rows = query.data?.data ?? []
  const max = Math.max(1, ...rows.map((r) => r.total))

  return (
    <>
      <PageHeader
        title="Lead Sources"
        description="Where enquiries come from, and how many become students."
        actions={
          <div className="flex flex-wrap gap-2">
            {range && <Button variant="outline" onClick={() => setRange(null)}>All time</Button>}
            <DateRangePicker value={range ?? { from: '', to: '' }} onChange={setRange} />
          </div>
        }
      />
      {query.isPending ? <LoadingSkeleton variant="table" /> : query.isError ? <ErrorState error={query.error} onRetry={query.refetch} /> : (
        <Card bodyClassName="p-0" title={`${query.data.meta.total} enquiries`} description={range ? `${range.from} – ${range.to}` : 'All time'}>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[720px] text-left text-sm">
              <thead className="bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                  <th className="px-5 py-3 font-semibold">Source</th>
                  <th className="px-5 py-3 font-semibold">Enquiries</th>
                  <th className="px-5 py-3 font-semibold">New</th>
                  <th className="px-5 py-3 font-semibold">Converted</th>
                  <th className="px-5 py-3 font-semibold">Conversion</th>
                  <th className="px-5 py-3 font-semibold">Latest</th>
                  <th className="px-5 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {rows.map((r) => (
                  <tr key={r.source} className="text-slate-700">
                    <td className="px-5 py-3 font-medium text-slate-800">{r.label}</td>
                    <td className="px-5 py-3">
                      <div className="flex items-center gap-3">
                        <span className="w-8 text-right font-semibold">{r.total}</span>
                        <span className="h-2 w-40 overflow-hidden rounded-full bg-slate-100"><span className="block h-full rounded-full bg-primary" style={{ width: `${(r.total / max) * 100}%` }} /></span>
                        <span className="text-xs text-slate-400">{r.share}%</span>
                      </div>
                    </td>
                    <td className="px-5 py-3">{r.new}</td>
                    <td className="px-5 py-3">{r.converted}</td>
                    <td className="px-5 py-3"><span className={r.conversion_rate >= 20 ? 'font-semibold text-emerald-600' : ''}>{r.conversion_rate}%</span></td>
                    <td className="px-5 py-3 text-xs whitespace-nowrap text-slate-500">{r.last_at ? formatDateTime(r.last_at) : '—'}</td>
                    <td className="px-5 py-3 text-right">
                      {r.total > 0 && <Link to={`/admin/leads?source=${r.source}`} className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline">View leads <ArrowRight className="size-3.5" /></Link>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
    </>
  )
}
