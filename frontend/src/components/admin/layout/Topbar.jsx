import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Bell, ChevronDown, ExternalLink, LogOut, Maximize, Menu, Minimize, Search, Settings, UserRound } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import Avatar from '@/components/admin/Avatar'
import Flag from '@/components/ui/Flag'
import { useToast } from '@/components/ui/Toast'
import { useAuth } from '@/context/AuthContext'
import { useSite } from '@/context/SiteContext'
import { withBase } from '@/lib/base'
import { cn, formatDateTime } from '@/lib/utils'
import { NOTIFICATION_ICONS } from '@/pages/admin/NotificationsPage'
import { adminSearchService, notificationService } from '@/services/admin'

/** Closes a popover on outside click or Escape. */
function usePopover() {
  const [open, setOpen] = useState(false)
  const ref = useRef(null)
  useEffect(() => {
    if (!open) return undefined
    const onDown = (e) => ref.current && !ref.current.contains(e.target) && setOpen(false)
    const onKey = (e) => e.key === 'Escape' && setOpen(false)
    document.addEventListener('mousedown', onDown)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onDown)
      document.removeEventListener('keydown', onKey)
    }
  }, [open])
  return { open, setOpen, ref }
}

const panel = 'absolute right-0 top-full z-50 mt-2 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_18px_40px_-12px_rgb(17_12_46_/_0.25)]'

function GlobalSearch() {
  const navigate = useNavigate()
  const { open, setOpen, ref } = usePopover()
  const [q, setQ] = useState('')
  const [term, setTerm] = useState('')
  const input = useRef(null)

  // Debounce typing; Ctrl/⌘+K focuses the box.
  useEffect(() => {
    const t = setTimeout(() => setTerm(q.trim()), 250)
    return () => clearTimeout(t)
  }, [q])
  useEffect(() => {
    const onKey = (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault()
        input.current?.focus()
      }
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [])

  const results = useQuery({ queryKey: ['admin', 'search', term], queryFn: () => adminSearchService.search(term), enabled: term.length >= 2, staleTime: 30_000 })
  const groups = results.data ?? []
  const first = groups[0]?.items[0]
  const go = (url) => {
    setOpen(false)
    setQ('')
    navigate(url)
  }

  return (
    <div ref={ref} className="relative w-full max-w-[345px]">
      <form onSubmit={(e) => { e.preventDefault(); if (first) go(first.url) }}>
        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" />
        <input
          ref={input}
          type="search"
          value={q}
          onChange={(e) => { setQ(e.target.value); setOpen(true) }}
          onFocus={() => setOpen(true)}
          placeholder="Search anything..."
          aria-label="Search"
          className="h-[34px] w-full rounded-md border border-slate-200 bg-slate-50/80 pr-3 pl-9 text-[12.5px] text-slate-700 placeholder:text-slate-500 focus:border-primary focus:bg-white focus:ring-4 focus:ring-primary-100 focus:outline-none"
        />
      </form>
      {open && term.length >= 2 && (
        <div className={cn(panel, 'left-0 w-full max-w-md')}>
          {results.isPending ? (
            <p className="p-4 text-sm text-slate-500">Searching…</p>
          ) : !groups.length ? (
            <p className="p-4 text-sm text-slate-500">No results for “{term}”.</p>
          ) : (
            <div className="max-h-96 overflow-y-auto py-1">
              {groups.map((g) => (
                <div key={g.label} className="py-1">
                  <p className="px-4 py-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">{g.label}</p>
                  {g.items.map((item) => (
                    <button key={item.url} type="button" onClick={() => go(item.url)} className="block w-full px-4 py-2 text-left hover:bg-primary-50">
                      <span className="block truncate text-sm font-medium text-slate-800">{item.title}</span>
                      {item.subtitle && <span className="block truncate text-xs text-slate-500">{item.subtitle}</span>}
                    </button>
                  ))}
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}

function Notifications() {
  const { open, setOpen, ref } = usePopover()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const query = useQuery({ queryKey: ['admin', 'notifications', 'summary'], queryFn: notificationService.summary, refetchInterval: 60_000, staleTime: 30_000 })
  const unread = query.data?.unread ?? 0
  const latest = query.data?.latest ?? []
  const pending = (query.data?.pending?.items ?? []).filter((i) => i.count > 0)
  const refresh = () => queryClient.invalidateQueries({ queryKey: ['admin', 'notifications'] })

  const openItem = async (n) => {
    setOpen(false)
    if (!n.read) await notificationService.markRead(n.id).catch(() => {})
    refresh()
    if (n.url) navigate(n.url)
  }
  const readAll = async () => {
    await notificationService.markAllRead().catch(() => {})
    refresh()
  }

  return (
    <div ref={ref} className="relative">
      <button type="button" onClick={() => setOpen(!open)} className="relative grid size-10 place-items-center rounded-full text-slate-600 hover:bg-slate-100" aria-label={`Notifications (${unread} unread)`} aria-expanded={open}>
        <Bell className="size-5" strokeWidth={1.75} />
        {unread > 0 && (
          <span className="absolute top-0.5 right-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white ring-2 ring-white">
            {unread > 99 ? '99+' : unread}
          </span>
        )}
      </button>
      {open && (
        <div className={cn(panel, 'w-[340px]')}>
          <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p className="text-sm font-semibold text-slate-800">Notifications</p>
            {unread > 0 && <button type="button" onClick={readAll} className="text-xs font-medium text-primary hover:underline">Mark all as read</button>}
          </div>
          {latest.length === 0 ? (
            <p className="px-4 py-6 text-center text-sm text-slate-500">You're all caught up.</p>
          ) : (
            <ul className="max-h-80 divide-y divide-slate-50 overflow-y-auto">
              {latest.map((n) => {
                const meta = NOTIFICATION_ICONS[n.type] ?? { icon: Bell, cls: 'bg-slate-100 text-slate-600' }
                return (
                  <li key={n.id}>
                    <button type="button" onClick={() => openItem(n)} className={cn('flex w-full items-start gap-3 px-4 py-2.5 text-left hover:bg-primary-50', !n.read && 'bg-primary-50/40')}>
                      <span className={cn('grid size-8 shrink-0 place-items-center rounded-lg', meta.cls)}><meta.icon className="size-4" /></span>
                      <span className="min-w-0 flex-1">
                        <span className={cn('block truncate text-[13px]', n.read ? 'text-slate-700' : 'font-semibold text-slate-900')}>{n.title}</span>
                        <span className="block text-[11px] text-slate-500">{formatDateTime(n.created_at)}</span>
                      </span>
                      {!n.read && <span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />}
                    </button>
                  </li>
                )
              })}
            </ul>
          )}
          {pending.length > 0 && (
            <div className="border-t border-slate-100 py-1">
              <p className="px-4 pt-2 pb-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">Waiting for action</p>
              {pending.map((item) => (
                <Link key={item.url} to={item.url} onClick={() => setOpen(false)} className="flex items-center justify-between px-4 py-2 text-sm text-slate-700 hover:bg-primary-50">
                  {item.label}
                  <span className="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary">{item.count}</span>
                </Link>
              ))}
            </div>
          )}
          <Link to="/admin/notifications" onClick={() => setOpen(false)} className="block border-t border-slate-100 px-4 py-2.5 text-center text-sm font-medium text-primary hover:bg-primary-50">View all notifications</Link>
        </div>
      )}
    </div>
  )
}

function FullscreenToggle() {
  const [full, setFull] = useState(() => !!document.fullscreenElement)
  useEffect(() => {
    const onChange = () => setFull(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', onChange)
    return () => document.removeEventListener('fullscreenchange', onChange)
  }, [])
  if (!document.fullscreenEnabled) return null
  const Icon = full ? Minimize : Maximize
  return (
    <button
      type="button"
      onClick={() => (full ? document.exitFullscreen() : document.documentElement.requestFullscreen()).catch(() => {})}
      className="hidden size-10 place-items-center rounded-full text-slate-600 hover:bg-slate-100 sm:grid"
      aria-label={full ? 'Exit full screen' : 'Full screen'}
    >
      <Icon className="size-[19px]" strokeWidth={1.75} />
    </button>
  )
}

function UserMenu() {
  const { user, logout, can } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const { open, setOpen, ref } = usePopover()
  const item = 'flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-primary-50 hover:text-primary'

  const signOut = async () => {
    await logout()
    toast.success('Signed out.')
    navigate('/admin/login', { replace: true })
  }

  return (
    <div ref={ref} className="relative">
      <button type="button" onClick={() => setOpen(!open)} className="flex items-center gap-3 rounded-full py-1 pr-1 pl-1 hover:bg-slate-50 sm:pr-2" aria-expanded={open} aria-haspopup="menu">
        <Avatar name={user?.name} src={user?.avatar_url} className="size-[38px] text-[13px]" />
        <span className="hidden text-left md:block">
          <span className="block text-[13px] leading-tight font-semibold text-slate-900">{user?.name}</span>
          <span className="block text-[11px] text-slate-500">{user?.role?.name}</span>
        </span>
        <ChevronDown className="hidden size-4 text-slate-600 md:block" />
      </button>
      {open && (
        <div className={cn(panel, 'w-56 py-1')} role="menu">
          <Link to="/admin/profile" onClick={() => setOpen(false)} className={item} role="menuitem"><UserRound className="size-4" /> My Profile</Link>
          {can('settings.manage') && <Link to="/admin/settings" onClick={() => setOpen(false)} className={item} role="menuitem"><Settings className="size-4" /> Settings</Link>}
          <a href={withBase('/')} target="_blank" rel="noopener noreferrer" className={item} role="menuitem"><ExternalLink className="size-4" /> Visit Website</a>
          <div className="my-1 border-t border-slate-100" />
          <button type="button" onClick={signOut} className={cn(item, 'text-rose-600 hover:bg-rose-50 hover:text-rose-700')} role="menuitem"><LogOut className="size-4" /> Sign out</button>
        </div>
      )}
    </div>
  )
}

export default function Topbar({ onMenu }) {
  const { setting } = useSite()

  return (
    <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-slate-200/70 bg-white px-4 sm:px-5">
      <button type="button" className="grid size-10 shrink-0 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden" onClick={onMenu} aria-label="Open menu">
        <Menu className="size-5" />
      </button>
      <GlobalSearch />
      <div className="ml-auto flex items-center gap-1 sm:gap-3">
        <a
          href={withBase('/')}
          target="_blank"
          rel="noopener noreferrer"
          className="hidden h-[34px] items-center gap-2.5 rounded-md border border-slate-200 bg-white px-3.5 text-[12.5px] font-medium text-slate-700 hover:border-primary hover:text-primary md:flex"
        >
          <Flag code={setting('general.site_country')} className="!h-4 !w-6 rounded-[2px]" />
          Visit Website
          <ExternalLink className="size-[15px] text-primary" />
        </a>
        <Notifications />
        <FullscreenToggle />
        <span className="mx-1 hidden h-8 w-px bg-slate-200 sm:block" />
        <UserMenu />
      </div>
    </header>
  )
}
