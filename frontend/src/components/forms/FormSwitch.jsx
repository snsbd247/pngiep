import { useId } from 'react'
import { cn } from '@/lib/utils'

/** Controlled toggle. Use with react-hook-form's <Controller>. */
export default function FormSwitch({ label, description, checked, onChange, disabled, className }) {
  const id = useId()
  return (
    <div className={cn('flex items-start justify-between gap-4', className)}>
      {(label || description) && (
        <label htmlFor={id} className="cursor-pointer">
          {label && <span className="block text-sm font-medium text-slate-700">{label}</span>}
          {description && <span className="block text-xs text-slate-500">{description}</span>}
        </label>
      )}
      <button
        id={id}
        type="button"
        role="switch"
        aria-checked={!!checked}
        disabled={disabled}
        onClick={() => onChange(!checked)}
        className={cn(
          'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors disabled:opacity-50',
          checked ? 'bg-primary' : 'bg-slate-300',
        )}
      >
        <span className={cn('inline-block size-5 rounded-full bg-white shadow transition-transform', checked ? 'translate-x-5.5' : 'translate-x-0.5')} />
      </button>
    </div>
  )
}
