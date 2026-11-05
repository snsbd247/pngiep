import { ImageOff } from 'lucide-react'
import { useState } from 'react'
import { assetUrl, cn } from '@/lib/utils'

/** Lazy-loaded image with a graceful placeholder when the source is missing or broken. */
export default function Image({ src, alt = '', className, imgClassName, eager = false, ...props }) {
  const [failed, setFailed] = useState(false)
  const url = assetUrl(src)

  if (!url || failed) {
    return (
      <div className={cn('grid place-items-center bg-gradient-to-br from-primary-50 to-primary-100 text-primary-300', className)} role="img" aria-label={alt}>
        <ImageOff className="size-8" />
      </div>
    )
  }

  return (
    <img
      src={url}
      alt={alt}
      loading={eager ? 'eager' : 'lazy'}
      decoding="async"
      onError={() => setFailed(true)}
      className={cn('object-cover', className, imgClassName)}
      {...props}
    />
  )
}
