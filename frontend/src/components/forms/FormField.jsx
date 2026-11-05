import { cn } from '@/lib/utils'

export const inputClass = (error) =>
  cn(
    'w-full rounded-xl border bg-white px-4 text-sm text-slate-800 placeholder:text-slate-400 transition focus:outline-none focus:ring-4 disabled:bg-slate-50 disabled:text-slate-500',
    error ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100' : 'border-slate-200 focus:border-primary focus:ring-primary-100',
  )

/** Label + control + help/error text. Every form control is wrapped in this. */
export default function FormField({ label, htmlFor, required, error, help, className, children }) {
  return (
    <div className={cn('space-y-1.5', className)}>
      {label && (
        <label htmlFor={htmlFor} className="block text-sm font-medium text-slate-700">
          {label}
          {required && <span className="ml-0.5 text-rose-500">*</span>}
        </label>
      )}
      {children}
      {error ? (
        <p className="text-xs font-medium text-rose-600" role="alert">{error}</p>
      ) : help ? (
        <p className="text-xs text-slate-500">{help}</p>
      ) : null}
    </div>
  )
}
