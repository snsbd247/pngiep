import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ExternalLink, IndentDecrease, IndentIncrease, Link2, Pencil, Plus, Trash2 } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FormProvider, useForm, useWatch } from 'react-hook-form'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import SortableList from '@/components/admin/SortableList'
import SchemaField from '@/components/admin/fields/SchemaField'
import FormInput from '@/components/forms/FormInput'
import FormSwitch from '@/components/forms/FormSwitch'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import Modal from '@/components/ui/Modal'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { cn, headline } from '@/lib/utils'
import { lookupService, menuService } from '@/services/admin'

const MENUS_KEY = ['admin', 'menus']

/** Tree → flat list with depth (0 = top level, 1 = dropdown child). */
const flatten = (items) => items.flatMap((item) => [{ ...item, depth: 0 }, ...(item.children ?? []).map((c) => ({ ...c, depth: 1 }))])

/** Re-derive parents from order + depth: a child belongs to the nearest top-level item above it. */
function normalise(list) {
  let parent = null
  return list.map((item, i) => {
    const depth = i === 0 ? 0 : item.depth
    if (depth === 0) parent = item.id
    return { ...item, depth, parent_id: depth === 0 ? null : parent }
  })
}

function ItemForm({ menuId, item, pages, onClose }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const form = useForm({
    defaultValues: {
      label: item?.label ?? '',
      link_type: item?.link_type ?? 'internal',
      url: item?.url ?? '',
      page_id: item?.page_id ? String(item.page_id) : '',
      target: item?.target ?? '_self',
      is_active: item?.is_active ?? true,
    },
  })
  const handleApiError = useApiErrorHandler(form.setError)
  const linkType = useWatch({ control: form.control, name: 'link_type' })

  const onSubmit = async (values) => {
    const payload = { ...values, menu_id: menuId, parent_id: item?.parent_id ?? null }
    try {
      const res = item ? await menuService.update(item.id, payload) : await menuService.create(payload)
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: MENUS_KEY })
      onClose()
    } catch (e) {
      handleApiError(e)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={item ? 'Edit menu item' : 'New menu item'}
      footer={
        <>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="menu-item-form" loading={form.formState.isSubmitting}>Save</Button>
        </>
      }
    >
      <FormProvider {...form}>
        <form id="menu-item-form" onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-4">
          <SchemaField field={{ name: 'label', label: 'Label', type: 'text', required: true }} />
          <SchemaField
            field={{ name: 'link_type', label: 'Link to', type: 'select', required: true, options: [
              { value: 'internal', label: 'A path on this website' },
              { value: 'page', label: 'A CMS page' },
              { value: 'external', label: 'Another website' },
            ] }}
          />
          {linkType === 'page' ? (
            <SchemaField field={{ name: 'page_id', label: 'Page', type: 'select', required: true, placeholder: 'Choose a page', options: pages }} />
          ) : (
            <SchemaField field={{ name: 'url', label: linkType === 'external' ? 'URL' : 'Path', type: 'url', required: true, placeholder: linkType === 'external' ? 'https://…' : '/destinations' }} />
          )}
          <SchemaField field={{ name: 'target', label: 'Open in', type: 'select', required: true, options: [
            { value: '_self', label: 'Same tab' },
            { value: '_blank', label: 'New tab' },
          ] }} />
          <SchemaField field={{ name: 'is_active', label: 'Visible', type: 'toggle' }} />
        </form>
      </FormProvider>
    </Modal>
  )
}

function MenuEditor({ menu, pages }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const [list, setList] = useState(() => flatten(menu.items))
  const [name, setName] = useState(menu.name)
  const [editing, setEditing] = useState(undefined)
  const [deleting, setDeleting] = useState(null)

  useEffect(() => setList(flatten(menu.items)), [menu.items])

  const refresh = () => queryClient.invalidateQueries({ queryKey: MENUS_KEY })
  const saveOrder = useMutation({
    mutationFn: (items) => menuService.reorderItems(menu.id, items.map(({ id, parent_id }) => ({ id, parent_id }))),
    onSuccess: (r) => {
      toast.success(r.message)
      refresh()
    },
    onError: (e) => {
      toast.error(e.message)
      refresh()
    },
  })
  const saveMenu = useMutation({
    mutationFn: (payload) => menuService.updateMenu(menu.id, payload),
    onSuccess: (r) => {
      toast.success(r.message)
      refresh()
    },
    onError: (e) => toast.error(e.message),
  })
  const remove = useMutation({
    mutationFn: () => menuService.remove(deleting.id),
    onSuccess: (r) => {
      toast.success(r.message)
      setDeleting(null)
      refresh()
    },
    onError: (e) => toast.error(e.message),
  })

  const commit = (next) => {
    const normalised = normalise(next)
    setList(normalised)
    saveOrder.mutate(normalised)
  }
  const setDepth = (index, depth) => commit(list.map((item, i) => (i === index ? { ...item, depth } : item)))
  const hasChildren = (index) => list[index + 1]?.depth === 1 && list[index].depth === 0

  return (
    <div className="space-y-5">
      <Card>
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
          <FormInput className="flex-1" label="Menu title" help="Shown as the column heading for footer menus." value={name} onChange={(e) => setName(e.target.value)} />
          <FormSwitch className="sm:mb-3" label="Menu enabled" checked={menu.is_active} onChange={(v) => saveMenu.mutate({ name, is_active: v })} />
          <Button variant="secondary" onClick={() => saveMenu.mutate({ name, is_active: menu.is_active })} loading={saveMenu.isPending} disabled={name === menu.name}>Save title</Button>
        </div>
      </Card>

      <div className="flex items-center justify-between">
        <p className="text-xs text-slate-500">Drag to reorder. Use the indent buttons to place an item inside the dropdown of the item above it.</p>
        <Button size="sm" onClick={() => setEditing(null)}><Plus className="size-4" /> Add item</Button>
      </div>

      {list.length === 0 ? (
        <p className="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">This menu is empty.</p>
      ) : (
        <SortableList
          items={list}
          className="space-y-2"
          onReorder={commit}
          renderItem={(item, i) => (
            <div className={cn('flex items-center gap-3 rounded-xl border bg-white px-4 py-2.5 shadow-card', item.depth === 1 && 'ml-8 border-l-4 border-l-primary-200', !item.is_active && 'opacity-60')}>
              <div className="min-w-0 flex-1">
                <p className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                  {item.label}
                  {!item.is_active && <Badge tone="neutral">Hidden</Badge>}
                </p>
                <p className="flex items-center gap-1 truncate text-xs text-slate-500">
                  {item.link_type === 'external' ? <ExternalLink className="size-3" /> : <Link2 className="size-3" />}
                  {item.link_type === 'page' ? `Page: ${item.page?.title ?? '—'} (/${item.page?.slug ?? ''})` : item.url}
                </p>
              </div>
              {item.depth === 0 ? (
                <Button variant="ghost" size="icon" className="size-8" disabled={i === 0 || hasChildren(i)} onClick={() => setDepth(i, 1)} aria-label="Move into dropdown" title="Move into the dropdown above">
                  <IndentIncrease className="size-4" />
                </Button>
              ) : (
                <Button variant="ghost" size="icon" className="size-8" onClick={() => setDepth(i, 0)} aria-label="Move to top level" title="Move to top level">
                  <IndentDecrease className="size-4" />
                </Button>
              )}
              <Button variant="ghost" size="icon" className="size-8" onClick={() => setEditing(item)} aria-label={`Edit ${item.label}`}><Pencil className="size-4" /></Button>
              <Button variant="ghost" size="icon" className="size-8 text-rose-600 hover:bg-rose-50" onClick={() => setDeleting(item)} aria-label={`Delete ${item.label}`}><Trash2 className="size-4" /></Button>
            </div>
          )}
        />
      )}

      {editing !== undefined && <ItemForm key={editing?.id ?? 'new'} menuId={menu.id} item={editing} pages={pages} onClose={() => setEditing(undefined)} />}
      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={() => remove.mutate()}
        loading={remove.isPending}
        title={`Delete "${deleting?.label}"?`}
        message="Any sub-items under it are deleted too."
      />
    </div>
  )
}

export default function MenusPage() {
  const query = useQuery({ queryKey: MENUS_KEY, queryFn: menuService.menus })
  const pages = useQuery({ queryKey: ['admin', 'lookups', ['pages']], queryFn: () => lookupService.get(['pages']) })
  const [activeId, setActiveId] = useState(null)
  const [searchParams] = useSearchParams()
  const menus = query.data ?? []
  const active = menus.find((m) => m.id === activeId) ?? menus.find((m) => m.location === searchParams.get('location')) ?? menus[0]

  return (
    <>
      <PageHeader title="Menus" description="Navigation for the header and footer." />
      {query.isPending ? (
        <LoadingSkeleton variant="table" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : (
        <div className="grid gap-6 lg:grid-cols-[220px_1fr]">
          <nav className="flex gap-1 overflow-x-auto pb-1 lg:flex-col" aria-label="Menus">
            {menus.map((m) => (
              <button
                key={m.id}
                type="button"
                onClick={() => setActiveId(m.id)}
                className={cn('shrink-0 rounded-xl px-4 py-2.5 text-left text-sm font-medium transition', m.id === active?.id ? 'bg-primary text-white shadow-glow' : 'text-slate-600 hover:bg-white hover:text-primary')}
              >
                {m.name}
                <span className={cn('block text-[11px]', m.id === active?.id ? 'text-white/70' : 'text-slate-400')}>{headline(m.location)}</span>
              </button>
            ))}
          </nav>
          {active && <MenuEditor key={active.id} menu={active} pages={pages.data?.pages ?? []} />}
        </div>
      )}
    </>
  )
}
