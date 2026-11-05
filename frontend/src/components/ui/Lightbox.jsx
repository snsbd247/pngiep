import { ChevronLeft, ChevronRight, X } from 'lucide-react'
import { useEffect } from 'react'
import { createPortal } from 'react-dom'
import { assetUrl } from '@/lib/utils'

/** Full-screen image viewer. images: [{ image, title }], index: open position or null. */
export default function Lightbox({ images = [], index, onChange, onClose }) {
  const open = index !== null && index !== undefined && images[index]

  useEffect(() => {
    if (!open) return undefined
    const onKey = (e) => {
      if (e.key === 'Escape') onClose()
      if (e.key === 'ArrowRight') onChange((index + 1) % images.length)
      if (e.key === 'ArrowLeft') onChange((index - 1 + images.length) % images.length)
    }
    document.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
    }
  }, [open, index, images.length, onChange, onClose])

  if (!open) return null
  const current = images[index]
  const nav = 'absolute top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20'

  return createPortal(
    <div className="fixed inset-0 z-[95] flex flex-col items-center justify-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label={current.title || 'Image'} onClick={onClose}>
      <button type="button" onClick={onClose} className="absolute top-4 right-4 grid size-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Close">
        <X className="size-6" />
      </button>
      <img src={assetUrl(current.image)} alt={current.title || ''} className="max-h-[80vh] max-w-full rounded-lg object-contain" onClick={(e) => e.stopPropagation()} />
      {current.title && <p className="mt-4 text-center text-sm text-white/85">{current.title}</p>}
      {images.length > 1 && (
        <>
          <button type="button" className={`${nav} left-3 sm:left-6`} onClick={(e) => { e.stopPropagation(); onChange((index - 1 + images.length) % images.length) }} aria-label="Previous image">
            <ChevronLeft className="size-6" />
          </button>
          <button type="button" className={`${nav} right-3 sm:right-6`} onClick={(e) => { e.stopPropagation(); onChange((index + 1) % images.length) }} aria-label="Next image">
            <ChevronRight className="size-6" />
          </button>
        </>
      )}
    </div>,
    document.body,
  )
}
