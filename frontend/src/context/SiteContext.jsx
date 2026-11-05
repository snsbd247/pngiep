import { useQuery } from '@tanstack/react-query'
import { createContext, useContext, useEffect, useMemo } from 'react'
import { siteService } from '@/services/siteService'

const SiteContext = createContext(null)

const EMPTY = { settings: {}, menus: {} }

/**
 * Loads public settings + menus once and exposes helpers:
 *   const { setting, menu } = useSite()
 *   setting('general.site_name')    menu('header')
 * Also applies admin-chosen brand colours and favicon to the document.
 */
export function SiteProvider({ children }) {
  const query = useQuery({ queryKey: ['site'], queryFn: siteService.get, staleTime: 5 * 60 * 1000 })
  const data = query.data ?? EMPTY

  useEffect(() => {
    const appearance = data.settings?.appearance ?? {}
    const root = document.documentElement
    if (appearance.color_primary) root.style.setProperty('--brand-primary', appearance.color_primary)
    if (appearance.color_secondary) root.style.setProperty('--brand-secondary', appearance.color_secondary)
    if (appearance.color_accent) root.style.setProperty('--brand-accent', appearance.color_accent)

    const favicon = data.settings?.general?.site_favicon
    if (favicon) {
      let link = document.querySelector("link[rel='icon']")
      if (!link) {
        link = document.createElement('link')
        link.rel = 'icon'
        document.head.appendChild(link)
      }
      link.href = favicon
    }
  }, [data])

  const value = useMemo(() => {
    const setting = (path, fallback = null) => {
      const [group, key] = path.split('.')
      const v = data.settings?.[group]?.[key]
      return v === undefined || v === null || v === '' ? fallback : v
    }
    const menu = (location) => data.menus?.[location]?.items ?? []
    const menuName = (location) => data.menus?.[location]?.name ?? ''

    return {
      ...data,
      setting,
      menu,
      menuName,
      isLoading: query.isLoading,
      isError: query.isError,
      error: query.error,
      refetch: query.refetch,
    }
  }, [data, query.isLoading, query.isError, query.error, query.refetch])

  return <SiteContext.Provider value={value}>{children}</SiteContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export function useSite() {
  const ctx = useContext(SiteContext)
  if (!ctx) throw new Error('useSite must be used within SiteProvider')
  return ctx
}
