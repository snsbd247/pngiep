import { Fragment } from 'react'
import { cn } from '@/lib/utils'

/**
 * Renders admin text where *words in asterisks* are highlighted in the brand colour,
 * e.g. "Our *Core Services*". Plain text only; no HTML is interpreted.
 */
export default function HighlightText({ text, highlightClassName = 'text-primary' }) {
  if (!text) return null
  const parts = String(text).split(/(\*[^*]+\*)/g)

  return parts.map((part, i) =>
    part.startsWith('*') && part.endsWith('*') && part.length > 2 ? (
      <span key={i} className={cn(highlightClassName)}>
        {part.slice(1, -1)}
      </span>
    ) : (
      <Fragment key={i}>{part}</Fragment>
    ),
  )
}

/** Plain-text version (for alt text, document titles …). */
// eslint-disable-next-line react-refresh/only-export-components
export const stripHighlight = (text = '') => String(text ?? '').replaceAll('*', '')
