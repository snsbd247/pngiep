import { forwardRef, useId } from 'react'
import { cn } from '@/lib/utils'
import FormField, { inputClass } from './FormField'

/** <FormInput label="Email" {...register('email')} error={errors.email?.message} /> */
const FormInput = forwardRef(function FormInput({ label, error, help, required, className, inputClassName, icon: Icon, ...props }, ref) {
  const id = useId()
  return (
    <FormField label={label} htmlFor={id} required={required} error={error} help={help} className={className}>
      <div className="relative">
        {Icon && <Icon className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />}
        <input
          ref={ref}
          id={id}
          aria-invalid={!!error}
          className={cn(inputClass(error), 'h-11', Icon && 'pl-10', inputClassName)}
          {...props}
        />
      </div>
    </FormField>
  )
})

export default FormInput
