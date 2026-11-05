import { ChevronDown } from 'lucide-react'
import { forwardRef, useId } from 'react'
import { cn } from '@/lib/utils'
import FormField, { inputClass } from './FormField'

/** options: [{ value, label }] */
const FormSelect = forwardRef(function FormSelect({ label, error, help, required, className, options = [], placeholder, ...props }, ref) {
  const id = useId()
  return (
    <FormField label={label} htmlFor={id} required={required} error={error} help={help} className={className}>
      <div className="relative">
        <select ref={ref} id={id} aria-invalid={!!error} className={cn(inputClass(error), 'h-11 appearance-none pr-10')} {...props}>
          {placeholder !== undefined && <option value="">{placeholder}</option>}
          {options.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>
        <ChevronDown className="pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-slate-400" />
      </div>
    </FormField>
  )
})

export default FormSelect
