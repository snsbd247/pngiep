import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import Badge from '@/components/ui/Badge'
import SearchBar from '@/components/ui/SearchBar'
import { useListParams } from '@/hooks/useListParams'
import { cn, formatDateTime } from '@/lib/utils'
import { messageService } from '@/services/admin'
import { StatusTabs, labelOf, useMeta } from './shared'

/** Contact form inbox. Archived messages are hidden unless that tab is chosen. */
export default function MessagesPage() {
  const meta = useMeta()
  const [params, setParams] = useListParams()
  const query = useQuery({ queryKey: ['admin', 'messages', params], queryFn: () => messageService.list(params), placeholderData: (p) => p })

  const columns = [
    { key: 'from', header: 'From', render: (m) => (
      <Link to={`/admin/messages/${m.id}`} className={cn('hover:text-primary', m.status === 'new' ? 'font-semibold text-slate-900' : 'text-slate-700')}>
        {m.status === 'new' && <span className="mr-1.5 inline-block size-2 rounded-full bg-primary align-middle" aria-label="Unread" />}
        {m.name}<span className="block text-xs font-normal text-slate-500">{m.email}</span>
      </Link>
    ) },
    { key: 'subject', header: 'Message', render: (m) => (
      <Link to={`/admin/messages/${m.id}`} className="block max-w-xl hover:text-primary">
        <span className="font-medium text-slate-800">{m.subject || m.inquiry_type}</span>
        <span className="line-clamp-1 text-xs text-slate-500">{m.message}</span>
      </Link>
    ) },
    { key: 'inquiry_type', header: 'Type', render: (m) => <span className="text-xs text-slate-600">{m.inquiry_type}</span> },
    { key: 'status', header: 'Status', render: (m) => <Badge status={m.status}>{labelOf(meta.contact_statuses, m.status)}</Badge> },
    { key: 'created_at', header: 'Received', className: 'whitespace-nowrap', render: (m) => formatDateTime(m.created_at) },
  ]

  return (
    <>
      <PageHeader title="Messages" description="Contact form submissions. Reply by email from here." />
      <StatusTabs options={meta.contact_statuses} counts={query.data?.meta?.counts} value={params.status ?? ''} onChange={(status) => setParams({ status })} allLabel="Inbox" />
      <SearchBar className="mb-5" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search name, email, subject or text…" />
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} emptyTitle="No messages" />
    </>
  )
}
