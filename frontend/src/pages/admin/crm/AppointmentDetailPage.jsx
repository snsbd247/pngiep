import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, CalendarClock, CalendarX2, Mail, Phone } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormInput from '@/components/forms/FormInput'
import { MODES, formatLongDate, formatTime } from '@/components/public/BookingWidgets'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Modal from '@/components/ui/Modal'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { cn, formatDateTime, telHref, whatsappHref } from '@/lib/utils'
import { appointmentAdminService } from '@/services/admin'
import { DetailList, NotesCard, labelOf, useMeta } from './shared'

const ACTIVE = ['pending', 'confirmed']

function RescheduleModal({ appointment, open, onClose, onDone }) {
  const toast = useToast()
  const [date, setDate] = useState('')
  const [time, setTime] = useState('')
  const slots = useQuery({
    queryKey: ['admin', 'slots', appointment.counsellor_id, date, appointment.id],
    queryFn: () => appointmentAdminService.slots({ counsellor_id: appointment.counsellor_id, date, ignore: appointment.id }),
    enabled: open && !!date,
  })
  const save = useMutation({
    mutationFn: () => appointmentAdminService.reschedule(appointment.id, { date, time }),
    onSuccess: (res) => { toast.success(res.message); onDone(res.data) },
    onError: (e) => toast.error(e.errors ? Object.values(e.errors)[0][0] : e.message),
  })

  return (
    <Modal open={open} onClose={onClose} title="Reschedule appointment" description="The student is emailed the new time."
      footer={<><Button variant="outline" onClick={onClose}>Cancel</Button><Button disabled={!date || !time} loading={save.isPending} onClick={() => save.mutate()}>Reschedule</Button></>}>
      <FormInput type="date" label="New date" value={date} onChange={(e) => { setDate(e.target.value); setTime('') }} />
      {date && (
        <div className="mt-4">
          <p className="mb-2 text-sm font-medium text-slate-700">Free times</p>
          {slots.isPending ? <p className="text-sm text-slate-400">Loading…</p> : !slots.data?.length ? (
            <p className="text-sm text-slate-500">No free times on this day (outside working hours, time off, or fully booked).</p>
          ) : (
            <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
              {slots.data.map((s) => (
                <button key={s.start} type="button" onClick={() => setTime(s.start)} aria-pressed={time === s.start}
                  className={cn('h-9 rounded-lg border text-sm', time === s.start ? 'border-primary bg-primary text-white' : 'border-slate-200 hover:border-primary')}>
                  {formatTime(s.start)}
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </Modal>
  )
}

export default function AppointmentDetailPage() {
  const { id } = useParams()
  const toast = useToast()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const meta = useMeta()
  const key = ['admin', 'appointments', 'record', id]
  const query = useQuery({ queryKey: key, queryFn: () => appointmentAdminService.get(id) })
  const [dialog, setDialog] = useState('')
  const [reason, setReason] = useState('')

  const refresh = () => {
    queryClient.invalidateQueries({ queryKey: key })
    queryClient.invalidateQueries({ queryKey: ['admin', 'appointments'], exact: false })
  }
  const action = useMutation({
    mutationFn: (fn) => fn(),
    onSuccess: (res) => { toast.success(res.message); setDialog(''); refresh() },
    onError: (e) => toast.error(e.message),
  })

  if (query.isPending) return <LoadingSkeleton variant="table" />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />
  const a = query.data
  const active = ACTIVE.includes(a.status)
  const statusButtons = [
    a.status === 'pending' && { status: 'confirmed', label: 'Confirm', variant: 'primary' },
    active && { status: 'completed', label: 'Mark completed', variant: 'secondary' },
    active && { status: 'no_show', label: 'No-show', variant: 'outline' },
  ].filter(Boolean)

  return (
    <>
      <PageHeader
        title={`Appointment ${a.reference}`}
        description={`${formatLongDate(a.appointment_date?.slice(0, 10))} · ${formatTime(a.start_time?.slice(0, 5))} – ${formatTime(a.end_time?.slice(0, 5))} (${a.timezone})`}
        actions={<Button to="/admin/appointments" variant="outline"><ArrowLeft className="size-4" /> Back</Button>}
      />
      <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
        <div className="space-y-6">
          <Card title="Booking" actions={<Badge status={a.status}>{labelOf(meta.appointment_statuses, a.status)}</Badge>}>
            <DetailList rows={[
              ['Service', a.service?.title],
              ['Counsellor', a.counsellor?.name],
              ['Meeting', MODES[a.mode]?.label ?? a.mode],
              ['Duration', `${a.duration_minutes} minutes`],
              ['Destination', a.destination?.name],
              ['Study level', a.study_level && labelOf(meta.study_levels, a.study_level)],
              ['Booked', formatDateTime(a.created_at)],
              ['Confirmed', a.confirmed_at && formatDateTime(a.confirmed_at)],
              ['Cancelled', a.cancelled_at && `${formatDateTime(a.cancelled_at)}${a.cancellation_reason ? ` – ${a.cancellation_reason}` : ''}`],
              ['Rescheduled from', a.rescheduled_from && <Link key="from" className="text-primary hover:underline" to={`/admin/appointments/${a.rescheduled_from.id}`}>{a.rescheduled_from.reference}</Link>],
              ['Moved to', a.rescheduled_to && <Link key="to" className="text-primary hover:underline" to={`/admin/appointments/${a.rescheduled_to.id}`}>{a.rescheduled_to.reference}</Link>],
            ]} />
            {a.message && <div className="mt-5 rounded-xl bg-slate-50 p-4 text-sm whitespace-pre-line text-slate-700"><p className="mb-1 text-xs font-semibold text-slate-400 uppercase">Message from student</p>{a.message}</div>}
            {active && (
              <div className="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                {statusButtons.map((b) => (
                  <Button key={b.status} variant={b.variant} size="sm" disabled={action.isPending}
                    onClick={() => action.mutate(() => appointmentAdminService.setStatus(a.id, b.status))}>{b.label}</Button>
                ))}
                <Button variant="outline" size="sm" onClick={() => setDialog('reschedule')}><CalendarClock className="size-4" /> Reschedule</Button>
                <Button variant="danger" size="sm" onClick={() => setDialog('cancel')}><CalendarX2 className="size-4" /> Cancel</Button>
              </div>
            )}
            {['completed', 'no_show'].includes(a.status) && (
              <div className="mt-6 border-t border-slate-100 pt-5">
                <Button variant="outline" size="sm" onClick={() => action.mutate(() => appointmentAdminService.setStatus(a.id, 'confirmed'))}>Re-open as confirmed</Button>
              </div>
            )}
          </Card>
          <NotesCard notes={a.notes} onAdd={(body) => appointmentAdminService.addNote(a.id, body)} onAdded={refresh} />
        </div>

        <Card title="Student">
          <p className="font-semibold text-slate-800">{a.name}</p>
          <ul className="mt-3 space-y-2 text-sm">
            <li><a href={`mailto:${a.email}`} className="inline-flex items-center gap-2 text-primary hover:underline"><Mail className="size-4" /> {a.email}</a></li>
            <li><a href={telHref(a.phone)} className="inline-flex items-center gap-2 text-primary hover:underline"><Phone className="size-4" /> {a.phone}</a></li>
            <li><a href={whatsappHref(a.phone)} target="_blank" rel="noopener noreferrer" className="text-xs text-emerald-600 hover:underline">Open WhatsApp chat</a></li>
          </ul>
          {a.lead && (
            <Button to={`/admin/leads/${a.lead.id}`} variant="secondary" size="sm" className="mt-5 w-full">View lead ({labelOf(meta.lead_statuses, a.lead.status)})</Button>
          )}
        </Card>
      </div>

      {dialog === 'reschedule' && (
        <RescheduleModal appointment={a} open onClose={() => setDialog('')} onDone={(n) => { setDialog(''); refresh(); navigate(`/admin/appointments/${n.id}`) }} />
      )}
      <Modal open={dialog === 'cancel'} onClose={() => setDialog('')} title="Cancel appointment?" description="The student is emailed and the time slot is released."
        footer={<><Button variant="outline" onClick={() => setDialog('')}>Keep</Button><Button variant="danger" loading={action.isPending} onClick={() => action.mutate(() => appointmentAdminService.cancel(a.id, reason || null))}>Cancel appointment</Button></>}>
        <FormInput label="Reason (included in the email)" value={reason} maxLength={255} onChange={(e) => setReason(e.target.value)} />
      </Modal>
    </>
  )
}
