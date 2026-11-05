import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router-dom'

/**
 * List filters stored in the URL (?page=2&search=uk), so lists are shareable
 * and survive refresh / back navigation.
 */
export function useListParams(defaults = {}) {
  const [searchParams, setSearchParams] = useSearchParams()

  const params = useMemo(() => {
    const values = { page: 1, ...defaults }
    searchParams.forEach((value, key) => {
      values[key] = key === 'page' ? Number(value) || 1 : value
    })
    return values
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams])

  const setParams = useCallback(
    (updates, { resetPage = true } = {}) => {
      setSearchParams(
        (prev) => {
          const next = new URLSearchParams(prev)
          Object.entries(updates).forEach(([k, v]) => (v === '' || v === null || v === undefined ? next.delete(k) : next.set(k, String(v))))
          if (resetPage && !('page' in updates)) next.delete('page')
          return next
        },
        { replace: true },
      )
    },
    [setSearchParams],
  )

  return [params, setParams]
}
