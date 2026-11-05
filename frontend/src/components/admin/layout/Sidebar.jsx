import { ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react'
import { useMemo, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useToast } from '@/components/ui/Toast'
import { adminNav } from '@/config/adminNav'
import { useAuth } from '@/context/AuthContext'
import { useSite } from '@/context/SiteContext'
import { cn } from '@/lib/utils'

const IGNORED_PARAMS = ['page', 'search', 'per_page', 'section', 'part']

/**
 * How well a link matches the current location (0 = not at all). A link matches when its
 * path matches (exactly for `end`, query or hash links; otherwise also sub-pages) and its
 * query and hash match exactly. Longer links score higher, so the most specific wins.
 */
function score(to, location, end) {
  const [pathQuery, hash = ''] = to.split('#')
  const [path, query = ''] = pathQuery.split('?')
  const wanted = new URLSearchParams(query)
  const current = new URLSearchParams(location.search)
  const currentKeys = [...current.keys()].filter((k) => !IGNORED_PARAMS.includes(k))
  const exact = location.pathname === path
  if (!exact && (end || query || hash || !location.pathname.startsWith(`${path}/`))) return 0
  // Homepage section links (?section=…) compare that parameter only.
  if (wanted.has('section')) return wanted.get('section') === current.get('section') && (wanted.get('part') ?? '') === (current.get('part') ?? '') ? to.length : 0
  if ([...wanted.keys()].length !== currentKeys.length) return 0
  if (![...wanted].every(([k, v]) => current.get(k) === v)) return 0
  if (location.hash.slice(1) !== hash) return 0
  return to.length + (exact ? 1 : 0)
}

/** Remove what the user can't access; drop groups left empty. Each item gets a unique `key` (its label path). */
function visible(items, can, prefix = '') {
  return items
    .map((item) => {
      const key = prefix + item.label
      return item.children ? { ...item, key, children: visible(item.children, can, `${key}/`) } : { ...item, key }
    })
    .filter((item) => (item.children ? item.children.length > 0 : !item.permission || can(item.permission)))
}

const links = (items) => items.flatMap((i) => (i.children ? [...(i.to ? [i] : []), ...links(i.children)] : i.to ? [i] : []))

/** `padded` = false for group rows, whose inner buttons carry the padding. */
const itemCls = (active, collapsed, padded = true) =>
  cn(
    'group flex w-full items-center gap-3 rounded-md text-[13.5px] transition-colors',
    padded && 'py-[7px]',
    collapsed ? 'justify-center px-0' : padded && 'px-[14px]',
    active ? 'bg-gradient-to-r from-primary to-[#5b3fe6] font-medium text-white shadow-[0_6px_16px_-6px_rgb(74_43_212_/_0.7)]' : 'text-slate-700 hover:bg-primary-50 hover:text-primary',
  )

/** Website name stacked one word per line; the last word in the site accent colour (as on the logo). */
function Brand() {
  const { setting } = useSite()
  const name = setting('general.site_name', '')
  const logo = setting('general.site_logo')
  const words = name.toUpperCase().split(/\s+/).filter(Boolean)
  const primary = setting('appearance.color_primary', '#1a3fa6')
  const accent = setting('appearance.color_accent', '#1e8a3e')

  return (
    <Link to="/admin" className="flex min-w-0 items-center gap-3" title={name}>
      {logo ? <img src={logo} alt="" className="size-[60px] shrink-0 object-contain" /> : <span className="grid size-12 shrink-0 place-items-center rounded-full bg-primary text-lg font-bold text-white">{words[0]?.[0]}</span>}
      <span className="min-w-0 text-[12.5px] leading-[15px] font-bold tracking-tight">
        {words.length <= 4
          ? words.map((w, i) => <span key={i} className="block" style={{ color: i === words.length - 1 && words.length > 1 ? accent : primary }}>{w}</span>)
          : <span style={{ color: primary }}>{words.join(' ')}</span>}
      </span>
    </Link>
  )
}

export default function Sidebar({ collapsed = false, onToggle, onNavigate }) {
  const { can, logout } = useAuth()
  const toast = useToast()
  const navigate = useNavigate()
  const location = useLocation()

  const items = useMemo(() => visible(adminNav, can), [can])
  // The single best-matching link is the active one. Some pages appear in two menus
  // (e.g. Appointment Settings); links pass their key in the history state so the one
  // that was clicked stays highlighted.
  const activeKey = useMemo(() => {
    let best = []
    let top = 0
    links(items).forEach((l) => {
      const s = score(l.to, location, l.end)
      if (s > top) [best, top] = [[l.key], s]
      else if (s && s === top) best.push(l.key)
    })
    return best.find((k) => k === location.state?.nav) ?? best[0] ?? null
  }, [items, location])

  const contains = (item) => item.key === activeKey || (item.children ?? []).some(contains)
  // Groups containing the current page start open.
  const [open, setOpen] = useState(() => {
    const o = {}
    const walk = (list) => list.forEach((i) => {
      if (i.children) {
        if (contains(i)) o[i.key] = true
        walk(i.children)
      }
    })
    walk(items)
    return o
  })

  const signOut = async () => {
    await logout()
    toast.success('Signed out.')
    navigate('/admin/login', { replace: true })
  }

  const renderChildren = (list, depth) => (
    <ul className={cn('my-1 space-y-0.5 border-l border-slate-200 pl-3', depth === 1 ? 'ml-[23px]' : 'ml-3')}>
      {list.map((child) => {
        const key = child.key
        if (child.children) {
          const expanded = open[key] ?? false
          return (
            <li key={key}>
              <button
                type="button"
                onClick={() => setOpen((o) => ({ ...o, [key]: !expanded }))}
                aria-expanded={expanded}
                className={cn('flex w-full items-center justify-between rounded-md px-3 py-1.5 text-left text-[12.5px] transition-colors', contains(child) ? 'font-medium text-primary' : 'text-slate-600 hover:text-primary')}
              >
                {child.label}
                <ChevronRight className={cn('size-3.5 text-slate-400 transition-transform', expanded && 'rotate-90')} />
              </button>
              {expanded && renderChildren(child.children, depth + 1)}
            </li>
          )
        }
        const active = key === activeKey
        return (
          <li key={key}>
            <Link
              to={child.to}
              state={{ nav: key }}
              onClick={onNavigate}
              aria-current={active ? 'page' : undefined}
              className={cn('block rounded-md px-3 py-1.5 text-[12.5px] transition-colors', active ? 'bg-primary-50 font-medium text-primary' : 'text-slate-500 hover:text-primary')}
            >
              {child.label}
            </Link>
          </li>
        )
      })}
    </ul>
  )

  return (
    <div className="flex h-full flex-col border-r border-slate-200/70 bg-white">
      <div className={cn('flex h-[80px] shrink-0 items-center gap-2', collapsed ? 'justify-center px-2' : 'justify-between pr-3 pl-[22px]')}>
        {!collapsed && <Brand />}
        {onToggle && (
          <button type="button" onClick={onToggle} className="grid size-8 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-primary" aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}>
            {collapsed ? <ChevronsRight className="size-5" /> : <ChevronsLeft className="size-5" />}
          </button>
        )}
      </div>

      <nav className="flex-1 space-y-[3px] overflow-y-auto px-3 pb-6 [scrollbar-width:thin]" aria-label="Admin">
        {items.map((item) => {
          if (item.action === 'logout') {
            return (
              <button key={item.label} type="button" onClick={signOut} className={cn(itemCls(false, collapsed), 'hover:bg-rose-50 hover:text-rose-600')} title={collapsed ? item.label : undefined}>
                <item.icon className="size-[19px] shrink-0" strokeWidth={1.75} />
                {!collapsed && <span className="truncate">{item.label}</span>}
              </button>
            )
          }

          if (!item.children) {
            const active = item.key === activeKey
            return (
              <Link key={item.key} to={item.to} state={{ nav: item.key }} onClick={onNavigate} className={itemCls(active, collapsed)} aria-current={active ? 'page' : undefined} title={collapsed ? item.label : undefined}>
                <item.icon className="size-[19px] shrink-0" strokeWidth={active ? 2.2 : 1.75} />
                {!collapsed && <span className="truncate">{item.label}</span>}
              </Link>
            )
          }

          const expanded = !collapsed && (open[item.label] ?? false)
          const groupActive = contains(item)
          const toggle = () => {
            if (collapsed) return onToggle?.()
            setOpen((o) => ({ ...o, [item.label]: item.to ? true : !expanded }))
            if (item.to) {
              navigate(item.to, { state: { nav: item.key } })
              onNavigate?.()
            }
          }
          return (
            <div key={item.label}>
              <div className={cn(itemCls(item.key === activeKey, collapsed, false), 'gap-0', groupActive && item.key !== activeKey && 'font-medium text-primary')}>
                <button type="button" onClick={toggle} className={cn('flex min-w-0 flex-1 items-center gap-3 py-[7px] text-left', collapsed ? 'justify-center' : 'pl-[14px]')} title={collapsed ? item.label : undefined} aria-expanded={item.to ? undefined : expanded}>
                  <item.icon className="size-[19px] shrink-0" strokeWidth={1.75} />
                  {!collapsed && <span className="flex-1 truncate">{item.label}</span>}
                </button>
                {!collapsed && (
                  <button type="button" onClick={() => setOpen((o) => ({ ...o, [item.label]: !expanded }))} className="grid h-full shrink-0 place-items-center px-3 py-[7px]" aria-label={`${expanded ? 'Collapse' : 'Expand'} ${item.label}`} aria-expanded={expanded}>
                    <ChevronRight className={cn('size-4 transition-transform', item.key === activeKey ? 'text-white/80' : 'text-slate-400', expanded && 'rotate-90')} />
                  </button>
                )}
              </div>
              {expanded && renderChildren(item.children, 1)}
            </div>
          )
        })}
      </nav>
    </div>
  )
}
