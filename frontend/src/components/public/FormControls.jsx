import { CheckCircle2 } from 'lucide-react'
import { forwardRef } from 'react'
import { cn } from '@/lib/utils'

/** Light form controls for public pages (white cards). Pair with react-hook-form's register(). */

const base = (invalid, icon) =>
  cn(
    'w-full rounded-lg border bg-white text-sm text-slate-700 placeholder:text-slate-400 transition focus:ring-4 focus:outline-none',
    icon ? 'pl-10 pr-3' : 'px-3.5',
    invalid ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-200 focus:border-primary focus:ring-primary-100',
  )

export function Field({ label, required, error, icon: Icon, hint, className, children }) {
  return (
    <label className={cn('block', className)}>
      {label && (
        <span className="mb-1.5 block text-sm font-medium text-ink">
          {label} {required && <span className="text-rose-500">*</span>}
        </span>
      )}
      <span className="relative block">
        {Icon && <Icon className="pointer-events-none absolute top-3.5 left-3.5 size-4 text-slate-400" />}
        {children}
      </span>
      {error ? <span className="mt-1 block text-xs font-medium text-rose-600">{error}</span> : hint && <span className="mt-1 block text-xs text-slate-400">{hint}</span>}
    </label>
  )
}

export const TextInput = forwardRef(function TextInput({ invalid, icon, className, ...props }, ref) {
  return <input ref={ref} className={cn(base(invalid, icon), 'h-11', className)} aria-invalid={invalid || undefined} {...props} />
})

export const SelectInput = forwardRef(function SelectInput({ invalid, icon, className, children, ...props }, ref) {
  return (
    <select ref={ref} className={cn(base(invalid, icon), 'h-11 appearance-none bg-[length:16px] bg-[right_0.75rem_center] bg-no-repeat pr-9', className)}
      style={{ backgroundImage: "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E\")" }}
      aria-invalid={invalid || undefined} {...props}>
      {children}
    </select>
  )
})

export const TextArea = forwardRef(function TextArea({ invalid, icon, className, rows = 4, ...props }, ref) {
  return <textarea ref={ref} rows={rows} className={cn(base(invalid, icon), 'py-2.5', className)} aria-invalid={invalid || undefined} {...props} />
})

/** Hidden honeypot field; bots fill it, people never see it. */
export const Honeypot = forwardRef(function Honeypot(props, ref) {
  return <input ref={ref} type="text" tabIndex={-1} autoComplete="off" className="hidden" aria-hidden="true" {...props} />
})

export const AgreeCheckbox = forwardRef(function AgreeCheckbox({ error, children, ...props }, ref) {
  return (
    <div>
      <label className="flex cursor-pointer items-start gap-2.5 text-sm text-slate-600">
        <input ref={ref} type="checkbox" className="mt-0.5 size-4 shrink-0 rounded border-slate-300 accent-primary" {...props} />
        <span>{children}</span>
      </label>
      {error && <p className="mt-1 text-xs font-medium text-rose-600">{error}</p>}
    </div>
  )
})

export function SuccessPanel({ title, children }) {
  return (
    <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-6 text-center">
      <CheckCircle2 className="mx-auto size-12 text-emerald-500" />
      {title && <h3 className="mt-3 text-xl">{title}</h3>}
      <div className="mt-2 text-sm text-slate-600">{children}</div>
    </div>
  )
}
