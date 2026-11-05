import { useQuery } from '@tanstack/react-query'
import { CalendarCheck, Eye } from 'lucide-react'
import { Link } from 'react-router-dom'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import FormInput from '@/components/forms/FormInput'
import FormSelect from '@/components/forms/FormSelect'
import { MODES, formatLongDate, formatTime } from '@/components/public/BookingWidgets'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import SearchBar from '@/components/ui/SearchBar'
import { useAuth } from '@/context/AuthContext'
import { useListParams } from '@/hooks/useListParams'
import { cn } from '@/lib/utils'
import { appointmentAdminService, lookupService } from '@/services/admin'
import { StatusTabs, labelOf, useMeta } from './shared'

const VIEWS = [
  { value: 'today', label: 'Today' },
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'past', label: 'Past' },
  { value: 'all', label: 'All' },
]

/** Admin appointment dashboard. Counsellors see only their own bookings (scoped by the API). */
export default function AppointmentsPage() {
  const { can } = useAuth()
  const meta = useMeta()
  const [params, setParams] = useListParams({ view: 'upcoming' })
  const query = useQuery({ queryKey: ['admin', 'appointments', params], queryFn: () => appointmentAdminService.list(params), placeholderData: (p) => p })
  const manageAll = can('appointments.manage')
  const counsellors = useQuery({ queryKey: ['admin', 'lookups', 'counsellors'], queryFn: () => lookupService.get(['counsellors']), enabled: manageAll }).data?.counsellors ?? []
  const statuses = meta.appointment_statuses ?? []

  const columns = [
    { key: 'when', header: 'Date & time', className: 'whitespace-nowrap', render: (a) => (
      <Link to={`/admin/appointments/${a.id}`} className="font-medium text-slate-800 hover:text-primary">
        {formatLongDate(a.appointment_date?.slice(0, 10), { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}
        <span className="block text-xs font-normal text-slate-500">{formatTime(a.start_time?.slice(0, 5))} – {formatTime(a.end_time?.slice(0, 5))}</span>
      </Link>
    ) },
    { key: 'name', header: 'Student', render: (a) => <>{a.name}<span className="block text-xs text-slate-500">{a.phone}</span></> },
    { key: 'service', header: 'Service', render: (a) => a.service?.title ?? '—' },
    ...(manageAll ? [{ key: 'counsellor', header: 'Counsellor', render: (a) => a.counsellor?.name ?? '—' }] : []),
    { key: 'mode', header: 'Mode', render: (a) => MODES[a.mode]?.label ?? a.mode },
    { key: 'status', header: 'Status', render: (a) => <Badge status={a.status}>{labelOf(statuses, a.status)}</Badge> },
    { key: 'actions', header: '', className: 'text-right', render: (a) => <Button to={`/admin/appointments/${a.id}`} variant="ghost" size="icon" aria-label="Open"><Eye className="size-4" /></Button> },
  ]

  const m = query.data?.meta
  return (
    <>
      <PageHeader
        title="Appointments"
        description={manageAll ? 'All consultation bookings.' : 'Your consultation bookings.'}
        actions={<span className="inline-flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700"><CalendarCheck className="size-4" /> {m?.today_count ?? 0} today</span>}
      />
      <div className="mb-4 inline-flex rounded-xl border border-slate-200 bg-white p-1">
        {VIEWS.map((v) => (
          <button key={v.value} type="button" onClick={() => setParams({ view: v.value })}
            className={cn('rounded-lg px-4 py-1.5 text-sm font-medium transition', params.view === v.value ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100')}>
            {v.label}
          </button>
        ))}
      </div>
      <StatusTabs options={statuses} counts={m?.counts} value={params.status ?? ''} onChange={(status) => setParams({ status })} />
      <div className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <SearchBar className="sm:col-span-2 xl:col-span-1" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Name, email, phone, reference…" />
        {manageAll && <FormSelect aria-label="Counsellor" placeholder="All counsellors" value={params.counsellor_id ?? ''} onChange={(e) => setParams({ counsellor_id: e.target.value })} options={counsellors} />}
        <FormInput type="date" aria-label="From date" value={params.date_from ?? ''} onChange={(e) => setParams({ date_from: e.target.value })} />
        <FormInput type="date" aria-label="To date" value={params.date_to ?? ''} onChange={(e) => setParams({ date_to: e.target.value })} />
      </div>
      {m?.timezone && <p className="mb-3 text-xs text-slate-500">Times in {m.timezone}.</p>}
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} emptyTitle="No appointments" emptyDescription="Try another view or filter." />
    </>
  )
}
