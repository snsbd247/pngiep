import { useQuery } from '@tanstack/react-query'
import { CalendarOff, Pencil } from 'lucide-react'
import Avatar from '@/components/admin/Avatar'
import PageHeader from '@/components/admin/PageHeader'
import { fmtDate } from '@/components/admin/dashboard/DateRangePicker'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { cn } from '@/lib/utils'
import { counsellorAvailabilityService } from '@/services/admin'

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

/** Counsellors › Availability: everyone's weekly hours and upcoming days off at a glance. */
export default function CounsellorAvailabilityPage() {
  const query = useQuery({ queryKey: ['admin', 'counsellors', 'availability'], queryFn: counsellorAvailabilityService.get })
  const rows = query.data?.data ?? []
  const working = query.data?.meta?.working_days ?? []

  return (
    <>
      <PageHeader
        title="Availability"
        description={`Weekly bookable hours${query.data?.meta?.timezone ? ` (${query.data.meta.timezone})` : ''}. Edit a counsellor to change their hours or add days off.`}
        actions={<Button to="/admin/settings/appointment" variant="outline">Appointment settings</Button>}
      />
      {query.isPending ? <LoadingSkeleton variant="table" /> : query.isError ? <ErrorState error={query.error} onRetry={query.refetch} /> : !rows.length ? (
        <EmptyState title="No counsellors yet" action={<Button to="/admin/content/counsellors/new" size="sm">Add counsellor</Button>} />
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200/80 bg-white shadow-card">
          <table className="w-full min-w-[900px] text-left text-sm">
            <thead className="bg-slate-50 text-xs text-slate-500 uppercase">
              <tr>
                <th className="px-4 py-3 font-semibold">Counsellor</th>
                {DAYS.map((d, i) => <th key={d} className={cn('px-2 py-3 text-center font-semibold', !working.includes(i) && 'text-slate-300')}>{d}</th>)}
                <th className="px-4 py-3 font-semibold">Upcoming days off</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 align-top">
              {rows.map((c) => (
                <tr key={c.id}>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-3">
                      <Avatar name={c.name} src={c.photo} className="size-9 text-xs" />
                      <div>
                        <p className="font-medium text-slate-800">{c.name}</p>
                        <p className="text-xs text-slate-500">{c.slot_duration} min · {c.week_bookings} booked this week</p>
                        {c.status !== 'published' && <Badge status={c.status} className="mt-1" />}
                      </div>
                    </div>
                  </td>
                  {DAYS.map((d, i) => (
                    <td key={d} className={cn('px-1.5 py-3 text-center', !working.includes(i) && 'bg-slate-50/70')}>
                      {(c.days[i] ?? []).length ? (
                        <div className="space-y-1">{c.days[i].map((w) => <span key={w} className="block rounded-md bg-primary-50 px-1.5 py-1 text-[11px] font-medium whitespace-nowrap text-primary-700">{w}</span>)}</div>
                      ) : <span className="text-xs text-slate-300">—</span>}
                    </td>
                  ))}
                  <td className="px-4 py-3">
                    {!c.time_offs.length ? <span className="text-xs text-slate-400">None</span> : (
                      <ul className="space-y-1">
                        {c.time_offs.map((t, k) => (
                          <li key={k} className="flex items-center gap-1.5 text-xs text-slate-600">
                            <CalendarOff className="size-3.5 text-rose-500" />
                            {fmtDate(t.date)}{t.start_time ? `, ${t.start_time}–${t.end_time}` : ' (all day)'}{t.reason ? ` · ${t.reason}` : ''}
                          </li>
                        ))}
                      </ul>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Button to={`/admin/content/counsellors/${c.id}`} variant="ghost" size="icon" aria-label={`Edit ${c.name}`}><Pencil className="size-4" /></Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </>
  )
}
