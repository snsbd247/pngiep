import { ImagePlus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import MediaPickerModal from '@/components/admin/MediaPickerModal'
import Button from '@/components/ui/Button'
import Image from '@/components/ui/Image'
import FormField from './FormField'

/**
 * Stores a media path (e.g. "media/general/2026/01/logo.webp") chosen from the
 * Media Library or uploaded on the spot. `previewUrl` is used when the server
 * already resolved an absolute URL.
 */
export default function ImageField({ label, value, previewUrl, onChange, error, help, folder = 'general', className }) {
  const [open, setOpen] = useState(false)
  const preview = value ? previewUrl || value : null

  return (
    <FormField label={label} error={error} help={help} className={className}>
      <div className="flex items-center gap-4">
        <div className="size-24 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-[conic-gradient(#f1f5f9_25%,white_0_50%,#f1f5f9_0_75%,white_0)] bg-[length:16px_16px]">
          {preview ? (
            <Image src={preview} alt="" className="size-full object-contain" />
          ) : (
            <div className="grid size-full place-items-center text-slate-300">
              <ImagePlus className="size-7" />
            </div>
          )}
        </div>
        <div className="flex flex-wrap gap-2">
          <Button variant="secondary" size="sm" onClick={() => setOpen(true)}>
            <ImagePlus className="size-4" /> {value ? 'Change' : 'Choose image'}
          </Button>
          {value && (
            <Button variant="ghost" size="sm" onClick={() => onChange(null, null)} className="text-rose-600 hover:bg-rose-50 hover:text-rose-700">
              <Trash2 className="size-4" /> Remove
            </Button>
          )}
        </div>
      </div>
      <MediaPickerModal
        open={open}
        onClose={() => setOpen(false)}
        folder={folder}
        type="image"
        onSelect={(media) => {
          onChange(media.path, media.url)
          setOpen(false)
        }}
      />
    </FormField>
  )
}
