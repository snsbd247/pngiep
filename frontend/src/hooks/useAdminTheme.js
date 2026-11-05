import { useEffect } from 'react'

/** Switches the page to the admin colour theme while an admin screen is mounted. */
export function useAdminTheme() {
  useEffect(() => {
    document.body.classList.add('admin-theme')
    return () => document.body.classList.remove('admin-theme')
  }, [])
}
