import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Info, Pencil } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { FormProvider, useForm } from 'react-hook-form'
import PageHeader from '@/components/admin/PageHeader'
import SortableList from '@/components/admin/SortableList'
import { SchemaDataFields, SectionHeadingFields, useSectionSchemas } from '@/components/admin/fields/PageSectionsField'
import { emptyValue } from '@/components/admin/fields/SchemaField'
import FormInput from '@/components/forms/FormInput'
import FormSwitch from '@/components/forms/FormSwitch'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import Modal from '@/components/ui/Modal'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { cn } from '@/lib/utils'
import { contentServices } from '@/services/admin'

const service = contentServices['homepage-sections']
const QUERY_KEY = ['admin', 'homepage-sections']

/** Sections whose content is a list of records (they use the "items to show" limit). */
const LIST_SECTIONS = ['destinations', 'services', 'universities', 'success_stories', 'testimonials', 'gallery', 'latest_news']

function SectionEditor({ section, schema, onClose }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const fields = schema?.fields ?? []
  const form = useForm({
    defaultValues: {
      eyebrow: section.eyebrow ?? '',
      title: section.title ?? '',
      subtitle: section.subtitle ?? '',
      button_text: section.button_text ?? '',
      button_url: section.button_url ?? '',
      items_limit: section.items_limit ?? '',
      is_active: section.is_active,
      data: Object.fromEntries(fields.map((f) => [f.name, section.data?.[f.name] ?? emptyValue(f)])),
    },
  })
  const handleApiError = useApiErrorHandler(form.setError)
  const { register, formState } = form

  const onSubmit = async (values) => {
    try {
      const res = await service.update(section.id, values)
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: QUERY_KEY })
      onClose()
    } catch (e) {
      handleApiError(e)
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="xl"
      title={schema?.label ?? section.name}
      description={section.note}
      footer={
        <>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="section-form" loading={formState.isSubmitting}>Save section</Button>
        </>
      }
    >
      <FormProvider {...form}>
        <form id="section-form" onSubmit={form.handleSubmit(onSubmit)} noValidate className="space-y-6">
          <SectionHeadingFields />
          <div className="grid gap-4 md:grid-cols-3">
            <FormInput label="Link text" {...register('button_text')} error={formState.errors.button_text?.message} />
            <FormInput label="Link URL" placeholder="/page or https://…" {...register('button_url')} error={formState.errors.button_url?.message} />
            {LIST_SECTIONS.includes(section.key) && (
              <FormInput label="Items to show" type="number" min={1} max={24} {...register('items_limit')} error={formState.errors.items_limit?.message} />
            )}
          </div>
          <SchemaDataFields fields={fields} prefix="data" />
        </form>
      </FormProvider>
    </Modal>
  )
}

export default function HomepageSectionsPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const schemas = useSectionSchemas()
  const query = useQuery({ queryKey: QUERY_KEY, queryFn: () => service.list({ per_page: 200 }) })
  const [rows, setRows] = useState([])
  const [editing, setEditing] = useState(null)

  useEffect(() => setRows(query.data?.data ?? []), [query.data])

  // Sidebar links such as Homepage › Why Choose Us open that section directly (?section=why_choose).
  const [searchParams, setSearchParams] = useSearchParams()
  const wanted = searchParams.get('section')
  useEffect(() => {
    const row = wanted && rows.find((r) => r.key === wanted)
    if (row && schemas.data) setEditing(row)
  }, [wanted, rows, schemas.data])
  const closeEditor = () => {
    setEditing(null)
    if (wanted) setSearchParams({}, { replace: true })
  }

  const onError = (e) => {
    toast.error(e.message)
    queryClient.invalidateQueries({ queryKey: QUERY_KEY })
  }
  const reorder = useMutation({ mutationFn: (ids) => service.reorder(ids), onSuccess: (r) => toast.success(r.message), onError })
  const toggle = useMutation({
    mutationFn: (row) => service.update(row.id, { ...row, is_active: !row.is_active }),
    onMutate: (row) => setRows((list) => list.map((r) => (r.id === row.id ? { ...r, is_active: !r.is_active } : r))),
    onSuccess: (r) => toast.success(r.message),
    onError,
  })

  return (
    <>
      <PageHeader title="Homepage" description="Drag to reorder sections, switch them on or off, and edit their content." actions={<Button to="/" target="_blank" variant="outline">View homepage</Button>} />
      {query.isPending || schemas.isPending ? (
        <LoadingSkeleton variant="table" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : (
        <SortableList
          items={rows}
          className="space-y-2"
          onReorder={(next) => {
            setRows(next)
            reorder.mutate(next.map((r) => r.id))
          }}
          renderItem={(row) => (
            <div className={cn('flex flex-wrap items-center gap-3 rounded-xl border bg-white px-4 py-3 shadow-card', row.is_active ? 'border-slate-200/80' : 'border-dashed border-slate-300 bg-slate-50')}>
              <div className="min-w-0 flex-1">
                <p className="flex items-center gap-2 font-semibold text-slate-800">
                  {row.schema_label}
                  {!row.is_active && <Badge tone="neutral">Hidden</Badge>}
                </p>
                <p className="truncate text-xs text-slate-500">{row.title?.replaceAll('*', '') || row.note}</p>
              </div>
              {row.note && <Info className="hidden size-4 text-slate-300 sm:block" title={row.note} />}
              <FormSwitch checked={row.is_active} onChange={() => toggle.mutate(row)} />
              <Button variant="ghost" size="icon" onClick={() => setEditing(row)} aria-label={`Edit ${row.schema_label}`}><Pencil className="size-4" /></Button>
            </div>
          )}
        />
      )}
      {editing && <SectionEditor key={editing.id} section={editing} schema={schemas.data?.homepage?.[editing.key]} onClose={closeEditor} />}
    </>
  )
}
