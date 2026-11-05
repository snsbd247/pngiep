import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, Download, FileText, Mail, Phone, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FormSelect from '@/components/forms/FormSelect'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { formatBytes, formatDateTime, headline, telHref } from '@/lib/utils'
import { applicationService, lookupService } from '@/services/admin'
import { DetailList, NotesCard, labelOf, saveBlob, useMeta } from './shared'

export default function ApplicationDetailPage() {
  const { id } = useParams()
  const toast = useToast()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const meta = useMeta()
  const key = ['admin', 'applications', 'record', id]
  const query = useQuery({ queryKey: key, queryFn: () => applicationService.get(id) })
  const staff = useQuery({ queryKey: ['admin', 'lookups', 'staff'], queryFn: () => lookupService.get(['staff']) }).data?.staff ?? []
  const [deleting, setDeleting] = useState(false)
  const [downloading, setDownloading] = useState(null)

  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'applications'] })
  const update = useMutation({
    mutationFn: (payload) => applicationService.update(id, payload),
    onSuccess: (res) => { toast.success(res.message); refresh() },
    onError: (e) => toast.error(e.message),
  })
  const remove = useMutation({
    mutationFn: () => applicationService.remove(id),
    onSuccess: (res) => { toast.success(res.message); refresh(); navigate('/admin/applications') },
    onError: (e) => toast.error(e.message),
  })

  // Files are private: fetched with the admin session and saved locally, never linked publicly.
  const download = async (doc) => {
    setDownloading(doc.id)
    try {
      saveBlob(await applicationService.download(id, doc.id), doc.original_name)
    } catch (e) {
      toast.error(e.status === 404 ? 'The file is missing from storage.' : e.status === 403 ? 'You do not have permission to download this file.' : 'Download failed.')
    } finally {
      setDownloading(null)
    }
  }

  if (query.isPending) return <LoadingSkeleton variant="table" />
  if (query.isError) return <ErrorState error={query.error} onRetry={query.refetch} />
  const a = query.data

  return (
    <>
      <PageHeader title={`Application ${a.reference}`} description={`Received ${formatDateTime(a.created_at)}`}
        actions={<>
          <Button to="/admin/applications" variant="outline"><ArrowLeft className="size-4" /> Back</Button>
          <Button variant="outline" className="text-rose-600" onClick={() => setDeleting(true)}><Trash2 className="size-4" /> Delete</Button>
        </>} />
      <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div className="space-y-6">
          <Card title="Applicant" actions={<Badge status={a.status}>{labelOf(meta.application_statuses, a.status)}</Badge>}>
            <DetailList rows={[
              ['Name', a.name],
              ['Email', <a key="e" href={`mailto:${a.email}`} className="inline-flex items-center gap-1 text-primary hover:underline"><Mail className="size-3.5" /> {a.email}</a>],
              ['Phone', <a key="p" href={telHref(a.phone)} className="inline-flex items-center gap-1 text-primary hover:underline"><Phone className="size-3.5" /> {a.phone}</a>],
              ['Study level', a.study_level && labelOf(meta.study_levels, a.study_level)],
              ['Destination', a.destination?.name],
              ['University', a.university?.name],
              ['Course', a.course?.name],
              ['Intake', a.intake],
            ]} />
            {a.message && <div className="mt-5 rounded-xl bg-slate-50 p-4 text-sm whitespace-pre-line text-slate-700">{a.message}</div>}
          </Card>

          <Card title="Documents" description="Stored privately. Downloads are logged in the activity log." bodyClassName="p-0">
            {!a.documents?.length ? <p className="p-5 text-sm text-slate-500">No documents uploaded.</p> : (
              <ul className="divide-y divide-slate-100">
                {a.documents.map((d) => (
                  <li key={d.id} className="flex flex-wrap items-center gap-3 px-5 py-3">
                    <FileText className="size-5 shrink-0 text-primary" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium text-slate-800">{d.original_name}</p>
                      <p className="text-xs text-slate-500">{headline(d.type)} · {formatBytes(d.size)}</p>
                    </div>
                    <Button variant="outline" size="sm" loading={downloading === d.id} onClick={() => download(d)}><Download className="size-4" /> Download</Button>
                  </li>
                ))}
              </ul>
            )}
          </Card>
          <NotesCard notes={a.notes} onAdd={(body) => applicationService.addNote(a.id, body)} onAdded={refresh} />
        </div>

        <Card title="Processing">
          <div className="space-y-4">
            <FormSelect label="Status" value={a.status} onChange={(e) => update.mutate({ status: e.target.value })} options={meta.application_statuses ?? []} disabled={update.isPending} />
            <FormSelect label="Assigned to" placeholder="Unassigned" value={a.assigned_to ?? ''} onChange={(e) => update.mutate({ assigned_to: e.target.value || null })} options={staff} disabled={update.isPending} />
            {a.lead && <Button to={`/admin/leads/${a.lead.id}`} variant="secondary" size="sm" className="w-full">View lead</Button>}
          </div>
        </Card>
      </div>
      <ConfirmDialog open={deleting} onClose={() => setDeleting(false)} onConfirm={() => remove.mutate()} loading={remove.isPending}
        title="Delete this application?" message="The application record is removed from the list." />
    </>
  )
}
