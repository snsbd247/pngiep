import { useCallback } from 'react'
import { useToast } from '@/components/ui/Toast'

/**
 * Returns a handler that maps Laravel 422 errors onto react-hook-form fields
 * and toasts anything else. Nested keys like "settings.site_name" are mapped
 * via the optional `fieldMap` function.
 *
 *   const handleApiError = useApiErrorHandler(setError)
 *   try { await save(values) } catch (e) { handleApiError(e) }
 */
export function useApiErrorHandler(setError, fieldMap = (key) => key) {
  const toast = useToast()

  return useCallback(
    (error) => {
      if (error?.isValidation && setError) {
        Object.entries(error.errors).forEach(([key, messages], i) => {
          setError(fieldMap(key), { type: 'server', message: messages[0] }, { shouldFocus: i === 0 })
        })
        toast.error(error.message || 'Please fix the highlighted fields.')
        return
      }
      toast.error(error?.message || 'Something went wrong.')
    },
    [setError, fieldMap, toast],
  )
}
