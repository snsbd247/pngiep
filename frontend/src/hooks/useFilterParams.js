import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router-dom'

/**
 * URL-backed list filters supporting multi-value keys (?destination[]=uk&destination[]=usa).
 * arrayKeys: keys that hold several values. Changing any filter resets to page 1.
 */
export function useFilterParams(arrayKeys = []) {
  const [searchParams, setSearchParams] = useSearchParams()

  const params = useMemo(() => {
    const values = { page: Number(searchParams.get('page')) || 1 }
    arrayKeys.forEach((key) => {
      values[key] = searchParams.getAll(`${key}[]`)
    })
    searchParams.forEach((value, key) => {
      if (!key.endsWith('[]') && key !== 'page') values[key] = value
    })
    return values
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams])

  const setParams = useCallback(
    (updates) => {
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev)
          Object.entries(updates).forEach(([key, value]) => {
            if (arrayKeys.includes(key)) {
              next.delete(`${key}[]`)
              ;(value ?? []).forEach((v) => next.append(`${key}[]`, v))
            } else if (value === '' || value === null || value === undefined) next.delete(key)
            else next.set(key, String(value))
          })
          if (!('page' in updates)) next.delete('page')
          return next
        },
        { replace: true },
      )
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [setSearchParams],
  )

  const clear = useCallback(() => setSearchParams(new URLSearchParams(), { replace: true }), [setSearchParams])

  /** Query object for the API (empty values dropped; arrays serialised as key[]=a&key[]=b). */
  const apiParams = useMemo(() => {
    const out = {}
    Object.entries(params).forEach(([k, v]) => {
      if (Array.isArray(v) ? v.length : v !== '' && v !== undefined) out[k] = v
    })
    return out
  }, [params])

  return { params, apiParams, setParams, clear }
}
