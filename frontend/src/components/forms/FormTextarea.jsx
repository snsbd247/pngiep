import { forwardRef, useId } from 'react'
import { cn } from '@/lib/utils'
import FormField, { inputClass } from './FormField'

const FormTextarea = forwardRef(function FormTextarea({ label, error, help, required, className, rows = 4, ...props }, ref) {
  const id = useId()
  return (
    <FormField label={label} htmlFor={id} required={required} error={error} help={help} className={className}>
      <textarea ref={ref} id={id} rows={rows} aria-invalid={!!error} className={cn(inputClass(error), 'py-3')} {...props} />
    </FormField>
  )
})

export default FormTextarea
