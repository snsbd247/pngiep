import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'

/**
 * Scrolls to the element named by the URL hash (e.g. /admin/profile#password) once `ready`
 * is true, and briefly highlights it. Used by sidebar links that point inside a page.
 */
export function useHashScroll(ready = true) {
  const { hash } = useLocation()
  useEffect(() => {
    if (!ready || !hash) return undefined
    const el = document.getElementById(decodeURIComponent(hash.slice(1)))
    if (!el) return undefined
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    el.classList.add('ring-4', 'ring-primary-200', 'rounded-xl', 'transition-shadow')
    const t = setTimeout(() => el.classList.remove('ring-4', 'ring-primary-200'), 2200)
    return () => clearTimeout(t)
  }, [hash, ready])
}
