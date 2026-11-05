import { cn } from '@/lib/utils'

const TONES = ['bg-violet-100 text-violet-700', 'bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700', 'bg-rose-100 text-rose-700', 'bg-indigo-100 text-indigo-700']

// eslint-disable-next-line react-refresh/only-export-components
export const initials = (name = '') => name.trim().split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase()

/** Round photo, or coloured initials (colour is stable per name). Defaults apply only when className sets none. */
export default function Avatar({ name = '', src, className = '' }) {
  const size = /(^|\s)(size|h|w)-/.test(className) ? '' : 'size-8'
  const text = /(^|\s)text-\[|(^|\s)text-(xs|sm|base)/.test(className) ? '' : 'text-[11px]'
  if (src) return <img src={src} alt="" className={cn(size, 'shrink-0 rounded-full object-cover', className)} />
  const tone = TONES[[...name].reduce((sum, c) => sum + c.charCodeAt(0), 0) % TONES.length]
  return (
    <span className={cn(size, text, 'grid shrink-0 place-items-center rounded-full font-semibold', tone, className)} aria-hidden="true">
      {initials(name) || '?'}
    </span>
  )
}
