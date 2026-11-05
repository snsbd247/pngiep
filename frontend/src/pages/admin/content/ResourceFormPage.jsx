import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, ExternalLink } from 'lucide-react'
import { useMemo } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { Navigate, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import PageSectionsField from '@/components/admin/fields/PageSectionsField'
import SchemaField, { emptyValue } from '@/components/admin/fields/SchemaField'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { resources } from '@/config/resources'
import { useApiErrorHandler } from '@/hooks/useApiForm'
import { cn } from '@/lib/utils'
import { RequirePermission } from '@/router/guards'
import { contentServices, lookupService } from '@/services/admin'

const getPath = (obj, path) => path.split('.').reduce((node, key) => node?.[key], obj)
const setPath = (obj, path, value) => {
  const keys = path.split('.')
  keys.slice(0, -1).reduce((node, key) => (node[key] ??= {}), obj)[keys.at(-1)] = value
}

/** Build form values from an API record (or defaults for a new one). */
function toFormValues(fields, record) {
  const values = {}
  fields.forEach((field) => {
    let value = record ? getPath(record, field.name) : undefined
    if (field.type === 'sections') {
      value = (value ?? []).map((s) => ({ id: s.id, type: s.type, eyebrow: s.eyebrow ?? '', title: s.title ?? '', subtitle: s.subtitle ?? '', is_active: s.is_active, data: s.data ?? {} }))
    }
    if (field.type === 'select' && value !== undefined && value !== null) value = String(value)
    if (field.type === 'date' && value) value = String(value).slice(0, 10)
    if (field.type === 'datetime' && value) {
      // API sends ISO 8601; <input type="datetime-local"> wants local "YYYY-MM-DDTHH:mm".
      const d = new Date(value)
      value = new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16)
    }
    setPath(values, field.name, value ?? emptyValue(field))
  })
  return values
}

function ResourceForm({ resourceKey, config, id, record, lookups }) {
  const toast = useToast()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const service = contentServices[resourceKey]
  const isNew = !record
  const [searchParams] = useSearchParams()
  // A new record opened from a filtered list (e.g. "Destination FAQs") starts with that filter's values.
  const defaults = useMemo(() => {
    const values = toFormValues(config.fields, record)
    if (!record) {
      config.fields.forEach((f) => {
        const v = searchParams.get(f.name)
        if (v !== null) values[f.name] = f.type === 'toggle' ? v === '1' || v === 'true' : v
      })
    }
    return values
  }, [config.fields, record, searchParams])
  const form = useForm({ defaultValues: defaults })
  const handleApiError = useApiErrorHandler(form.setError)

  const main = config.fields.filter((f) => !f.side && !f.group)
  const side = config.fields.filter((f) => f.side)
  const groups = Object.entries(
    config.fields.filter((f) => f.group).reduce((acc, f) => ({ ...acc, [f.group]: [...(acc[f.group] ?? []), f] }), {}),
  )

  const onSubmit = async (values) => {
    try {
      const res = isNew ? await service.create(values) : await service.update(id, values)
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: ['admin', resourceKey] })
      if (isNew) {
        navigate(`/admin/content/${resourceKey}/${res.data.id}`, { replace: true })
      } else {
        queryClient.setQueryData(['admin', resourceKey, 'record', String(id)], res.data)
        form.reset(toFormValues(config.fields, res.data))
      }
    } catch (error) {
      handleApiError(error)
    }
  }

  const renderField = (field) =>
    field.type === 'sections' ? (
      <div key={field.name} className="md:col-span-2">
        <PageSectionsField name={field.name} lookups={lookups} />
      </div>
    ) : (
      <div key={field.name} className={cn(['textarea', 'html', 'list', 'image', 'images', 'repeater', 'seo'].includes(field.type) && 'md:col-span-2')}>
        <SchemaField field={field} lookups={lookups} />
      </div>
    )

  const viewUrl = !record || record.status === 'draft' ? null : config.view ? config.view(record) : resourceKey === 'pages' ? `/${record.slug}` : resourceKey === 'services' ? `/services/${record.slug}` : null

  return (
    <FormProvider {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} noValidate>
        <PageHeader
          title={isNew ? `New ${config.singular}` : `Edit ${config.singular}`}
          actions={
            <>
              <Button to={`/admin/content/${resourceKey}`} variant="outline"><ArrowLeft className="size-4" /> Back</Button>
              {viewUrl && <Button to={viewUrl} target="_blank" variant="outline"><ExternalLink className="size-4" /> View</Button>}
              <Button type="submit" loading={form.formState.isSubmitting}>{isNew ? 'Create' : 'Save changes'}</Button>
            </>
          }
        />
        <div className="grid items-start gap-6 lg:grid-cols-[1fr_320px]">
          <div className="space-y-6">
            <Card title="Details">
              <div className="grid gap-5 md:grid-cols-2">{main.map(renderField)}</div>
            </Card>
            {groups.map(([title, fields]) => (
              <Card key={title} title={title}>
                <div className="grid gap-5 md:grid-cols-2">{fields.map(renderField)}</div>
              </Card>
            ))}
          </div>
          {side.length > 0 && (
            <Card title="Settings" className="lg:sticky lg:top-24">
              <div className="space-y-5">{side.map((f) => <SchemaField key={f.name} field={f} lookups={lookups} />)}</div>
              <Button type="submit" className="mt-6 w-full" loading={form.formState.isSubmitting}>{isNew ? 'Create' : 'Save changes'}</Button>
            </Card>
          )}
        </div>
      </form>
    </FormProvider>
  )
}

function ResourceFormLoader({ resourceKey, config }) {
  const { id } = useParams()
  const isNew = id === 'new'
  const service = contentServices[resourceKey]

  const record = useQuery({ queryKey: ['admin', resourceKey, 'record', id], queryFn: () => service.get(id), enabled: !isNew })
  const lookups = useQuery({ queryKey: ['admin', 'lookups', config.lookups], queryFn: () => lookupService.get(config.lookups), enabled: !!config.lookups?.length })

  if (!isNew && record.isPending) return <LoadingSkeleton variant="table" />
  if (!isNew && record.isError) return <ErrorState error={record.error} onRetry={record.refetch} />
  if (config.lookups?.length && lookups.isPending) return <LoadingSkeleton variant="table" />

  return <ResourceForm key={id} resourceKey={resourceKey} config={config} id={isNew ? null : id} record={isNew ? null : record.data} lookups={lookups.data ?? {}} />
}

export default function ResourceFormPage() {
  const { resource } = useParams()
  const config = resources[resource]
  if (!config) return <Navigate to="/admin" replace />

  return (
    <RequirePermission permission={config.permission}>
      <ResourceFormLoader resourceKey={resource} config={config} />
    </RequirePermission>
  )
}
