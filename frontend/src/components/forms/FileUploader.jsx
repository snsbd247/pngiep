import { UploadCloud } from 'lucide-react'
import { useRef, useState } from 'react'
import { cn, formatBytes } from '@/lib/utils'

const DEFAULT_ACCEPT = 'image/jpeg,image/png,image/webp,image/gif,application/pdf'

/**
 * Drag & drop / click-to-browse file picker with client-side type & size checks.
 * The server re-validates everything (real MIME + size); this is only for fast feedback.
 */
export default function FileUploader({
  onFiles,
  accept = DEFAULT_ACCEPT,
  multiple = true,
  maxSizeMb = 5,
  disabled,
  progress = null,
  hint,
  className,
}) {
  const inputRef = useRef(null)
  const [dragging, setDragging] = useState(false)
  const [error, setError] = useState('')

  const handle = (fileList) => {
    const allowed = accept.split(',').map((t) => t.trim())
    const files = Array.from(fileList || [])
    const invalid = files.find((f) => !allowed.includes(f.type))
    const tooBig = files.find((f) => f.size > maxSizeMb * 1024 * 1024)

    if (invalid) return setError(`"${invalid.name}" is not an allowed file type.`)
    if (tooBig) return setError(`"${tooBig.name}" is ${formatBytes(tooBig.size)}. Maximum is ${maxSizeMb} MB.`)
    setError('')
    if (files.length) onFiles(multiple ? files : files.slice(0, 1))
  }

  return (
    <div className={className}>
      <button
        type="button"
        disabled={disabled}
        onClick={() => inputRef.current?.click()}
        onDragOver={(e) => {
          e.preventDefault()
          setDragging(true)
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={(e) => {
          e.preventDefault()
          setDragging(false)
          if (!disabled) handle(e.dataTransfer.files)
        }}
        className={cn(
          'flex w-full flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-10 text-center transition',
          dragging ? 'border-primary bg-primary-50' : 'border-slate-200 bg-slate-50/50 hover:border-primary-300 hover:bg-primary-50/40',
          disabled && 'cursor-not-allowed opacity-60',
        )}
      >
        <span className="mb-3 grid size-12 place-items-center rounded-2xl bg-white text-primary shadow-card">
          <UploadCloud className="size-6" />
        </span>
        <span className="text-sm font-semibold text-slate-700">
          <span className="text-primary">Click to upload</span> or drag and drop
        </span>
        <span className="mt-1 text-xs text-slate-500">{hint || `JPG, PNG, WebP, GIF or PDF · up to ${maxSizeMb} MB each`}</span>
        {progress !== null && (
          <span className="mt-4 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-slate-200">
            <span className="block h-full rounded-full bg-primary transition-all" style={{ width: `${progress}%` }} />
          </span>
        )}
      </button>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        multiple={multiple}
        className="hidden"
        onChange={(e) => {
          handle(e.target.files)
          e.target.value = ''
        }}
      />
      {error && <p className="mt-2 text-xs font-medium text-rose-600">{error}</p>}
    </div>
  )
}
