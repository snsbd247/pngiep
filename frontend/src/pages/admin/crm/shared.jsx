/* eslint-disable react-refresh/only-export-components */
import { useMutation, useQuery } from '@tanstack/react-query'
import { MessageSquarePlus } from 'lucide-react'
import { useState } from 'react'
import Card from '@/components/admin/Card'
import FormTextarea from '@/components/forms/FormTextarea'
import Button from '@/components/ui/Button'
import { useToast } from '@/components/ui/Toast'
import { cn, formatDateTime } from '@/lib/utils'
import { metaService } from '@/services/admin'

/** Enum option lists from /admin/meta (status labels live in the PHP enums). */
export function useMeta() {
  return useQuery({ queryKey: ['admin', 'meta'], queryFn: metaService.get, staleTime: Infinity }).data ?? {}
}

export const labelOf = (options = [], value) => options.find((o) => o.value === value)?.label ?? value

/** Status filter tabs with counts from meta.counts. */
export function StatusTabs({ options = [], counts = {}, value, onChange, allLabel = 'All' }) {
  const total = Object.values(counts).reduce((a, b) => a + Number(b), 0)
  const tab = (active) => cn('flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition', active ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100')
  return (
    <div className="mb-4 flex gap-1 overflow-x-auto pb-1" role="tablist">
      <button type="button" role="tab" aria-selected={!value} className={tab(!value)} onClick={() => onChange('')}>
        {allLabel} <span className="text-xs opacity-70">{total}</span>
      </button>
      {options.map((o) => (
        <button key={o.value} type="button" role="tab" aria-selected={value === o.value} className={tab(value === o.value)} onClick={() => onChange(o.value)}>
          {o.label} <span className="text-xs opacity-70">{counts[o.value] ?? 0}</span>
        </button>
      ))}
    </div>
  )
}

/** Label / value list for detail pages. rows: [[label, value]]; empty values are skipped. */
export function DetailList({ rows }) {
  return (
    <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
      {rows.filter(([, v]) => v !== null && v !== undefined && v !== '').map(([label, value]) => (
        <div key={label} className="min-w-0">
          <dt className="text-xs font-semibold tracking-wide text-slate-400 uppercase">{label}</dt>
          <dd className="mt-0.5 text-sm break-words text-slate-800">{value}</dd>
        </div>
      ))}
    </dl>
  )
}

/** Internal notes timeline + add form. onAdd(body) must return a promise. */
export function NotesCard({ notes = [], onAdd, onAdded }) {
  const toast = useToast()
  const [body, setBody] = useState('')
  const add = useMutation({
    mutationFn: () => onAdd(body),
    onSuccess: (res) => {
      setBody('')
      toast.success(res.message)
      onAdded?.()
    },
    onError: (e) => toast.error(e.errors?.body?.[0] ?? e.message),
  })

  return (
    <Card title="Internal notes" description="Only staff can see these.">
      <form onSubmit={(e) => { e.preventDefault(); if (body.trim()) add.mutate() }}>
        <FormTextarea rows={3} value={body} onChange={(e) => setBody(e.target.value)} placeholder="Add a note (call summary, next steps…)" aria-label="New note" maxLength={5000} />
        <Button type="submit" size="sm" className="mt-2" loading={add.isPending} disabled={!body.trim()}><MessageSquarePlus className="size-4" /> Add note</Button>
      </form>
      {notes.length > 0 && (
        <ol className="mt-5 space-y-3 border-t border-slate-100 pt-4">
          {notes.map((n) => (
            <li key={n.id} className="rounded-xl bg-slate-50 p-3">
              <p className="text-sm whitespace-pre-line text-slate-700">{n.body}</p>
              <p className="mt-1.5 text-xs text-slate-400">{n.user?.name ?? 'System'} · {formatDateTime(n.created_at)}</p>
            </li>
          ))}
        </ol>
      )}
    </Card>
  )
}

/** Save a Blob (from an authenticated API download) under a file name. */
export function saveBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = Object.assign(document.createElement('a'), { href: url, download: filename })
  document.body.appendChild(link)
  link.click()
  link.remove()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
