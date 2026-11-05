import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Pencil, Plus, Star, Trash2 } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Navigate, useParams } from 'react-router-dom'
import PageHeader from '@/components/admin/PageHeader'
import SortableList from '@/components/admin/SortableList'
import FormSelect from '@/components/forms/FormSelect'
import FormSwitch from '@/components/forms/FormSwitch'
import AppIcon from '@/components/ui/AppIcon'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import Image from '@/components/ui/Image'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { resources } from '@/config/resources'
import { useListParams } from '@/hooks/useListParams'
import { cn } from '@/lib/utils'
import { RequirePermission } from '@/router/guards'
import { contentServices, lookupService } from '@/services/admin'

function Cell({ column, row, onPatch }) {
  const value = row[column.key]
  if (column.render) return column.render(row)
  switch (column.type) {
    case 'image':
      return <Image src={value} alt="" className="size-12 rounded-lg border border-slate-100" />
    case 'icon':
      return value ? (
        <span className="grid size-10 place-items-center rounded-lg bg-primary-50 text-primary"><AppIcon name={value} className="size-5" /></span>
      ) : null
    case 'status':
      return <Badge status={value} />
    case 'active':
      return <FormSwitch checked={!!value} onChange={(v) => onPatch({ [column.key]: v })} />
    case 'featured':
      return (
        <button type="button" onClick={() => onPatch({ [column.key]: !value })} aria-pressed={!!value} aria-label="Toggle featured" className={cn('grid size-8 place-items-center rounded-lg', value ? 'text-amber-500' : 'text-slate-300 hover:text-slate-400')}>
          <Star className={cn('size-5', value && 'fill-current')} />
        </button>
      )
    default:
      return <span className="line-clamp-2">{value ?? '—'}</span>
  }
}

function ResourceList({ resourceKey, config }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const service = contentServices[resourceKey]
  const [params, setParams] = useListParams()
  const [deleting, setDeleting] = useState(null)
  // Any filter (including preset ones from the sidebar, e.g. ?is_featured=1) disables drag & drop:
  // reordering a partial list would scramble the full order.
  const filtered = Object.entries(params).some(([k, v]) => k !== 'page' && v !== '' && v !== undefined)
  const preset = config.presets?.find((p) => Object.entries(p.params).every(([k, v]) => String(params[k] ?? '') === v))
  const newUrl = `/admin/content/${resourceKey}/new${preset ? `?${new URLSearchParams(preset.params)}` : ''}`
  const dragEnabled = config.sortable && !filtered
  const listKey = ['admin', resourceKey, params]

  const query = useQuery({
    queryKey: listKey,
    queryFn: () => service.list({ ...params, per_page: dragEnabled ? 200 : 20 }),
    placeholderData: (p) => p,
  })
  const lookups = useQuery({ queryKey: ['admin', 'lookups', config.lookups], queryFn: () => lookupService.get(config.lookups), enabled: !!config.lookups?.length })

  const [rows, setRows] = useState([])
  useEffect(() => setRows(query.data?.data ?? []), [query.data])

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', resourceKey] })

  const patch = useMutation({
    mutationFn: ({ row, changes }) => service.update(row.id, { ...row, ...changes }),
    onMutate: ({ row, changes }) => setRows((list) => list.map((r) => (r.id === row.id ? { ...r, ...changes } : r))),
    onSuccess: () => invalidate(),
    onError: (e) => {
      toast.error(e.message)
      invalidate()
    },
  })

  const reorder = useMutation({
    mutationFn: (ids) => service.reorder(ids),
    onSuccess: (res) => toast.success(res.message),
    onError: (e) => {
      toast.error(e.message)
      invalidate()
    },
  })

  const remove = useMutation({
    mutationFn: () => service.remove(deleting.id),
    onSuccess: (res) => {
      toast.success(res.message)
      setDeleting(null)
      invalidate()
    },
    onError: (e) => toast.error(e.message),
  })

  const renderRow = (row) => (
    <div className="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-card">
      {config.columns.map((col, i) => (
        <div key={col.key} className={cn('text-sm text-slate-700', i === 0 && ['image', 'icon'].includes(col.type) ? 'shrink-0' : i <= 1 ? 'min-w-40 flex-1 font-medium text-slate-800' : 'shrink-0')}>
          {col.header && !['image', 'icon'].includes(col.type) && i > 1 && <span className="mr-1.5 text-xs text-slate-400 md:hidden">{col.header}:</span>}
          <Cell column={col} row={row} onPatch={(changes) => patch.mutate({ row, changes })} />
        </div>
      ))}
      <div className="ml-auto flex gap-1">
        <Button to={`/admin/content/${resourceKey}/${row.id}`} variant="ghost" size="icon" aria-label="Edit"><Pencil className="size-4" /></Button>
        {config.deletable !== false && <Button variant="ghost" size="icon" className="text-rose-600 hover:bg-rose-50" onClick={() => setDeleting(row)} aria-label="Delete"><Trash2 className="size-4" /></Button>}
      </div>
    </div>
  )

  return (
    <>
      <PageHeader
        title={preset?.title ?? config.title}
        description={preset?.description ?? config.description}
        actions={
          <>
            {preset && <Button to={`/admin/content/${resourceKey}`} variant="outline">View all {config.title.toLowerCase()}</Button>}
            {config.creatable !== false && <Button to={newUrl}><Plus className="size-4" /> New {config.singular}</Button>}
          </>
        }
      />

      {(config.searchable || config.filters) && (
        <div className="mb-5 flex flex-col gap-3 sm:flex-row">
          {config.searchable && <SearchBar className="flex-1" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder={`Search ${config.title.toLowerCase()}…`} />}
          {config.filters?.map((f) => (
            <FormSelect key={f.name} className="sm:w-56" aria-label={f.label} placeholder={f.label} value={params[f.name] ?? ''} onChange={(e) => setParams({ [f.name]: e.target.value })} options={f.options ?? lookups.data?.[f.optionsFrom] ?? []} />
          ))}
        </div>
      )}

      {dragEnabled && rows.length > 1 && <p className="mb-3 text-xs text-slate-500">Drag rows to change the order on the website.</p>}

      {query.isPending ? (
        <LoadingSkeleton variant="table" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !rows.length ? (
        <EmptyState
          title={filtered ? 'No matches' : `No ${config.title.toLowerCase()} yet`}
          action={!filtered && config.creatable !== false && <Button to={newUrl} size="sm"><Plus className="size-4" /> New {config.singular}</Button>}
        />
      ) : dragEnabled ? (
        <SortableList
          items={rows}
          className="space-y-2"
          renderItem={renderRow}
          onReorder={(next) => {
            setRows(next)
            reorder.mutate(next.map((r) => r.id))
          }}
        />
      ) : (
        <>
          <ul className={cn('space-y-2', query.isFetching && 'opacity-60')}>
            {rows.map((row) => (
              <li key={row.id}>{renderRow(row)}</li>
            ))}
          </ul>
          <Pagination className="mt-5" meta={query.data?.meta} onPageChange={(page) => setParams({ page })} />
        </>
      )}

      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={() => remove.mutate()}
        loading={remove.isPending}
        title={`Delete this ${config.singular}?`}
        message="It will be removed from the website."
      />
    </>
  )
}

export default function ResourceListPage() {
  const { resource } = useParams()
  const config = resources[resource]
  if (!config) return <Navigate to="/admin" replace />

  return (
    <RequirePermission permission={config.permission}>
      <ResourceList key={resource} resourceKey={resource} config={config} />
    </RequirePermission>
  )
}
