import { useQuery } from '@tanstack/react-query'
import DataTable from '@/components/admin/DataTable'
import PageHeader from '@/components/admin/PageHeader'
import FormSelect from '@/components/forms/FormSelect'
import Badge from '@/components/ui/Badge'
import SearchBar from '@/components/ui/SearchBar'
import { useListParams } from '@/hooks/useListParams'
import { cn, formatDateTime, headline } from '@/lib/utils'
import { activityLogService } from '@/services/admin'

const GROUPS = [
  { value: '', label: 'All', title: 'Activity Log' },
  { value: 'login', label: 'Login', title: 'Login Activities' },
  { value: 'content', label: 'Content', title: 'Content Activities' },
  { value: 'users', label: 'Users', title: 'User Activities' },
  { value: 'system', label: 'System', title: 'System Activities' },
]

const ACTION_TONES = { created: 'success', create: 'success', upload: 'success', updated: 'info', update: 'info', deleted: 'danger', delete: 'danger', login: 'primary', logout: 'neutral' }

export default function ActivityLogPage() {
  const [params, setParams] = useListParams()
  const query = useQuery({ queryKey: ['admin', 'activity-logs', params], queryFn: () => activityLogService.list(params), placeholderData: (p) => p })
  const modules = query.data?.meta?.modules ?? []

  const columns = [
    { key: 'created_at', header: 'When', className: 'whitespace-nowrap', render: (l) => formatDateTime(l.created_at) },
    { key: 'user', header: 'User', render: (l) => l.user?.name ?? 'System' },
    { key: 'action', header: 'Action', render: (l) => <Badge tone={ACTION_TONES[l.action] ?? 'neutral'}>{headline(l.action)}</Badge> },
    { key: 'module', header: 'Module', render: (l) => headline(l.module) },
    { key: 'description', header: 'Details', render: (l) => <span className="text-slate-600">{l.description}</span> },
    { key: 'ip_address', header: 'IP', className: 'font-mono text-xs' },
  ]

  return (
    <>
      <PageHeader title={GROUPS.find((g) => g.value === (params.group ?? ''))?.title ?? 'Activity Log'} description="An audit trail of important admin actions." />
      <div className="mb-4 flex gap-1 overflow-x-auto pb-1" role="tablist">
        {GROUPS.map((g) => (
          <button key={g.value} type="button" role="tab" aria-selected={(params.group ?? '') === g.value} onClick={() => setParams({ group: g.value })}
            className={cn('shrink-0 rounded-lg px-3 py-2 text-sm font-medium', (params.group ?? '') === g.value ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100')}>
            {g.label}
          </button>
        ))}
      </div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row">
        <SearchBar className="flex-1" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search details…" />
        <FormSelect className="sm:w-56" value={params.module ?? ''} onChange={(e) => setParams({ module: e.target.value })} placeholder="All modules" options={modules.map((m) => ({ value: m, label: headline(m) }))} aria-label="Module" />
      </div>
      <DataTable columns={columns} query={query} onPageChange={(page) => setParams({ page })} emptyTitle="No activity yet" />
    </>
  )
}
