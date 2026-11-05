import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, Copy, FileText, Trash2 } from 'lucide-react'
import { useState } from 'react'
import Card from '@/components/admin/Card'
import PageHeader from '@/components/admin/PageHeader'
import FileUploader from '@/components/forms/FileUploader'
import FormInput from '@/components/forms/FormInput'
import FormSelect from '@/components/forms/FormSelect'
import Button from '@/components/ui/Button'
import ConfirmDialog from '@/components/ui/ConfirmDialog'
import Modal from '@/components/ui/Modal'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useHashScroll } from '@/hooks/useHashScroll'
import { useListParams } from '@/hooks/useListParams'
import { cn, formatBytes, formatDateTime } from '@/lib/utils'
import { mediaService } from '@/services/admin'

function MediaDetails({ media, onClose, onDeleted }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  // Keyed by media id in the parent, so initial state is always for the current file.
  const [form, setForm] = useState({ title: media.title ?? '', alt: media.alt ?? '', folder: media.folder ?? '' })
  const [confirming, setConfirming] = useState(false)

  const save = useMutation({
    mutationFn: () => mediaService.update(media.id, form),
    onSuccess: (res) => {
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
      onClose()
    },
    onError: (e) => toast.error(e.message),
  })

  const remove = useMutation({
    mutationFn: () => mediaService.remove(media.id),
    onSuccess: (res) => {
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
      setConfirming(false)
      onDeleted()
    },
    onError: (e) => toast.error(e.message),
  })

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(media.url)
      toast.success('URL copied.')
    } catch {
      toast.error('Could not copy. Select the URL manually.')
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      size="lg"
      title="File details"
      footer={
        <>
          <Button variant="ghost" className="mr-auto text-rose-600 hover:bg-rose-50" onClick={() => setConfirming(true)}>
            <Trash2 className="size-4" /> Delete
          </Button>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button onClick={() => save.mutate()} loading={save.isPending}>Save</Button>
        </>
      }
    >
      <div className="grid gap-6 md:grid-cols-2">
        <div className="space-y-3">
          <div className="grid aspect-square place-items-center overflow-hidden rounded-2xl bg-slate-100">
            {media.is_image ? <img src={media.url} alt={media.alt || ''} className="size-full object-contain" /> : <FileText className="size-16 text-slate-400" />}
          </div>
          <dl className="grid grid-cols-2 gap-2 text-xs text-slate-500">
            <dt>Type</dt><dd className="text-right text-slate-700">{media.mime_type}</dd>
            <dt>Size</dt><dd className="text-right text-slate-700">{formatBytes(media.size)}</dd>
            {media.width && (<><dt>Dimensions</dt><dd className="text-right text-slate-700">{media.width} × {media.height}</dd></>)}
            <dt>Uploaded</dt><dd className="text-right text-slate-700">{formatDateTime(media.created_at)}</dd>
            {media.uploaded_by && (<><dt>By</dt><dd className="text-right text-slate-700">{media.uploaded_by.name}</dd></>)}
          </dl>
        </div>
        <div className="space-y-4">
          <div className="flex gap-2">
            <FormInput className="flex-1" label="File URL" value={media.url} readOnly onFocus={(e) => e.target.select()} />
            <Button variant="secondary" size="icon" className="mt-7" onClick={copy} aria-label="Copy URL"><Copy className="size-4" /></Button>
          </div>
          <FormInput label="Title" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} />
          <FormInput label="Alt text" help="Describes the image for screen readers and SEO." value={form.alt} onChange={(e) => setForm((f) => ({ ...f, alt: e.target.value }))} />
          <FormInput label="Folder" value={form.folder} onChange={(e) => setForm((f) => ({ ...f, folder: e.target.value }))} />
        </div>
      </div>
      <ConfirmDialog
        open={confirming}
        onClose={() => setConfirming(false)}
        onConfirm={() => remove.mutate()}
        loading={remove.isPending}
        title="Delete this file?"
        message="It will be removed from any page that still uses it."
      />
    </Modal>
  )
}

export default function MediaLibraryPage() {
  const toast = useToast()
  const queryClient = useQueryClient()
  const [params, setParams] = useListParams()
  const [folder, setFolder] = useState('general')
  useHashScroll()
  const [progress, setProgress] = useState(null)
  const [selected, setSelected] = useState([])
  const [active, setActive] = useState(null)
  const [confirmBulk, setConfirmBulk] = useState(false)

  const query = useQuery({
    queryKey: ['admin', 'media', params],
    queryFn: () => mediaService.list({ ...params, per_page: 24 }),
    placeholderData: (prev) => prev,
  })

  const upload = async (files) => {
    try {
      setProgress(0)
      const res = await mediaService.upload(files, folder, (e) => setProgress(Math.round((e.loaded / (e.total || 1)) * 100)))
      toast.success(res.message)
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
    } catch (e) {
      toast.error(e.errors ? Object.values(e.errors)[0][0] : e.message)
    } finally {
      setProgress(null)
    }
  }

  const bulkDelete = useMutation({
    mutationFn: () => mediaService.bulkRemove(selected),
    onSuccess: (res) => {
      toast.success(res.message)
      setSelected([])
      setConfirmBulk(false)
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
    },
    onError: (e) => toast.error(e.message),
  })

  const folders = query.data?.meta?.folders ?? []
  const toggle = (id) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]))

  return (
    <>
      <PageHeader
        title="Media Library"
        description="Images are automatically resized and converted to WebP."
        actions={
          selected.length > 0 && (
            <Button variant="danger" onClick={() => setConfirmBulk(true)}>
              <Trash2 className="size-4" /> Delete {selected.length}
            </Button>
          )
        }
      />

      <Card id="upload" className="mb-6">
        <div className="mb-4 max-w-xs">
          <FormInput label="Upload to folder" value={folder} onChange={(e) => setFolder(e.target.value.replace(/[^a-zA-Z0-9 _-]/g, ''))} />
        </div>
        <FileUploader onFiles={upload} progress={progress} disabled={progress !== null} />
      </Card>

      <div className="mb-5 flex flex-col gap-3 sm:flex-row">
        <SearchBar className="flex-1" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search by name, title or alt text…" />
        <FormSelect className="sm:w-48" value={params.folder ?? ''} onChange={(e) => setParams({ folder: e.target.value })} placeholder="All folders" options={folders.map((f) => ({ value: f, label: f }))} aria-label="Folder" />
        <FormSelect className="sm:w-40" value={params.type ?? ''} onChange={(e) => setParams({ type: e.target.value })} placeholder="All types" options={[{ value: 'image', label: 'Images' }, { value: 'document', label: 'Documents' }]} aria-label="Type" />
      </div>

      {query.isPending ? (
        <LoadingSkeleton count={8} className="lg:grid-cols-4" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !query.data.data.length ? (
        <EmptyState title="No files found" description="Upload images or PDFs to reuse them across the website." />
      ) : (
        <>
          <div className={cn('grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6', query.isFetching && 'opacity-60')}>
            {query.data.data.map((m) => {
              const isSelected = selected.includes(m.id)
              return (
                <div key={m.id} className={cn('group relative overflow-hidden rounded-2xl border-2 bg-white shadow-card transition', isSelected ? 'border-primary' : 'border-transparent')}>
                  <button type="button" onClick={() => setActive(m)} className="block aspect-square w-full bg-slate-100" title={m.original_name}>
                    {m.is_image ? (
                      <img src={m.thumb_url} alt={m.alt || ''} loading="lazy" className="size-full object-cover" />
                    ) : (
                      <span className="grid size-full place-items-center text-slate-400"><FileText className="size-10" /></span>
                    )}
                  </button>
                  <button
                    type="button"
                    onClick={() => toggle(m.id)}
                    aria-label={isSelected ? 'Deselect' : 'Select'}
                    aria-pressed={isSelected}
                    className={cn(
                      'absolute top-2 left-2 grid size-6 place-items-center rounded-md border-2 transition',
                      isSelected ? 'border-primary bg-primary text-white' : 'border-white bg-white/70 text-transparent opacity-0 group-hover:opacity-100 focus:opacity-100',
                    )}
                  >
                    <Check className="size-4" />
                  </button>
                  <div className="px-3 py-2">
                    <p className="truncate text-xs font-medium text-slate-700">{m.title || m.original_name}</p>
                    <p className="text-[11px] text-slate-400">{formatBytes(m.size)} · {m.folder}</p>
                  </div>
                </div>
              )
            })}
          </div>
          <Pagination className="mt-6" meta={query.data.meta} onPageChange={(page) => setParams({ page })} />
        </>
      )}

      {active && <MediaDetails key={active.id} media={active} onClose={() => setActive(null)} onDeleted={() => setActive(null)} />}
      <ConfirmDialog
        open={confirmBulk}
        onClose={() => setConfirmBulk(false)}
        onConfirm={() => bulkDelete.mutate()}
        loading={bulkDelete.isPending}
        title={`Delete ${selected.length} file(s)?`}
        message="They will be removed from any page that still uses them."
      />
    </>
  )
}
