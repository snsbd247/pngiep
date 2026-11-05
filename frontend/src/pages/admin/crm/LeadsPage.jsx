import { useQuery } from '@tanstack/react-query'
import { Eye } from 'lucide-react'
import { Link } from 'react-router-dom'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import FormSelect from '@/components/forms/FormSelect'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import SearchBar from '@/components/ui/SearchBar'
import { useListParams } from '@/hooks/useListParams'
import { formatDateTime } from '@/lib/utils'
import { leadService, lookupService } from '@/services/admin'
import { StatusTabs, labelOf, useMeta } from './shared'

/** Every enquiry from the website (contact, booking, apply, enquiry forms, newsletter) becomes a lead. */
export default function LeadsPage() {
  const meta = useMeta()
  const [params, setParams] = useListParams()
  const query = useQuery({ queryKey: ['admin', 'leads', params], queryFn: () => leadService.list(params), placeholderData: (p) => p })
  const staff = useQuery({ queryKey: ['admin', 'lookups', 'staff'], queryFn: () => lookupService.get(['staff']) }).data?.staff ?? []

  const columns = [
    { key: 'name', header: 'Name', render: (l) => (
      <Link to={`/admin/leads/${l.id}`} className="font-medium text-slate-800 hover:text-primary">
        {l.name || l.email}<span className="block text-xs font-normal text-slate-500">{l.email}{l.phone ? ` · ${l.phone}` : ''}</span>
      </Link>
    ) },
    { key: 'source', header: 'Source', render: (l) => labelOf(meta.lead_sources, l.source) },
    { key: 'destination', header: 'Destination', render: (l) => l.destination?.name ?? '—' },
    { key: 'assignee', header: 'Assigned to', render: (l) => l.assignee?.name ?? <span className="text-slate-400">Unassigned</span> },
    { key: 'status', header: 'Status', render: (l) => <Badge status={l.status}>{labelOf(meta.lead_statuses, l.status)}</Badge> },
    { key: 'created_at', header: 'Received', className: 'whitespace-nowrap', render: (l) => formatDateTime(l.created_at) },
    { key: 'actions', header: '', className: 'text-right', render: (l) => <Button to={`/admin/leads/${l.id}`} variant="ghost" size="icon" aria-label="Open"><Eye className="size-4" /></Button> },
  ]

  return (
    <>
      <PageHeader title="Leads" description="Enquiries from every form on the website, in one place." />
      <StatusTabs options={meta.lead_statuses} counts={query.data?.meta?.counts} value={params.status ?? ''} onChange={(status) => setParams({ status })} />
      <div className="mb-5 flex flex-col gap-3 md:flex-row">
        <SearchBar className="flex-1" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Name, email or phone…" />
        <FormSelect className="md:w-52" aria-label="Source" placeholder="All sources" value={params.source ?? ''} onChange={(e) => setParams({ source: e.target.value })} options={meta.lead_sources ?? []} />
        <FormSelect className="md:w-52" aria-label="Assigned to" placeholder="Anyone" value={params.assigned_to ?? ''} onChange={(e) => setParams({ assigned_to: e.target.value })} options={staff} />
      </div>
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} emptyTitle="No leads yet" />
    </>
  )
}
