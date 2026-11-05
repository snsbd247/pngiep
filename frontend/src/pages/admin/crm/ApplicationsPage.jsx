import { useQuery } from '@tanstack/react-query'
import { Eye, Paperclip } from 'lucide-react'
import { Link } from 'react-router-dom'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import FormSelect from '@/components/forms/FormSelect'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import SearchBar from '@/components/ui/SearchBar'
import { useListParams } from '@/hooks/useListParams'
import { formatDateTime } from '@/lib/utils'
import { applicationService, lookupService } from '@/services/admin'
import { StatusTabs, labelOf, useMeta } from './shared'

export default function ApplicationsPage() {
  const meta = useMeta()
  const [params, setParams] = useListParams()
  const query = useQuery({ queryKey: ['admin', 'applications', params], queryFn: () => applicationService.list(params), placeholderData: (p) => p })
  const lookups = useQuery({ queryKey: ['admin', 'lookups', 'destinations,staff'], queryFn: () => lookupService.get(['destinations', 'staff']) }).data ?? {}

  const columns = [
    { key: 'reference', header: 'Reference', className: 'whitespace-nowrap', render: (a) => (
      <Link to={`/admin/applications/${a.id}`} className="font-mono text-xs font-semibold text-primary hover:underline">{a.reference}</Link>
    ) },
    { key: 'name', header: 'Applicant', render: (a) => <>{a.name}<span className="block text-xs text-slate-500">{a.email}</span></> },
    { key: 'program', header: 'Program', render: (a) => (
      <span className="text-sm">{a.course?.name ?? a.university?.name ?? '—'}<span className="block text-xs text-slate-500">{[a.university && a.course ? a.university.name : null, a.destination?.name].filter(Boolean).join(' · ')}</span></span>
    ) },
    { key: 'documents_count', header: 'Docs', render: (a) => <span className="inline-flex items-center gap-1 text-slate-600"><Paperclip className="size-3.5" /> {a.documents_count}</span> },
    { key: 'assignee', header: 'Assigned', render: (a) => a.assignee?.name ?? <span className="text-slate-400">—</span> },
    { key: 'status', header: 'Status', render: (a) => <Badge status={a.status}>{labelOf(meta.application_statuses, a.status)}</Badge> },
    { key: 'created_at', header: 'Received', className: 'whitespace-nowrap', render: (a) => formatDateTime(a.created_at) },
    { key: 'actions', header: '', className: 'text-right', render: (a) => <Button to={`/admin/applications/${a.id}`} variant="ghost" size="icon" aria-label="Open"><Eye className="size-4" /></Button> },
  ]

  return (
    <>
      <PageHeader title="Applications" description="Apply Now submissions with their documents." />
      <StatusTabs options={meta.application_statuses} counts={query.data?.meta?.counts} value={params.status ?? ''} onChange={(status) => setParams({ status })} />
      <div className="mb-5 flex flex-col gap-3 md:flex-row">
        <SearchBar className="flex-1" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Name, email or reference…" />
        <FormSelect className="md:w-52" aria-label="Destination" placeholder="All destinations" value={params.destination_id ?? ''} onChange={(e) => setParams({ destination_id: e.target.value })} options={lookups.destinations ?? []} />
        <FormSelect className="md:w-52" aria-label="Assigned to" placeholder="Anyone" value={params.assigned_to ?? ''} onChange={(e) => setParams({ assigned_to: e.target.value })} options={lookups.staff ?? []} />
      </div>
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} emptyTitle="No applications yet" />
    </>
  )
}
