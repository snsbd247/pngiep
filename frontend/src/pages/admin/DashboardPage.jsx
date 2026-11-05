import { useQuery } from '@tanstack/react-query'
import {
  ArrowDown,
  ArrowRight,
  ArrowUp,
  BookOpen,
  CalendarCheck,
  CalendarDays,
  ChartColumn,
  ChevronRight,
  EllipsisVertical,
  FileText,
  GraduationCap,
  ImageIcon,
  Landmark,
  Mail,
  Plane,
  Settings,
  UserRound,
  Users,
} from 'lucide-react'
import { useState } from 'react'
import { Helmet } from 'react-helmet-async'
import { Link } from 'react-router-dom'
import Avatar from '@/components/admin/Avatar'
import { Donut, LineChart } from '@/components/admin/dashboard/Charts'
import DateRangePicker, { fmtDate, thisMonth } from '@/components/admin/dashboard/DateRangePicker'
import Button from '@/components/ui/Button'
import { ErrorState } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { useSite } from '@/context/SiteContext'
import { cn } from '@/lib/utils'
import { saveBlob } from '@/pages/admin/crm/shared'
import { dashboardService } from '@/services/admin'

/* ------------------------------------------------------------------ design tokens (from the admin design) */

const CARDS = {
  enquiries: { label: 'Total Enquiries', icon: Users, color: '#5b3df5', bg: '#eeeaff', to: '/admin/leads' },
  appointments: { label: 'Total Appointments', icon: CalendarDays, color: '#2f7cf6', bg: '#e6f0ff', to: '/admin/appointments' },
  applications: { label: 'Total Applications', icon: FileText, color: '#22b573', bg: '#e3f7ec', to: '/admin/applications' },
  success_stories: { label: 'Success Stories', icon: GraduationCap, color: '#f59e0b', bg: '#fff1dc', to: '/admin/content/success-stories' },
}

const TOTALS = {
  destinations: { label: 'Destinations', hint: 'Total study destinations', icon: Plane, color: '#f04438', bg: '#feecea', to: '/admin/content/destinations' },
  universities: { label: 'Universities', hint: 'Active partner universities', icon: Landmark, color: '#5b3df5', bg: '#efebff', to: '/admin/content/universities' },
  courses: { label: 'Courses', hint: 'Total available courses', icon: BookOpen, color: '#4f46e5', bg: '#eceafe', to: '/admin/content/courses' },
  services: { label: 'Services', hint: 'Total support services', icon: Settings, color: '#14b8a6', bg: '#dff7f3', to: '/admin/content/services' },
  blogs: { label: 'Blog / Notice', hint: 'Published articles', icon: FileText, color: '#ec4899', bg: '#fdebf4', to: '/admin/content/blogs' },
}

const SERIES = [
  { key: 'enquiries', label: 'Enquiries', color: '#6d4aff', area: 0.18 },
  { key: 'appointments', label: 'Appointments', color: '#3b82f6', area: 0.1 },
  { key: 'applications', label: 'Applications', color: '#22c55e', area: 0.08 },
]

const SOURCE_COLORS = {
  'Contact Form': '#5b3df5',
  Appointment: '#3b82f6',
  'Apply Now': '#22c55e',
  'University Enquiry': '#f97316',
  'Course Enquiry': '#d062c9',
  'Website/Other': '#8b5cf6',
}

const PILL = {
  blue: 'bg-sky-50 text-sky-600',
  amber: 'bg-amber-50 text-amber-600',
  green: 'bg-emerald-50 text-emerald-600',
  violet: 'bg-violet-50 text-violet-600',
  indigo: 'bg-indigo-50 text-indigo-600',
  orange: 'bg-orange-50 text-orange-600',
  red: 'bg-rose-50 text-rose-600',
  slate: 'bg-slate-100 text-slate-600',
}
const STATUS_TONE = {
  // leads
  new: 'blue', contacted: 'amber', interested: 'green', follow_up: 'indigo', converted: 'green', closed: 'slate',
  // appointments
  pending: 'amber', confirmed: 'green', completed: 'blue', cancelled: 'red', no_show: 'red', rescheduled: 'slate',
  // applications
  documents_pending: 'orange', under_review: 'violet', submitted: 'blue', processing: 'violet', rejected: 'red',
}

const ACTIVITY = {
  application: { icon: FileText, cls: 'bg-violet-50 text-violet-600' },
  appointment: { icon: CalendarCheck, cls: 'bg-emerald-50 text-emerald-600' },
  enquiry: { icon: UserRound, cls: 'bg-sky-50 text-sky-600' },
  message: { icon: Mail, cls: 'bg-indigo-50 text-indigo-600' },
  blog: { icon: FileText, cls: 'bg-rose-50 text-rose-500' },
  gallery: { icon: ImageIcon, cls: 'bg-amber-50 text-amber-600' },
}

/* ------------------------------------------------------------------ helpers */

const shortDate = (iso) => (iso ? fmtDate(iso) : '—')
const dayTime = (date, time) => {
  if (!date) return '—'
  const d = Number(date.split('-')[2])
  const [hh, mm] = (time ?? '00:00').split(':').map(Number)
  return `${d} ${fmtDate(date).split(' ')[1]}, ${((hh + 11) % 12) + 1}:${String(mm).padStart(2, '0')} ${hh < 12 ? 'AM' : 'PM'}`
}
const ago = (iso) => {
  const secs = (Date.now() - new Date(iso).getTime()) / 1000
  const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })
  for (const [unit, s] of [['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60]]) {
    if (secs >= s) return rtf.format(-Math.floor(secs / s), unit)
  }
  return 'just now'
}
/** "PNG International Education Portal" → "PNGIEP": all-caps words kept, others reduced to their initial. */
const shortName = (name = '') => name.split(/\s+/).filter(Boolean).map((w) => (w === w.toUpperCase() ? w : w[0])).join('').toUpperCase()

function Pill({ status, children }) {
  return <span className={cn('inline-block rounded-[5px] px-2 py-[2px] text-[10.5px] leading-[14px] font-medium whitespace-nowrap', PILL[STATUS_TONE[status] ?? 'slate'])}>{children}</span>
}

function Panel({ title, to, action, className, children }) {
  return (
    <section className={cn('flex min-w-0 flex-col rounded-xl border border-slate-200/60 bg-white px-4 pt-2.5 pb-2 shadow-[0_1px_3px_rgb(17_12_46_/_0.04)]', className)}>
      <div className="mb-1.5 flex items-center justify-between gap-3">
        <h2 className="text-[15px] leading-5 font-bold text-slate-900">{title}</h2>
        {to ? (
          <Link to={to} className="inline-flex items-center gap-1.5 text-[12px] font-medium text-primary hover:underline">
            View All <ArrowRight className="size-3.5" />
          </Link>
        ) : action}
      </div>
      {children}
    </section>
  )
}

function Empty({ children = 'Nothing here yet.' }) {
  return <p className="py-8 text-center text-sm text-slate-400">{children}</p>
}

function MiniTable({ head, rows }) {
  return (
    <div className="-mx-1 overflow-x-auto">
      <table className="w-full min-w-[330px] text-left text-[11.5px]">
        <thead>
          <tr className="bg-[#f6f7fb] text-[10.5px] text-slate-600">
            {head.map((h, i) => <th key={i} className={cn('px-1.5 py-1 font-medium whitespace-nowrap first:rounded-l-md last:rounded-r-md', i === 0 && 'w-5')}>{h}</th>)}
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">{rows}</tbody>
      </table>
    </div>
  )
}

/* ------------------------------------------------------------------ cards */

function StatCard({ card, compare }) {
  const meta = CARDS[card.key]
  const up = (card.change ?? 0) >= 0
  return (
    <Link to={meta.to} className="flex h-[88px] items-center gap-[22px] rounded-xl border border-slate-200/60 bg-white px-4 shadow-[0_1px_3px_rgb(17_12_46_/_0.04)] transition hover:-translate-y-0.5 hover:shadow-md">
      <span className="grid size-16 shrink-0 place-items-center rounded-full" style={{ background: meta.bg, color: meta.color }}>
        <meta.icon className="size-7" strokeWidth={2} fill={meta.key === 'none' ? 'currentColor' : 'none'} />
      </span>
      <span className="min-w-0">
        <span className="block text-[12px] font-medium text-slate-700">{meta.label}</span>
        <span className="block text-[22px] leading-[30px] font-bold text-slate-900">{card.value.toLocaleString()}</span>
        <span className="flex items-center gap-1.5 text-[11.5px] whitespace-nowrap">
          {card.change === null ? (
            <>
              <span className="inline-flex items-center gap-0.5 font-semibold text-emerald-500"><ArrowUp className="size-3.5" strokeWidth={3} />{card.value} new</span>
              <span className="ml-2 text-slate-500">{compare}</span>
            </>
          ) : (
            <>
              <span className={cn('inline-flex items-center gap-0.5 font-semibold', up ? 'text-emerald-500' : 'text-rose-500')}>
                {up ? <ArrowUp className="size-3.5" strokeWidth={3} /> : <ArrowDown className="size-3.5" strokeWidth={3} />}
                {Math.abs(Math.round(card.change))}%
              </span>
              <span className="ml-2 text-slate-500">{compare}</span>
            </>
          )}
        </span>
      </span>
    </Link>
  )
}

function TotalCard({ total }) {
  const meta = TOTALS[total.key]
  return (
    <Link to={meta.to} className="flex h-[88px] items-center gap-3.5 rounded-xl border border-slate-200/60 bg-white px-3.5 shadow-[0_1px_3px_rgb(17_12_46_/_0.04)] transition hover:-translate-y-0.5 hover:shadow-md">
      <span className="grid size-[58px] shrink-0 place-items-center rounded-full" style={{ background: meta.bg, color: meta.color }}>
        <meta.icon className="size-7" strokeWidth={2} />
      </span>
      <span className="min-w-0">
        <span className="block text-[12px] font-medium text-slate-700">{meta.label}</span>
        <span className="block text-[20px] leading-[28px] font-bold text-slate-900">{total.value.toLocaleString()}</span>
        <span className="flex items-center gap-1.5 truncate text-[11px] text-slate-500">
          {total.new > 0 && total.new < total.value && <span className="inline-flex items-center gap-0.5 font-semibold text-emerald-500"><ArrowUp className="size-3.5" strokeWidth={3} />{total.new}</span>}
          {meta.hint}
        </span>
      </span>
    </Link>
  )
}

/* ------------------------------------------------------------------ page */

export default function DashboardPage() {
  const { user } = useAuth()
  const { setting } = useSite()
  const toast = useToast()
  const [range, setRange] = useState(thisMonth)
  const [exporting, setExporting] = useState(false)
  const query = useQuery({ queryKey: ['admin', 'dashboard', range], queryFn: () => dashboardService.get(range), placeholderData: (p) => p })
  const d = query.data

  const report = async () => {
    setExporting(true)
    try {
      saveBlob(await dashboardService.report(range), `report-${range.from}-to-${range.to}.csv`)
    } catch {
      toast.error('Could not generate the report.')
    } finally {
      setExporting(false)
    }
  }

  const sources = (d?.sources?.items ?? []).map((s) => ({ ...s, color: SOURCE_COLORS[s.label] ?? '#94a3b8' }))

  return (
    <div className={cn('space-y-3 transition-opacity', query.isFetching && d && 'opacity-70')}>
      <Helmet><title>Dashboard | Admin</title></Helmet>

      {/* Greeting + period */}
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p className="text-[14px] text-slate-700">Welcome back,</p>
          <h1 className="text-[26px] leading-[32px] font-bold text-slate-900">{user?.name} <span aria-hidden="true">👋</span></h1>
          <p className="text-[12.5px] text-slate-500">Here's an overview of your {shortName(setting('general.site_name', ''))} website and student management activities.</p>
        </div>
        <div className="flex flex-col gap-3 pt-1.5 sm:flex-row sm:items-center">
          <DateRangePicker value={range} onChange={setRange} />
          <Button onClick={report} loading={exporting} className="h-[39px] gap-2 rounded-md bg-gradient-to-r from-primary to-[#5b3fe6] px-4 text-[12.5px] shadow-[0_8px_18px_-8px_rgb(74_43_212_/_0.8)]">
            {!exporting && <ChartColumn className="size-4" />} Generate Report
          </Button>
        </div>
      </div>

      {query.isError && !d ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !d ? (
        <div className="space-y-4" aria-busy="true">
          <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{Array.from({ length: 4 }, (_, i) => <div key={i} className="h-[96px] animate-pulse rounded-xl bg-white" />)}</div>
          <div className="grid gap-3 sm:grid-cols-3 xl:grid-cols-5">{Array.from({ length: 5 }, (_, i) => <div key={i} className="h-[94px] animate-pulse rounded-xl bg-white" />)}</div>
          <div className="h-72 animate-pulse rounded-xl bg-white" />
        </div>
      ) : (
        <>
          {/* KPIs for the period */}
          <div className={cn('grid gap-3 sm:grid-cols-2', d.cards.length >= 4 && 'wide:grid-cols-4')}>
            {d.cards.map((card) => <StatCard key={card.key} card={card} compare={d.period.compare_label} />)}
          </div>

          {/* Content totals */}
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 wide:grid-cols-5">
            {d.totals.map((t) => <TotalCard key={t.key} total={t} />)}
          </div>

          {/* Activity chart + enquiry sources */}
          <div className={cn('grid gap-3', d.sources && 'xl:grid-cols-[1.45fr_1fr] wide:grid-cols-[1.78fr_1fr]')}>
            <Panel
              title="Enquiries, Appointments & Applications"
              action={
                <div className="hidden flex-wrap gap-5 sm:flex">
                  {SERIES.filter((s) => s.key !== 'enquiries' || d.sources).map((s) => (
                    <span key={s.key} className="flex items-center gap-2 text-[12.5px] text-slate-600">
                      <span className="size-2.5 rounded-full" style={{ background: s.color }} /> {s.label}
                    </span>
                  ))}
                </div>
              }
            >
              <LineChart points={d.chart.points} unit={d.chart.unit} series={SERIES} height={152} />
            </Panel>

            {d.sources && (
              <Panel title="Enquiry Sources" action={<Link to="/admin/leads" className="grid size-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Open leads"><EllipsisVertical className="size-5" /></Link>}>
                <div className="flex flex-1 flex-col items-center gap-6 sm:flex-row sm:justify-center sm:gap-12 xl:justify-between xl:gap-6">
                  <Donut items={sources} total={d.sources.total} caption="Total Enquiries" size={152} stroke={26} />
                  <ul className="w-full max-w-[230px] space-y-[7px]">
                    {sources.map((s) => (
                      <li key={s.label} className="flex items-center gap-3 text-[12.5px] text-slate-700">
                        <span className="size-3 shrink-0 rounded-full" style={{ background: s.color }} />
                        <span className="flex-1">{s.label}</span>
                        <span className="text-slate-600">{s.percent}%</span>
                      </li>
                    ))}
                  </ul>
                </div>
              </Panel>
            )}
          </div>

          {/* Recent records */}
          <div className="grid gap-3 lg:grid-cols-2 wide:grid-cols-3 [&>*:last-child:nth-child(odd)]:lg:col-span-2 [&>*:last-child:nth-child(odd)]:wide:col-span-1">
            {d.recent_enquiries && (
              <Panel title="Recent Enquiries" to="/admin/leads">
                {!d.recent_enquiries.length ? <Empty>No enquiries yet.</Empty> : (
                  <MiniTable head={['#', 'Name', 'Enquiry Type', 'Date', 'Status']} rows={d.recent_enquiries.map((r, i) => (
                    <tr key={r.id} className="text-slate-700">
                      <td className="px-1.5 py-[5px]">{i + 1}</td>
                      <td className="px-1.5 py-[5px]"><Link to={`/admin/leads/${r.id}`} className="flex items-center gap-2 hover:text-primary"><Avatar name={r.name} className="size-5 text-[8px]" /><span className="max-w-[88px] truncate">{r.name}</span></Link></td>
                      <td className="max-w-[100px] truncate px-1.5 py-[5px]">{r.type}</td>
                      <td className="px-1.5 py-[5px] whitespace-nowrap">{shortDate(r.date)}</td>
                      <td className="px-1.5 py-[5px]"><Pill status={r.status}>{r.status_label}</Pill></td>
                    </tr>
                  ))} />
                )}
              </Panel>
            )}
            {d.upcoming_appointments && (
              <Panel title="Upcoming Appointments" to="/admin/appointments">
                {!d.upcoming_appointments.length ? <Empty>No upcoming appointments.</Empty> : (
                  <MiniTable head={['#', 'Student Name', 'Date & Time', 'Counsellor', 'Status']} rows={d.upcoming_appointments.map((r, i) => (
                    <tr key={r.id} className="text-slate-700">
                      <td className="px-1.5 py-[5px]">{i + 1}</td>
                      <td className="px-1.5 py-[5px]"><Link to={`/admin/appointments/${r.id}`} className="block max-w-[96px] truncate hover:text-primary">{r.name}</Link></td>
                      <td className="px-1.5 py-[5px] whitespace-nowrap">{dayTime(r.date, r.time)}</td>
                      <td className="px-1.5 py-[5px]">{r.counsellor && <span title={r.counsellor.name}><Avatar name={r.counsellor.name} src={r.counsellor.photo} className="size-5 text-[8px]" /></span>}</td>
                      <td className="px-1.5 py-[5px]"><Pill status={r.status}>{r.status_label}</Pill></td>
                    </tr>
                  ))} />
                )}
              </Panel>
            )}
            {d.recent_applications && (
              <Panel title="Recent Applications" to="/admin/applications">
                {!d.recent_applications.length ? <Empty>No applications yet.</Empty> : (
                  <MiniTable head={['#', 'Student Name', 'University', 'Course', 'Status']} rows={d.recent_applications.map((r, i) => (
                    <tr key={r.id} className="text-slate-700">
                      <td className="px-1.5 py-[5px]">{i + 1}</td>
                      <td className="px-1.5 py-[5px]"><Link to={`/admin/applications/${r.id}`} className="block max-w-[86px] truncate hover:text-primary">{r.name}</Link></td>
                      <td className="max-w-[96px] truncate px-1.5 py-[5px]">{r.university ?? '—'}</td>
                      <td className="max-w-[64px] truncate px-1.5 py-[5px]">{r.course ?? '—'}</td>
                      <td className="px-1.5 py-[5px]"><Pill status={r.status}>{r.status_label}</Pill></td>
                    </tr>
                  ))} />
                )}
              </Panel>
            )}
          </div>

          {/* Content + activity */}
          <div className="grid gap-3 lg:grid-cols-2 wide:grid-cols-3">
            <Panel title="Latest Blog / Notice" to="/admin/content/blogs">
              {!d.latest_blogs.length ? <Empty>No posts yet.</Empty> : (
                <ul className="divide-y divide-slate-100">
                  {d.latest_blogs.map((b) => (
                    <li key={b.id}>
                      <Link to={`/admin/content/blogs/${b.id}`} className="group flex items-center gap-3 py-[3px]">
                        {b.image ? <img src={b.image} alt="" className="h-[34px] w-12 shrink-0 rounded-md object-cover" loading="lazy" /> : <span className="grid h-[38px] w-[50px] shrink-0 place-items-center rounded-md bg-slate-100"><FileText className="size-4 text-slate-400" /></span>}
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-[11.5px] font-semibold text-slate-800 group-hover:text-primary">{b.title}</span>
                          <span className="block truncate text-[11px] text-slate-500">{b.category}</span>
                        </span>
                        <span className="shrink-0 text-[11px] text-slate-500">{shortDate(b.date)}</span>
                        <ChevronRight className="size-4 shrink-0 text-slate-400" />
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </Panel>

            <Panel title="Latest Success Stories" to="/admin/content/success-stories">
              {!d.latest_stories.length ? <Empty>No stories yet.</Empty> : (
                <ul className="divide-y divide-slate-100">
                  {d.latest_stories.map((s) => (
                    <li key={s.id}>
                      <Link to={`/admin/content/success-stories/${s.id}`} className="group flex items-center gap-3 py-[3px]">
                        {s.image ? <img src={s.image} alt="" className="h-[34px] w-12 shrink-0 rounded-md object-cover" loading="lazy" /> : <Avatar name={s.title} className="h-[38px] w-[50px] rounded-md" />}
                        <span className="min-w-0 flex-1">
                          <span className="block truncate text-[11.5px] font-semibold text-slate-800 group-hover:text-primary">{s.title}</span>
                          <span className="block text-[11px] text-slate-500">Success Stories</span>
                        </span>
                        <span className="shrink-0 text-[11px] text-slate-500">{shortDate(s.date)}</span>
                        <ChevronRight className="size-4 shrink-0 text-slate-400" />
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </Panel>

            <Panel title="System Activity" to="/admin/activity-logs" className="lg:col-span-2 wide:col-span-1">
              {!d.activity.length ? <Empty>No activity yet.</Empty> : (
                <ul>
                  {d.activity.map((a, i) => {
                    const meta = ACTIVITY[a.type] ?? ACTIVITY.enquiry
                    return (
                      <li key={i}>
                        <Link to={a.url} className="flex items-center gap-3 rounded-md px-1 py-[3px] leading-[18px] hover:bg-slate-50">
                          <span className={cn('grid size-[18px] shrink-0 place-items-center rounded', meta.cls)}><meta.icon className="size-3" /></span>
                          <span className="min-w-0 flex-1 truncate text-[11.5px] text-slate-700">{a.text}</span>
                          <span className="shrink-0 text-[11px] text-slate-500">{ago(a.at)}</span>
                        </Link>
                      </li>
                    )
                  })}
                </ul>
              )}
            </Panel>
          </div>
        </>
      )}
    </div>
  )
}
