import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Mail, Phone, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormSelect from '@/components/forms/FormSelect'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { formatDate, formatDateTime, telHref, whatsappHref } from '@/lib/utils'
import { leadService, lookupService } from '@/services/admin'
import { DetailList, NotesCard, labelOf, useMeta } from './shared'

function Related({ title, items, render }) {
  if (!items?.length) return null
  return (
    <Card title={title} bodyClassName="p-0">
      <ul className="divide-y divide-slate-100">{items.map((item) => <li key={item.id} className="px-5 py-3 text-sm">{render(item)}</li>)}</ul>
    </Card>
  )
}

export default function LeadDetailPage() {
  const { id } = useParams()
  const toast = useToast()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { can } = useAuth()
  const meta = useMeta()
  const key = ['admin', 'leads', 'record', id]
  const query = useQuery({ queryKey: key, queryFn: () => leadService.get(id) })
  const staff = useQuery({ queryKey: ['admin', 'lookups', 'staff'], queryFn: () => lookupService.get(['staff']) }).data?.staff ?? []
  const [deleting, setDeleting] = useState(false)

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'leads'] })
  const update = useMutation({
    mutationFn: (payload) => leadService.update(id, payload),
    onSuccess: (res) => { toast.success(res.message); refresh() },
    onError: (e) => toast.error(e.message),
  })
  const remove = useMutation({
    mutationFn: () => leadService.remove(id),
    onSuccess: (res) => { toast.success(res.message); refresh(); navigate('/admin/leads') },
    onError: (e) => toast.error(e.message),
  })

  if (query.isPending) return <LoadingSkeleton variant="table" />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />
  const l = query.data
  const history = l.meta?.history ?? []

  return (
    <>
      <PageHeader title={l.name || l.email} description={`Lead from ${labelOf(meta.lead_sources, l.source)} · ${formatDateTime(l.created_at)}`}
        actions={<>
          <Button to="/admin/leads" variant="outline"><ArrowLeft className="size-4" /> Back</Button>
          <Button variant="outline" className="text-rose-600" onClick={() => setDeleting(true)}><Trash2 className="size-4" /> Delete</Button>
        </>} />
      <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div className="space-y-6">
          <Card title="Details" actions={<Badge status={l.status}>{labelOf(meta.lead_statuses, l.status)}</Badge>}>
            <DetailList rows={[
              ['Email', <a key="e" href={`mailto:${l.email}`} className="inline-flex items-center gap-1 text-primary hover:underline"><Mail className="size-3.5" /> {l.email}</a>],
              ['Phone', l.phone && <span key="p" className="inline-flex flex-wrap items-center gap-3"><a href={telHref(l.phone)} className="inline-flex items-center gap-1 text-primary hover:underline"><Phone className="size-3.5" /> {l.phone}</a><a href={whatsappHref(l.phone)} target="_blank" rel="noopener noreferrer" className="text-xs text-emerald-600 hover:underline">WhatsApp</a></span>],
              ['Destination', l.destination?.name],
              ['University', l.university?.name],
              ['Course', l.course?.name],
              ['Study level', l.study_level && labelOf(meta.study_levels, l.study_level)],
              ['Intake', l.intake],
              ['Last contacted', l.last_contacted_at && formatDateTime(l.last_contacted_at)],
              ['Page', l.page_url && <a key="u" href={l.page_url} target="_blank" rel="noopener noreferrer" className="break-all text-primary hover:underline">{l.page_url}</a>],
            ]} />
            {l.message && <div className="mt-5 rounded-xl bg-slate-50 p-4 text-sm whitespace-pre-line text-slate-700">{l.message}</div>}
            {history.length > 0 && (
              <div className="mt-5">
                <p className="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">Earlier enquiries from this person</p>
                <ul className="space-y-2">
                  {history.map((h, i) => (
                    <li key={i} className="rounded-xl border border-slate-100 p-3 text-sm text-slate-600">
                      <span className="text-xs text-slate-400">{labelOf(meta.lead_sources, h.source)} · {h.at && formatDateTime(h.at)}</span>
                      {h.message && <p className="mt-1 whitespace-pre-line">{h.message}</p>}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </Card>
          <Related title="Appointments" items={l.appointments} render={(a) => (
            <Link to={`/admin/appointments/${a.id}`} className="flex flex-wrap items-center justify-between gap-2 hover:text-primary">
              <span>{a.reference} · {formatDate(a.appointment_date)} {a.start_time?.slice(0, 5)} · {a.counsellor?.name}</span>
              <Badge status={a.status}>{labelOf(meta.appointment_statuses, a.status)}</Badge>
            </Link>
          )} />
          <Related title="Applications" items={l.applications} render={(a) => (
            <Link to={`/admin/applications/${a.id}`} className="flex flex-wrap items-center justify-between gap-2 hover:text-primary">
              <span>{a.reference} · {formatDate(a.created_at)}</span>
              <Badge status={a.status}>{labelOf(meta.application_statuses, a.status)}</Badge>
            </Link>
          )} />
          <Related title="Messages" items={l.contact_messages} render={(m) => {
            const body = <span className="flex flex-wrap items-center justify-between gap-2"><span>{m.subject || m.inquiry_type} · {formatDate(m.created_at)}</span><Badge status={m.status}>{labelOf(meta.contact_statuses, m.status)}</Badge></span>
            return can('contacts.manage') ? <Link to={`/admin/messages/${m.id}`} className="block hover:text-primary">{body}</Link> : body
          }} />
          <NotesCard notes={l.notes} onAdd={(body) => leadService.addNote(l.id, body)} onAdded={refresh} />
        </div>

        <Card title="Follow-up">
          <div className="space-y-4">
            <FormSelect label="Status" value={l.status} onChange={(e) => update.mutate({ status: e.target.value })} options={meta.lead_statuses ?? []} disabled={update.isPending} />
            <FormSelect label="Assigned to" placeholder="Unassigned" value={l.assigned_to ?? ''} onChange={(e) => update.mutate({ assigned_to: e.target.value || null })} options={staff} disabled={update.isPending} />
          </div>
        </Card>
      </div>
      <ConfirmDialog open={deleting} onClose={() => setDeleting(false)} onConfirm={() => remove.mutate()} loading={remove.isPending}
        title="Delete this lead?" message="Linked appointments, applications and messages are kept." />
    </>
  )
}
