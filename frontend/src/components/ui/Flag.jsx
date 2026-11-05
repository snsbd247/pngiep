import 'flag-icons/css/flag-icons.min.css'
import { cn } from '@/lib/utils'

/** Country flag from an ISO 3166-1 alpha-2 code (bundled SVGs, no external CDN). */
export default function Flag({ code, className, title }) {
  if (!code || !/^[a-z]{2}$/i.test(code)) return null
  return <span className={cn('fi shrink-0 rounded-[3px] shadow-sm', `fi-${code.toLowerCase()}`, className)} title={title} role="img" aria-label={title || code.toUpperCase()} />
}
