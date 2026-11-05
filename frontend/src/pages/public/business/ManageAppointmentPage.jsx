import { useQuery, useQueryClient } from '@tanstack/react-query'
import { CalendarClock, CalendarPlus, CalendarX2 } from 'lucide-react'
import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { AppointmentSummary, MODES, MonthCalendar, SlotPicker, downloadIcs, formatLongDate, formatTime } from '@/components/public/BookingWidgets'
import { Field, TextInput } from '@/components/public/FormControls'
import Seo from '@/components/Seo'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useSite } from '@/context/SiteContext'
import { bookingService } from '@/services/publicService'


/** Student self-service page reached from the email link: /appointment/{token}. */
export default function ManageAppointmentPage() {
  const { token } = useParams()
  const { setting } = useSite()
  const queryClient = useQueryClient()
  const query = useQuery({ queryKey: ['manage-appointment', token], queryFn: () => bookingService.manage(token), retry: false })
  const [mode, setMode] = useState('') // '' | 'reschedule' | 'cancel'
  const [month, setMonth] = useState('')
  const [date, setDate] = useState('')
  const [time, setTime] = useState('')
  const [reason, setReason] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const a = query.data
  const tzLabel = setting('appointment.appointment_timezone_label', a?.timezone)
  // Booking window in the business timezone, from the server.
  const rules = useQuery({ queryKey: ['booking-options'], queryFn: bookingService.options, staleTime: 60_000, enabled: mode === 'reschedule' }).data?.rules
  const today = rules?.today.slice(0, 7) ?? ''
  const lastMonth = rules?.last_date.slice(0, 7) ?? ''

  const run = async (fn, success) => {
    setBusy(true)
    setError('')
    try {
      const res = await fn()
      queryClient.setQueryData(['manage-appointment', token], res.data)
      setNotice(res.message || success)
      setMode('')
      setDate('')
      setTime('')
    } catch (e) {
      setError(e.errors ? Object.values(e.errors)[0][0] : e.message)
      queryClient.invalidateQueries({ queryKey: ['slots'] })
    } finally {
      setBusy(false)
    }
  }

  return (
    <>
      <Seo title="Manage Appointment" noindex />
      <section className="bg-lavender py-12 sm:py-16">
        <div className="container-page max-w-3xl">
          <h1 className="text-3xl">Manage Your Appointment</h1>
          <p className="mt-2 text-sm text-slate-500">View, reschedule or cancel your consultation.</p>

          <div className="mt-8 rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
            {query.isPending ? (
              <LoadingSkeleton count={3} />
            ) : query.isError ? (
              <ErrorState error={query.error.isNotFound ? { message: 'This appointment link is invalid or has expired.' } : query.error} onRetry={query.error.isNotFound ? undefined : query.refetch} />
            ) : (
              <>
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                  <h2 className="text-xl">Appointment {a.reference}</h2>
                  <Badge status={a.status}>{a.status_label}</Badge>
                </div>
                {notice && <p className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">{notice}</p>}
                <AppointmentSummary rows={[
                  ['Name', a.name],
                  ['Email', a.email],
                  ['Service', a.service?.title],
                  ['Counsellor', a.counsellor?.name],
                  ['Date', formatLongDate(a.date)],
                  ['Time', `${formatTime(a.start_time)} – ${formatTime(a.end_time)} (${tzLabel})`],
                  ['Meeting', MODES[a.mode]?.label ?? a.mode],
                ]} />
                {a.cancellation_policy && <p className="mt-3 text-xs text-slate-500">{a.cancellation_policy}</p>}

                {['pending', 'confirmed'].includes(a.status) && (
                  <div className="mt-6 flex flex-wrap gap-3">
                    <Button variant="outline" onClick={() => downloadIcs(a, setting('general.site_name', ''))}><CalendarPlus className="size-4" /> Add to Calendar</Button>
                    {a.can_change && (
                      <>
                        <Button variant="secondary" onClick={() => { setMode('reschedule'); setMonth(a.date.slice(0, 7)); setError('') }}><CalendarClock className="size-4" /> Reschedule</Button>
                        <Button variant="danger" onClick={() => { setMode('cancel'); setError('') }}><CalendarX2 className="size-4" /> Cancel</Button>
                      </>
                    )}
                  </div>
                )}
                {['pending', 'confirmed'].includes(a.status) && !a.can_change && (
                  <p className="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This appointment can no longer be changed online. Please call us at {setting('contact.contact_phone')}.
                  </p>
                )}

                {error && <p className="mt-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{error}</p>}

                {mode === 'reschedule' && (
                  <div className="mt-6 border-t border-slate-100 pt-6">
                    <h3 className="mb-4 text-lg">Choose a new time</h3>
                    <div className="grid gap-6 md:grid-cols-2">
                      <MonthCalendar counsellorId={a.counsellor?.id} month={month} onMonthChange={setMonth} minMonth={today} maxMonth={lastMonth} value={date}
                        onChange={(d) => { setDate(d); setTime('') }} />
                      <SlotPicker counsellorId={a.counsellor?.id} date={date} value={time} onChange={setTime} timezoneLabel={tzLabel} />
                    </div>
                    <div className="mt-5 flex justify-end gap-3">
                      <Button variant="ghost" onClick={() => setMode('')}>Keep current time</Button>
                      <Button disabled={!date || !time} loading={busy} onClick={() => run(() => bookingService.reschedule(token, { date, time }), 'Rescheduled.')}>Confirm New Time</Button>
                    </div>
                  </div>
                )}

                {mode === 'cancel' && (
                  <div className="mt-6 border-t border-slate-100 pt-6">
                    <h3 className="text-lg">Cancel this appointment?</h3>
                    <Field label="Reason (optional)" className="mt-4">
                      <TextInput value={reason} maxLength={255} onChange={(e) => setReason(e.target.value)} placeholder="Let us know why (optional)" />
                    </Field>
                    <div className="mt-5 flex justify-end gap-3">
                      <Button variant="ghost" onClick={() => setMode('')}>Keep appointment</Button>
                      <Button variant="danger" loading={busy} onClick={() => run(() => bookingService.cancel(token, reason || null), 'Cancelled.')}>Yes, cancel it</Button>
                    </div>
                  </div>
                )}

                {['cancelled', 'completed', 'no_show'].includes(a.status) && (
                  <div className="mt-6"><Button to="/book-appointment">Book a New Appointment</Button></div>
                )}
              </>
            )}
          </div>
        </div>
      </section>
    </>
  )
}
