import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, FileText } from 'lucide-react'
import { useState } from 'react'
import FileUploader from '@/components/forms/FileUploader'
import Button from '@/components/ui/Button'
import Modal from '@/components/ui/Modal'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { cn } from '@/lib/utils'
import { mediaService } from '@/services/admin'

/** Pick an existing file from the Media Library or upload a new one. */
export default function MediaPickerModal({ open, onClose, onSelect, folder = 'general', type = 'image' }) {
  const toast = useToast()
  const queryClient = useQueryClient()
  const [params, setParams] = useState({ page: 1, search: '', type })
  const [selected, setSelected] = useState(null)
  const [progress, setProgress] = useState(null)

  const query = useQuery({
    queryKey: ['admin', 'media', params],
    queryFn: () => mediaService.list({ ...params, per_page: 18 }),
    enabled: open,
  })

  if (!open) return null

  const upload = async (files) => {
    try {
      setProgress(0)
      const res = await mediaService.upload(files, folder, (e) => setProgress(Math.round((e.loaded / (e.total || 1)) * 100)))
      queryClient.invalidateQueries({ queryKey: ['admin', 'media'] })
      toast.success(res.message)
      if (res.data?.[0]) setSelected(res.data[0])
    } catch (e) {
      toast.error(e.message)
    } finally {
      setProgress(null)
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      size="xl"
      title="Media Library"
      description="Choose a file or upload a new one."
      footer={
        <>
          <Button variant="outline" onClick={onClose}>Cancel</Button>
          <Button disabled={!selected} onClick={() => onSelect(selected)}>Use selected</Button>
        </>
      }
    >
      <FileUploader onFiles={upload} progress={progress} disabled={progress !== null} accept={type === 'image' ? 'image/jpeg,image/png,image/webp,image/gif' : undefined} />

      <div className="mt-6 mb-4">
        <SearchBar value={params.search} onSearch={(search) => setParams((p) => ({ ...p, search, page: 1 }))} placeholder="Search media…" />
      </div>

      {query.isPending ? (
        <LoadingSkeleton variant="table" count={4} />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !query.data.data.length ? (
        <EmptyState title="No files yet" description="Upload your first file above." />
      ) : (
        <>
          <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            {query.data.data.map((m) => (
              <button
                key={m.id}
                type="button"
                onClick={() => setSelected(m)}
                onDoubleClick={() => onSelect(m)}
                className={cn(
                  'group relative aspect-square overflow-hidden rounded-xl border-2 bg-slate-50 transition',
                  selected?.id === m.id ? 'border-primary ring-4 ring-primary-100' : 'border-transparent hover:border-primary-200',
                )}
                title={m.original_name}
              >
                {m.is_image ? (
                  <img src={m.thumb_url} alt={m.alt || ''} loading="lazy" className="size-full object-cover" />
                ) : (
                  <span className="grid size-full place-items-center text-slate-400"><FileText className="size-8" /></span>
                )}
                {selected?.id === m.id && (
                  <span className="absolute top-1.5 right-1.5 grid size-6 place-items-center rounded-full bg-primary text-white">
                    <Check className="size-4" />
                  </span>
                )}
              </button>
            ))}
          </div>
          <Pagination className="mt-5" meta={query.data.meta} onPageChange={(page) => setParams((p) => ({ ...p, page }))} />
        </>
      )}
    </Modal>
  )
}
