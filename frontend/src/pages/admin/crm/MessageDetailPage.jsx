import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Archive, ArrowLeft, Inbox, Mail, Phone, Send, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormTextarea from '@/components/forms/FormTextarea'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { formatDateTime, telHref, whatsappHref } from '@/lib/utils'
import { messageService } from '@/services/admin'
import { DetailList, labelOf, useMeta } from './shared'

export default function MessageDetailPage() {
  const { id } = useParams()
  const toast = useToast()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const meta = useMeta()
  const key = ['admin', 'messages', 'record', id]
  // Opening a message marks it read on the server, so refresh the inbox counts afterwards.
  const query = useQuery({ queryKey: key, queryFn: async () => {
    const m = await messageService.get(id)
    queryClient.invalidateQueries({ queryKey: ['admin', 'messages'], predicate: (q) => q.queryKey[2] !== 'record' })
    return m
  } })
  const [reply, setReply] = useState('')
  const [deleting, setDeleting] = useState(false)

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'messages'] })
  const onError = (e) => toast.error(e.errors ? Object.values(e.errors)[0][0] : e.message)
  const send = useMutation({ mutationFn: () => messageService.reply(id, reply), onSuccess: (res) => { toast.success(res.message); setReply(''); refresh() }, onError })
  const setStatus = useMutation({ mutationFn: (status) => messageService.setStatus(id, status), onSuccess: (res) => { toast.success(res.message); refresh() }, onError })
  const remove = useMutation({ mutationFn: () => messageService.remove(id), onSuccess: (res) => { toast.success(res.message); refresh(); navigate('/admin/messages') }, onError })

  if (query.isPending) return <LoadingSkeleton variant="table" />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />
  const m = query.data

  return (
    <>
      <PageHeader title={m.subject || m.inquiry_type} description={`From ${m.name} · ${formatDateTime(m.created_at)}`}
        actions={<>
          <Button to="/admin/messages" variant="outline"><ArrowLeft className="size-4" /> Inbox</Button>
          {m.status === 'archived'
            ? <Button variant="outline" onClick={() => setStatus.mutate('read')}><Inbox className="size-4" /> Unarchive</Button>
            : <Button variant="outline" onClick={() => setStatus.mutate('archived')}><Archive className="size-4" /> Archive</Button>}
          <Button variant="outline" className="text-rose-600" onClick={() => setDeleting(true)}><Trash2 className="size-4" /> Delete</Button>
        </>} />
      <div className="grid gap-6 xl:grid-cols-[1fr_340px]">
        <div className="space-y-6">
          <Card title={m.inquiry_type} actions={<Badge status={m.status}>{labelOf(meta.contact_statuses, m.status)}</Badge>}>
            <p className="text-sm leading-relaxed whitespace-pre-line text-slate-700">{m.message}</p>
          </Card>
          {m.reply && (
            <Card title="Your reply" description={m.replied_at && `Sent ${formatDateTime(m.replied_at)}`}>
              <p className="text-sm whitespace-pre-line text-slate-700">{m.reply}</p>
            </Card>
          )}
          <Card title={m.reply ? 'Send another reply' : 'Reply by email'} description={`Sent to ${m.email} using the "Reply to a contact message" email template.`}>
            <form onSubmit={(e) => { e.preventDefault(); send.mutate() }}>
              <FormTextarea rows={6} value={reply} onChange={(e) => setReply(e.target.value)} placeholder={`Hi ${m.name.split(' ')[0]},`} aria-label="Reply" maxLength={10000} />
              <Button type="submit" className="mt-3" loading={send.isPending} disabled={reply.trim().length < 2}><Send className="size-4" /> Send reply</Button>
            </form>
          </Card>
        </div>
        <Card title="Sender">
          <DetailList rows={[
            ['Name', m.name],
            ['Email', <a key="e" href={`mailto:${m.email}`} className="inline-flex items-center gap-1 text-primary hover:underline"><Mail className="size-3.5" /> {m.email}</a>],
            ['Phone', m.phone && <span key="p" className="inline-flex flex-wrap items-center gap-3"><a href={telHref(m.phone)} className="inline-flex items-center gap-1 text-primary hover:underline"><Phone className="size-3.5" /> {m.phone}</a><a href={whatsappHref(m.phone)} target="_blank" rel="noopener noreferrer" className="text-xs text-emerald-600 hover:underline">WhatsApp</a></span>],
            ['Destination', m.destination?.name],
          ]} />
          {m.lead && <Button to={`/admin/leads/${m.lead.id}`} variant="secondary" size="sm" className="mt-5 w-full">View lead</Button>}
        </Card>
      </div>
      <ConfirmDialog open={deleting} onClose={() => setDeleting(false)} onConfirm={() => remove.mutate()} loading={remove.isPending} title="Delete this message?" />
    </>
  )
}
