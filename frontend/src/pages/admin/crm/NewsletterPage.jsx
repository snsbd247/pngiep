import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Download, Trash2 } from 'lucide-react'
import { useState } from 'react'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import SearchBar from '@/components/ui/SearchBar'
import { useToast } from '@/components/ui/Toast'
import { useListParams } from '@/hooks/useListParams'
import { formatDateTime } from '@/lib/utils'
import { newsletterService } from '@/services/admin'
import { saveBlob } from './shared'

export default function NewsletterPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const [params, setParams] = useListParams()
  const query = useQuery({ queryKey: ['admin', 'newsletter', params], queryFn: () => newsletterService.list(params), placeholderData: (p) => p })
  const [deleting, setDeleting] = useState(null)
  const [exporting, setExporting] = useState(false)

  const remove = useMutation({
    mutationFn: () => newsletterService.remove(deleting.id),
    onSuccess: (res) => { toast.success(res.message); setDeleting(null); queryClient.invalidateQueries({ queryKey: ['admin', 'newsletter'] }) },
    onError: (e) => toast.error(e.message),
  })

  const exportCsv = async () => {
    setExporting(true)
    try {
      saveBlob(await newsletterService.export(), `newsletter-subscribers-${new Date().toISOString().slice(0, 10)}.csv`)
    } catch {
      toast.error('Export failed.')
    } finally {
      setExporting(false)
    }
  }

  const columns = [
    { key: 'email', header: 'Email', render: (s) => <span className="font-medium text-slate-800">{s.email}</span> },
    { key: 'name', header: 'Name', render: (s) => s.name ?? '—' },
    { key: 'status', header: 'Status', render: (s) => <Badge status={s.status} /> },
    { key: 'created_at', header: 'Subscribed', className: 'whitespace-nowrap', render: (s) => formatDateTime(s.created_at) },
    { key: 'actions', header: '', className: 'text-right', render: (s) => (
      <Button variant="ghost" size="icon" className="text-rose-600 hover:bg-rose-50" onClick={() => setDeleting(s)} aria-label={`Delete ${s.email}`}><Trash2 className="size-4" /></Button>
    ) },
  ]

  return (
    <>
      <PageHeader title="Newsletter" description={`${query.data?.meta?.total ?? 0} subscribers from the website footer and sidebars.`}
        actions={<Button variant="outline" loading={exporting} onClick={exportCsv}><Download className="size-4" /> Export CSV</Button>} />
      <SearchBar className="mb-5" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search email…" />
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} emptyTitle="No subscribers yet" />
      <ConfirmDialog open={!!deleting} onClose={() => setDeleting(null)} onConfirm={() => remove.mutate()} loading={remove.isPending} title="Remove this subscriber?" message={deleting?.email} />
    </>
  )
}
