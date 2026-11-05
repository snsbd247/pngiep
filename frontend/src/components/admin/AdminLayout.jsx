import { X } from 'lucide-react'
import { Suspense, useEffect, useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import Sidebar from '@/components/admin/layout/Sidebar'
import Topbar from '@/components/admin/layout/Topbar'
import { LoadingSkeleton } from '@/components/ui/States'
import { useSite } from '@/context/SiteContext'
import { useAdminTheme } from '@/hooks/useAdminTheme'
import { cn, interpolate } from '@/lib/utils'

const COLLAPSE_KEY = 'admin.sidebar.collapsed'

export default function AdminLayout() {
  useAdminTheme()
  const { setting } = useSite()
  const { pathname } = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [collapsed, setCollapsed] = useState(() => {
    try {
      return localStorage.getItem(COLLAPSE_KEY) === '1'
    } catch {
      return false
    }
  })

  useEffect(() => setMobileOpen(false), [pathname])

  const toggle = () =>
    setCollapsed((c) => {
      try {
        localStorage.setItem(COLLAPSE_KEY, c ? '0' : '1')
      } catch {
        // storage unavailable: keep the in-memory state only
      }
      return !c
    })

  return (
    <div className="flex min-h-screen flex-col bg-[#f6f7fb]">
      <div className="flex flex-1">
        {/* Desktop sidebar: sticky, so the footer bar below spans the full width. */}
        <aside className={cn('hidden shrink-0 transition-[width] duration-200 lg:block', collapsed ? 'w-[76px]' : 'w-[250px]')}>
          {/* Sticky, never taller than the row, so it ends where the footer bar starts. */}
          <div className="sticky top-0 h-[min(100vh,100%)]">
            <Sidebar collapsed={collapsed} onToggle={toggle} />
          </div>
        </aside>

        {/* Mobile drawer */}
        <div className={cn('fixed inset-0 z-50 overflow-hidden lg:hidden', mobileOpen ? 'visible' : 'invisible')}>
          <div className={cn('absolute inset-0 bg-slate-900/50 transition-opacity', mobileOpen ? 'opacity-100' : 'opacity-0')} onClick={() => setMobileOpen(false)} />
          <div className={cn('absolute inset-y-0 left-0 w-[270px] transition-transform', mobileOpen ? 'translate-x-0' : '-translate-x-full')}>
            <Sidebar onNavigate={() => setMobileOpen(false)} />
            <button type="button" onClick={() => setMobileOpen(false)} className="absolute top-3 -right-12 grid size-10 place-items-center rounded-full bg-white text-slate-700" aria-label="Close menu">
              <X className="size-5" />
            </button>
          </div>
        </div>

        <div className="flex min-w-0 flex-1 flex-col">
          <Topbar onMenu={() => setMobileOpen(true)} />
          <main className="flex-1 px-4 pt-3.5 pb-4 sm:px-6 lg:px-[26px]">
            <Suspense fallback={<LoadingSkeleton variant="table" />}>
              <Outlet />
            </Suspense>
          </main>
        </div>
      </div>
      <footer className="bg-[#261a7d] px-4 py-[11px] text-center text-[12px] text-white/85">
        {interpolate(setting('footer.footer_copyright', `© {year} ${setting('general.site_name', '')}`))}
      </footer>
    </div>
  )
}
