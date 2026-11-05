import { ArrowDown, ArrowUp, ImagePlus, Plus, Trash2, X } from 'lucide-react'
import { lazy, Suspense, useId, useState } from 'react'
import { Controller, useFieldArray, useFormContext } from 'react-hook-form'
import MediaPickerModal from '@/components/admin/MediaPickerModal'
import FormField, { inputClass } from '@/components/forms/FormField'
import FormInput from '@/components/forms/FormInput'
import FormSelect from '@/components/forms/FormSelect'
import FormSwitch from '@/components/forms/FormSwitch'
import FormTextarea from '@/components/forms/FormTextarea'
import ImageField from '@/components/forms/ImageField'
import Button from '@/components/ui/Button'
import Image from '@/components/ui/Image'
import { cn } from '@/lib/utils'
import IconPicker from './IconPicker'

// The editor is heavy; load it only on forms that use it.
const RichTextEditor = lazy(() => import('./RichTextEditor'))

/** Read a nested error message: errorAt(errors, 'data.items.0.title') */
// eslint-disable-next-line react-refresh/only-export-components
export const errorAt = (errors, path) => path.split('.').reduce((node, key) => node?.[key], errors)?.message

/** Default empty value for a field type (used for new records and new repeater rows). */
// eslint-disable-next-line react-refresh/only-export-components
export function emptyValue(field) {
  if (field.default !== undefined) return field.default
  switch (field.type) {
    case 'toggle':
      return false
    case 'images':
    case 'list':
    case 'repeater':
    case 'sections':
    case 'multiselect':
      return []
    case 'seo':
      return {}
    default:
      return ''
  }
}

const SEO_FIELDS = [
  { name: 'title', label: 'SEO title', type: 'text', help: 'Shown in Google results and browser tabs. ~60 characters.' },
  { name: 'description', label: 'Meta description', type: 'textarea', help: '~155 characters.' },
  { name: 'og_image', label: 'Social share image', type: 'image', help: '1200×630 recommended.' },
  { name: 'og_title', label: 'Social share title', type: 'text' },
  { name: 'og_description', label: 'Social share description', type: 'textarea' },
  { name: 'canonical_url', label: 'Canonical URL', type: 'url', help: 'Only if this content also exists at another address.' },
  {
    name: 'robots',
    label: 'Search engine indexing',
    type: 'select',
    placeholder: 'Default (index, follow)',
    options: [
      { value: 'index,follow', label: 'Index, follow' },
      { value: 'noindex,follow', label: 'Hide from search (noindex)' },
      { value: 'noindex,nofollow', label: 'Hide and do not follow links' },
    ],
  },
]

function ImagesField({ value = [], onChange, max = 20, label, help, error }) {
  const [open, setOpen] = useState(false)
  const move = (from, to) => {
    const next = [...value]
    next.splice(to, 0, next.splice(from, 1)[0])
    onChange(next)
  }
  return (
    <FormField label={label} help={help} error={error}>
      <div className="flex flex-wrap gap-3">
        {value.map((src, i) => (
          <div key={`${src}-${i}`} className="group relative size-24 overflow-hidden rounded-xl border border-slate-200">
            <Image src={src} alt="" className="size-full" />
            <div className="absolute inset-0 flex items-end justify-center gap-1 bg-black/40 p-1 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
              {i > 0 && <button type="button" onClick={() => move(i, i - 1)} className="grid size-6 place-items-center rounded bg-white/90 text-slate-700" aria-label="Move left"><ArrowUp className="size-3 -rotate-90" /></button>}
              <button type="button" onClick={() => onChange(value.filter((_, j) => j !== i))} className="grid size-6 place-items-center rounded bg-white/90 text-rose-600" aria-label="Remove image"><X className="size-3" /></button>
            </div>
          </div>
        ))}
        {value.length < max && (
          <button type="button" onClick={() => setOpen(true)} className="grid size-24 place-items-center rounded-xl border-2 border-dashed border-slate-200 text-slate-400 hover:border-primary-300 hover:text-primary" aria-label="Add image">
            <ImagePlus className="size-6" />
          </button>
        )}
      </div>
      <MediaPickerModal
        open={open}
        onClose={() => setOpen(false)}
        folder="content"
        onSelect={(media) => {
          onChange([...value, media.path])
          setOpen(false)
        }}
      />
    </FormField>
  )
}

function ListField({ value = [], onChange, label, help, error, placeholder }) {
  const [draft, setDraft] = useState('')
  const add = () => {
    if (!draft.trim()) return
    onChange([...value, draft.trim()])
    setDraft('')
  }
  return (
    <FormField label={label} help={help} error={error}>
      <ul className="mb-2 space-y-1.5">
        {value.map((item, i) => (
          <li key={i} className="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
            <span className="flex-1">{item}</span>
            <button type="button" onClick={() => onChange(value.filter((_, j) => j !== i))} className="text-slate-400 hover:text-rose-600" aria-label={`Remove ${item}`}><X className="size-4" /></button>
          </li>
        ))}
      </ul>
      <div className="flex gap-2">
        <input
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              e.preventDefault()
              add()
            }
          }}
          placeholder={placeholder || 'Type and press Enter'}
          className={cn(inputClass(), 'h-10')}
        />
        <Button variant="secondary" size="sm" className="h-10" onClick={add}><Plus className="size-4" /> Add</Button>
      </div>
    </FormField>
  )
}

function RepeaterField({ field, name, lookups }) {
  const { control } = useFormContext()
  const { fields: rows, append, remove, move } = useFieldArray({ control, name })
  const max = field.max ?? 50
  const titleKey = field.fields.find((f) => f.type === 'text')?.name
  // columns: compact one-row items (e.g. day + from + to), set per field definition.
  const grid = { 3: 'md:grid-cols-3', 4: 'md:grid-cols-4' }[field.columns] ?? 'md:grid-cols-2'

  return (
    <FormField label={field.label} help={field.help}>
      <div className="space-y-3">
        {rows.map((row, i) => (
          <div key={row.id} className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <div className="mb-3 flex items-center justify-between gap-2">
              <span className="text-xs font-semibold tracking-wide text-slate-500 uppercase">#{i + 1}{titleKey && row[titleKey] ? ` · ${row[titleKey]}` : ''}</span>
              <div className="flex gap-1">
                <Button variant="ghost" size="icon" className="size-8" disabled={i === 0} onClick={() => move(i, i - 1)} aria-label="Move up"><ArrowUp className="size-4" /></Button>
                <Button variant="ghost" size="icon" className="size-8" disabled={i === rows.length - 1} onClick={() => move(i, i + 1)} aria-label="Move down"><ArrowDown className="size-4" /></Button>
                <Button variant="ghost" size="icon" className="size-8 text-rose-600 hover:bg-rose-50" onClick={() => remove(i)} aria-label="Remove"><Trash2 className="size-4" /></Button>
              </div>
            </div>
            <div className={cn('grid gap-4', grid)}>
              {field.fields.map((sub) => (
                <div key={sub.name} className={cn(['textarea', 'html', 'repeater', 'images'].includes(sub.type) && 'md:col-span-2')}>
                  <SchemaField field={sub} name={`${name}.${i}.${sub.name}`} lookups={lookups} />
                </div>
              ))}
            </div>
          </div>
        ))}
        {rows.length < max && (
          <Button variant="outline" size="sm" onClick={() => append(Object.fromEntries(field.fields.map((f) => [f.name, emptyValue(f)])))}>
            <Plus className="size-4" /> Add {field.itemLabel || 'item'}
          </Button>
        )}
      </div>
    </FormField>
  )
}

/**
 * Renders one form control from a field definition. Used by admin resource forms
 * and by section editors whose schemas come from the API (config/sections.php).
 *
 * field: { name, label, type, required?, help?, options?, optionsFrom?, suggestionsFrom?, max?, fields? }
 * name:  full form path, e.g. "data.items.0.title" (defaults to field.name)
 */
export default function SchemaField({ field, name = field.name, lookups = {} }) {
  const { register, control, formState } = useFormContext()
  const listId = useId()
  const error = errorAt(formState.errors, name)
  const rules = field.required ? { required: `${field.label} is required.` } : {}
  const common = { label: field.label, help: field.help, error, required: field.required }

  switch (field.type) {
    case 'textarea':
      return <FormTextarea {...common} rows={field.rows ?? 3} placeholder={field.placeholder} {...register(name, rules)} />
    case 'html':
      return (
        <Controller
          name={name}
          control={control}
          rules={rules}
          render={({ field: f }) => (
            <FormField {...common}>
              <Suspense fallback={<div className="h-56 animate-pulse rounded-xl bg-slate-100" />}>
                <RichTextEditor value={f.value} onChange={f.onChange} invalid={!!error} placeholder={field.label} />
              </Suspense>
            </FormField>
          )}
        />
      )
    case 'image':
      return (
        <Controller
          name={name}
          control={control}
          rules={rules}
          render={({ field: f }) => <ImageField {...common} value={f.value || null} folder={field.folder || 'content'} onChange={(path) => f.onChange(path || '')} />}
        />
      )
    case 'images':
      return <Controller name={name} control={control} render={({ field: f }) => <ImagesField {...common} max={field.max} value={f.value || []} onChange={f.onChange} />} />
    case 'list':
      return <Controller name={name} control={control} render={({ field: f }) => <ListField {...common} placeholder={field.placeholder} value={f.value || []} onChange={f.onChange} />} />
    case 'icon':
      return (
        <Controller
          name={name}
          control={control}
          rules={rules}
          render={({ field: f }) => (
            <FormField {...common}>
              <IconPicker value={f.value} onChange={(v) => f.onChange(v || '')} invalid={!!error} />
            </FormField>
          )}
        />
      )
    case 'toggle':
      return (
        <Controller
          name={name}
          control={control}
          render={({ field: f }) => <FormSwitch label={field.label} description={field.help} checked={!!f.value} onChange={f.onChange} />}
        />
      )
    case 'select': {
      const options = field.options ?? lookups[field.optionsFrom] ?? []
      return <FormSelect {...common} options={options} placeholder={field.placeholder ?? (field.required ? undefined : '—')} {...register(name, rules)} />
    }
    case 'color':
      return (
        <Controller
          name={name}
          control={control}
          render={({ field: f }) => (
            <FormField {...common}>
              <input type="color" value={f.value || '#000000'} onChange={f.onChange} className="h-11 w-16 cursor-pointer rounded-xl border border-slate-200 p-1" aria-label={field.label} />
            </FormField>
          )}
        />
      )
    case 'repeater':
      return <RepeaterField field={field} name={name} lookups={lookups} />
    case 'multiselect': {
      // Checkbox list of ids (e.g. the services a counsellor offers).
      const options = field.options ?? lookups[field.optionsFrom] ?? []
      return (
        <Controller
          name={name}
          control={control}
          render={({ field: f }) => {
            const value = (f.value ?? []).map(String)
            const toggle = (v) => f.onChange(value.includes(v) ? value.filter((x) => x !== v) : [...value, v])
            return (
              <FormField {...common}>
                <div className="grid gap-2 sm:grid-cols-2">
                  {options.map((o) => (
                    <label key={o.value} className="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:border-primary-200">
                      <input type="checkbox" className="size-4 accent-primary" checked={value.includes(String(o.value))} onChange={() => toggle(String(o.value))} />
                      {o.label}
                    </label>
                  ))}
                  {!options.length && <p className="text-sm text-slate-400">Nothing to choose yet.</p>}
                </div>
              </FormField>
            )
          }}
        />
      )
    }
    case 'readonly':
      // Display-only list from the record, e.g. the placeholders an email template supports.
      return (
        <Controller
          name={name}
          control={control}
          render={({ field: f }) => (
            <FormField {...common}>
              <div className="flex flex-wrap gap-1.5">
                {[].concat(f.value ?? []).map((v) => <code key={v} className="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{field.format ? field.format(v) : v}</code>)}
              </div>
            </FormField>
          )}
        />
      )
    case 'seo':
      return (
        <div className="grid gap-4 md:grid-cols-2">
          {SEO_FIELDS.map((sub) => (
            <div key={sub.name} className={cn(['textarea', 'image'].includes(sub.type) && 'md:col-span-2')}>
              <SchemaField field={sub} name={`${name}.${sub.name}`} />
            </div>
          ))}
        </div>
      )
    default: {
      const suggestions = field.suggestionsFrom ? lookups[field.suggestionsFrom] ?? [] : []
      const type = { number: 'number', email: 'email', date: 'date', datetime: 'datetime-local', time: 'time' }[field.type] ?? 'text'
      return (
        <>
          <FormInput
            {...common}
            type={type}
            placeholder={field.placeholder ?? (field.type === 'url' ? '/page or https://…' : undefined)}
            list={suggestions.length ? listId : undefined}
            {...register(name, rules)}
          />
          {suggestions.length > 0 && (
            <datalist id={listId}>
              {suggestions.map((o) => (
                <option key={o.value} value={o.value} />
              ))}
            </datalist>
          )}
        </>
      )
    }
  }
}
