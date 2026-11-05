import { useQuery } from '@tanstack/react-query'
import { ArrowDown, ArrowUp, ChevronDown, Eye, EyeOff, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useFieldArray, useFormContext, useWatch } from 'react-hook-form'
import FormInput from '@/components/forms/FormInput'
import FormSelect from '@/components/forms/FormSelect'
import FormTextarea from '@/components/forms/FormTextarea'
import Badge from '@/components/ui/Badge'
import Button from '@/components/ui/Button'
import { cn } from '@/lib/utils'
import { sectionSchemaService } from '@/services/admin'
import SchemaField, { emptyValue, errorAt } from './SchemaField'

// eslint-disable-next-line react-refresh/only-export-components
export function useSectionSchemas() {
  return useQuery({ queryKey: ['admin', 'section-schemas'], queryFn: sectionSchemaService.get, staleTime: Infinity })
}

/** Common heading fields every section has (stored in columns, not in `data`). */
export function SectionHeadingFields({ prefix = '' }) {
  const { register, formState } = useFormContext()
  const p = (n) => (prefix ? `${prefix}.${n}` : n)
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <FormInput label="Eyebrow (small text above the title)" {...register(p('eyebrow'))} error={errorAt(formState.errors, p('eyebrow'))} />
      <FormInput label="Title" help="Wrap words in *asterisks* to highlight them." {...register(p('title'))} error={errorAt(formState.errors, p('title'))} />
      <FormTextarea className="md:col-span-2" label="Subtitle" rows={2} {...register(p('subtitle'))} error={errorAt(formState.errors, p('subtitle'))} />
    </div>
  )
}

/** Fields of one schema rendered under `${prefix}.data.*`. */
export function SchemaDataFields({ fields = [], prefix = 'data', lookups }) {
  if (!fields.length) return null
  return (
    <div className="grid gap-4 md:grid-cols-2">
      {fields.map((f) => (
        <div key={f.name} className={cn(['textarea', 'html', 'repeater', 'images', 'image'].includes(f.type) && 'md:col-span-2')}>
          <SchemaField field={f} name={`${prefix}.${f.name}`} lookups={lookups} />
        </div>
      ))}
    </div>
  )
}

function SectionCard({ index, total, schema, onMove, onRemove, lookups }) {
  const { setValue, control } = useFormContext()
  const [open, setOpen] = useState(false)
  const [title, active] = useWatch({ control, name: [`sections.${index}.title`, `sections.${index}.is_active`] })

  return (
    <div className={cn('rounded-2xl border bg-white shadow-card', active === false ? 'border-dashed border-slate-300 opacity-70' : 'border-slate-200')}>
      <div className="flex items-center gap-2 p-3 pl-4">
        <button type="button" onClick={() => setOpen((o) => !o)} className="flex min-w-0 flex-1 items-center gap-3 text-left" aria-expanded={open}>
          <ChevronDown className={cn('size-4 shrink-0 text-slate-400 transition-transform', open && 'rotate-180')} />
          <Badge tone="primary">{schema?.label ?? 'Unknown'}</Badge>
          <span className="truncate text-sm font-medium text-slate-700">{title?.replaceAll('*', '') || 'Untitled section'}</span>
        </button>
        <Button variant="ghost" size="icon" className="size-8" onClick={() => setValue(`sections.${index}.is_active`, active === false, { shouldDirty: true })} aria-label={active === false ? 'Show section' : 'Hide section'} title={active === false ? 'Hidden' : 'Visible'}>
          {active === false ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
        </Button>
        <Button variant="ghost" size="icon" className="size-8" disabled={index === 0} onClick={() => onMove(index, index - 1)} aria-label="Move up"><ArrowUp className="size-4" /></Button>
        <Button variant="ghost" size="icon" className="size-8" disabled={index === total - 1} onClick={() => onMove(index, index + 1)} aria-label="Move down"><ArrowDown className="size-4" /></Button>
        <Button variant="ghost" size="icon" className="size-8 text-rose-600 hover:bg-rose-50" onClick={() => onRemove(index)} aria-label="Remove section"><Trash2 className="size-4" /></Button>
      </div>
      {open && (
        <div className="space-y-5 border-t border-slate-100 p-5">
          {schema?.note && <p className="rounded-lg bg-primary-50 px-3 py-2 text-xs text-primary-700">{schema.note}</p>}
          <SectionHeadingFields prefix={`sections.${index}`} />
          <SchemaDataFields fields={schema?.fields} prefix={`sections.${index}.data`} lookups={lookups} />
        </div>
      )}
    </div>
  )
}

/** Ordered list of typed content blocks for a CMS page. */
export default function PageSectionsField({ name = 'sections', lookups }) {
  const { control } = useFormContext()
  const { fields: rows, append, remove, move } = useFieldArray({ control, name })
  const schemas = useSectionSchemas()
  const [newType, setNewType] = useState('')
  const types = schemas.data?.page_types ?? {}

  const add = () => {
    const schema = types[newType]
    if (!schema) return
    append({ type: newType, eyebrow: '', title: '', subtitle: '', is_active: true, data: Object.fromEntries(schema.fields.map((f) => [f.name, emptyValue(f)])) })
    setNewType('')
  }

  return (
    <div className="space-y-3">
      {rows.length === 0 && <p className="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">No sections yet. Add the first one below.</p>}
      {rows.map((row, i) => (
        <SectionCard key={row.id} index={i} total={rows.length} schema={types[row.type]} onMove={move} onRemove={remove} lookups={lookups} />
      ))}
      <div className="flex flex-col gap-2 sm:flex-row">
        <FormSelect
          className="flex-1"
          aria-label="Section type"
          placeholder="Choose a section type…"
          value={newType}
          onChange={(e) => setNewType(e.target.value)}
          options={Object.entries(types).map(([value, t]) => ({ value, label: t.label }))}
        />
        <Button variant="secondary" onClick={add} disabled={!newType}>
          <Plus className="size-4" /> Add section
        </Button>
      </div>
    </div>
  )
}
