/* eslint-disable react-refresh/only-export-components */
import { useQuery } from '@tanstack/react-query'
import { ChevronLeft, ChevronRight, Clock, MapPin, Moon, Phone, Sun, Video } from 'lucide-react'
import { useMemo } from 'react'
import { cn } from '@/lib/utils'
import { bookingService } from '@/services/publicService'

/* Dates are "wall clock" strings (YYYY-MM-DD, HH:mm) in the business timezone, as the API returns them. */

const pad = (n) => String(n).padStart(2, '0')
export const ymd = (y, m, d) => `${y}-${pad(m)}-${pad(d)}`
export const addMonths = (month, n) => {
  const [y, m] = month.split('-').map(Number)
  const d = new Date(Date.UTC(y, m - 1 + n, 1))
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}`
}

/** "14:30" → "2:30 PM" */
export function formatTime(hm = '') {
  const [h, m] = hm.split(':').map(Number)
  if (Number.isNaN(h)) return hm
  return `${((h + 11) % 12) + 1}:${pad(m)} ${h < 12 ? 'AM' : 'PM'}`
}

/** "2026-10-11" → "Sunday, 11 October 2026" (no timezone shift). */
export function formatLongDate(date, opts = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) {
  if (!date) return ''
  const [y, m, d] = date.split('-').map(Number)
  return new Date(Date.UTC(y, m - 1, d)).toLocaleDateString('en-GB', { ...opts, timeZone: 'UTC' })
}

/** UTC Date for a wall-clock date/time in an IANA timezone. */
function zonedToUtc(date, time, tz) {
  const guess = new Date(`${date}T${time}:00Z`)
  try {
    const parts = Object.fromEntries(
      new Intl.DateTimeFormat('en-US', { timeZone: tz, hourCycle: 'h23', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
        .formatToParts(guess).map((p) => [p.type, p.value]),
    )
    const asZone = Date.UTC(+parts.year, +parts.month - 1, +parts.day, +parts.hour, +parts.minute)
    return new Date(guess.getTime() - (asZone - guess.getTime()))
  } catch {
    return guess
  }
}

/** Downloads an .ics calendar file for an appointment summary (from the API's present()). */
export function downloadIcs(a, siteName = '') {
  const stamp = (d) => d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '')
  const esc = (s = '') => String(s).replace(/[\\;,]/g, (c) => `\\${c}`).replace(/\n/g, '\\n')
  const start = zonedToUtc(a.date, a.start_time, a.timezone)
  const end = zonedToUtc(a.date, a.end_time, a.timezone)
  const ics = [
    'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Appointment//EN', 'BEGIN:VEVENT',
    `UID:${a.reference}@${window.location.hostname}`, `DTSTAMP:${stamp(new Date())}`, `DTSTART:${stamp(start)}`, `DTEND:${stamp(end)}`,
    `SUMMARY:${esc(`Consultation${a.counsellor ? ` with ${a.counsellor.name}` : ''}${siteName ? ` – ${siteName}` : ''}`)}`,
    `DESCRIPTION:${esc(`Reference: ${a.reference}${a.service ? `\nService: ${a.service.title}` : ''}`)}`,
    'END:VEVENT', 'END:VCALENDAR',
  ].join('\r\n')
  const url = URL.createObjectURL(new Blob([ics], { type: 'text/calendar;charset=utf-8' }))
  const link = Object.assign(document.createElement('a'), { href: url, download: `${a.reference}.ics` })
  link.click()
  URL.revokeObjectURL(url)
}

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

/** Month grid; only days with free slots (from the availability endpoint) are selectable. */
export function MonthCalendar({ counsellorId, month, onMonthChange, minMonth, maxMonth, value, onChange }) {
  const query = useQuery({
    queryKey: ['availability', counsellorId, month],
    queryFn: () => bookingService.availability(counsellorId, month),
    enabled: !!counsellorId,
    staleTime: 30_000,
  })
  const available = query.data ?? {}

  const cells = useMemo(() => {
    const [y, m] = month.split('-').map(Number)
    const first = new Date(Date.UTC(y, m - 1, 1)).getUTCDay()
    const days = new Date(Date.UTC(y, m, 0)).getUTCDate()
    return [...Array(first).fill(null), ...Array.from({ length: days }, (_, i) => ymd(y, m, i + 1))]
  }, [month])

  const title = formatLongDate(`${month}-01`, { month: 'long', year: 'numeric' })
  const nav = 'grid size-9 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-primary hover:text-primary disabled:pointer-events-none disabled:opacity-40'

  return (
    <div className="rounded-xl border border-slate-200 p-4">
      <div className="mb-3 flex items-center justify-between">
        <button type="button" className={nav} onClick={() => onMonthChange(addMonths(month, -1))} disabled={month <= minMonth} aria-label="Previous month"><ChevronLeft className="size-4" /></button>
        <p className="font-semibold text-ink" aria-live="polite">{title}</p>
        <button type="button" className={nav} onClick={() => onMonthChange(addMonths(month, 1))} disabled={month >= maxMonth} aria-label="Next month"><ChevronRight className="size-4" /></button>
      </div>
      <div className="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-400">
        {WEEKDAYS.map((d) => <div key={d} className="py-1">{d}</div>)}
      </div>
      <div className={cn('grid grid-cols-7 gap-1', query.isFetching && 'opacity-60')} role="grid" aria-busy={query.isFetching}>
        {cells.map((date, i) => {
          if (!date) return <div key={`e${i}`} />
          const free = available[date]
          const selected = value === date
          return (
            <button
              key={date}
              type="button"
              disabled={!free}
              onClick={() => onChange(date)}
              aria-pressed={selected}
              aria-label={`${formatLongDate(date)}${free ? `, ${free} slots` : ', unavailable'}`}
              className={cn(
                'relative aspect-square rounded-lg text-sm font-medium transition',
                selected ? 'bg-primary text-white shadow-glow' : free ? 'bg-primary-50 text-primary-700 hover:bg-primary-100' : 'text-slate-300',
              )}
            >
              {Number(date.slice(8))}
              {free && !selected && <span className="absolute bottom-1 left-1/2 size-1 -translate-x-1/2 rounded-full bg-primary" />}
            </button>
          )
        })}
      </div>
      {query.isSuccess && !Object.keys(available).length && (
        <p className="mt-3 text-center text-xs text-slate-500">No free times this month. Try the next month.</p>
      )}
    </div>
  )
}

/** Free time slots for the chosen day, split into morning / afternoon. */
export function SlotPicker({ counsellorId, date, value, onChange, timezoneLabel }) {
  const query = useQuery({
    queryKey: ['slots', counsellorId, date],
    queryFn: () => bookingService.slots(counsellorId, date),
    enabled: !!counsellorId && !!date,
    staleTime: 15_000,
  })

  if (!date) return <p className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">Select a date to see available times.</p>
  if (query.isPending) return <div className="grid grid-cols-3 gap-2">{Array.from({ length: 6 }, (_, i) => <div key={i} className="h-10 animate-pulse rounded-lg bg-slate-100" />)}</div>
  if (query.isError) return <p className="text-sm text-rose-600">{query.error.message}</p>

  const slots = query.data ?? []
  const groups = [
    { label: 'Morning', icon: Sun, items: slots.filter((s) => s.start < '12:00') },
    { label: 'Afternoon', icon: Moon, items: slots.filter((s) => s.start >= '12:00') },
  ].filter((g) => g.items.length)

  return (
    <div>
      <p className="mb-3 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
        <Clock className="size-3.5 text-primary" /> {formatLongDate(date)}
        {timezoneLabel && <span className="rounded-full bg-primary-50 px-2 py-0.5 font-medium text-primary-700">{timezoneLabel}</span>}
      </p>
      {!groups.length && <p className="text-sm text-slate-500">No free times left on this day. Please choose another date.</p>}
      {groups.map(({ label, icon: Icon, items }) => (
        <div key={label} className="mb-4">
          <p className="mb-2 flex items-center gap-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase"><Icon className="size-3.5" /> {label}</p>
          <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
            {items.map((s) => (
              <button
                key={s.start}
                type="button"
                onClick={() => onChange(s.start)}
                aria-pressed={value === s.start}
                className={cn('h-10 rounded-lg border text-sm font-medium transition',
                  value === s.start ? 'border-primary bg-primary text-white shadow-glow' : 'border-slate-200 text-slate-700 hover:border-primary hover:text-primary')}
              >
                {formatTime(s.start)}
              </button>
            ))}
          </div>
        </div>
      ))}
    </div>
  )
}

export const MODES = {
  in_person: { label: 'In-Person', hint: 'Visit our office', icon: MapPin },
  online: { label: 'Online', hint: 'Video call link by email', icon: Video },
  phone: { label: 'Phone Call', hint: 'We will call you', icon: Phone },
}

/** Summary rows shared by the details and confirmation steps. */
export function AppointmentSummary({ rows }) {
  return (
    <dl className="divide-y divide-slate-100 rounded-xl border border-slate-100 bg-slate-50/60 text-sm">
      {rows.filter(([, v]) => v).map(([label, value]) => (
        <div key={label} className="flex flex-wrap justify-between gap-x-4 gap-y-0.5 px-4 py-2.5">
          <dt className="text-slate-500">{label}</dt>
          <dd className="font-medium text-ink">{value}</dd>
        </div>
      ))}
    </dl>
  )
}

