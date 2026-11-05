import { ArrowRight, ChevronDown, Menu, Phone, Search, X } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { NavLink, useLocation, useNavigate } from 'react-router-dom'
import Button from '@/components/ui/Button'
import SmartLink from '@/components/ui/SmartLink'
import { useSite } from '@/context/SiteContext'
import { cn, telHref } from '@/lib/utils'
import SiteLogo from './SiteLogo'
import SocialLinks from './SocialLinks'

function isActive(item, pathname) {
  if (item.url === '/') return pathname === '/'
  return (item.url !== '#' && pathname.startsWith(item.url)) || item.children?.some((c) => isActive(c, pathname))
}

const linkBase = 'relative flex items-center gap-1 px-2.5 py-2 text-[13px] font-medium whitespace-nowrap transition-colors 2xl:px-3 2xl:text-sm'
const underline = 'after:absolute after:inset-x-2.5 after:-bottom-0.5 after:h-0.5 after:rounded-full after:bg-primary'

function DesktopItem({ item, pathname }) {
  const active = isActive(item, pathname)
  const cls = cn(linkBase, active ? cn('text-primary', underline) : 'text-slate-700 hover:text-primary')

  if (!item.children?.length) {
    return item.is_external ? (
      <SmartLink to={item.url} target={item.target} className={cls}>{item.label}</SmartLink>
    ) : (
      <NavLink to={item.url} end={item.url === '/'} className={cls}>{item.label}</NavLink>
    )
  }

  return (
    <div className="group relative">
      <SmartLink to={item.url} className={cls} aria-haspopup="true">
        {item.label}
        <ChevronDown className="size-3.5 transition-transform group-focus-within:rotate-180 group-hover:rotate-180" />
      </SmartLink>
      <div className="invisible absolute top-full left-0 z-50 min-w-56 translate-y-2 pt-3 opacity-0 transition-all group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
        <div className="rounded-xl border border-slate-100 bg-white p-2 shadow-card-hover">
          {item.children.map((child) => (
            <SmartLink
              key={child.id}
              to={child.url}
              target={child.target}
              className={cn(
                'block rounded-lg px-4 py-2.5 text-sm font-medium transition-colors',
                isActive(child, pathname) ? 'bg-primary-50 text-primary' : 'text-slate-600 hover:bg-primary-50 hover:text-primary',
              )}
            >
              {child.label}
            </SmartLink>
          ))}
        </div>
      </div>
    </div>
  )
}

function MobileItem({ item, pathname }) {
  const [open, setOpen] = useState(() => isActive(item, pathname))
  const active = isActive(item, pathname)
  const cls = cn('flex w-full items-center justify-between rounded-lg px-4 py-3 text-base font-medium', active ? 'bg-primary-50 text-primary' : 'text-slate-700')

  if (!item.children?.length) {
    return <SmartLink to={item.url} target={item.target} className={cls}>{item.label}</SmartLink>
  }

  return (
    <div>
      <button type="button" className={cls} onClick={() => setOpen((o) => !o)} aria-expanded={open}>
        {item.label}
        <ChevronDown className={cn('size-5 transition-transform', open && 'rotate-180')} />
      </button>
      {open && (
        <div className="mt-1 ml-4 space-y-1 border-l-2 border-primary-100 pl-3">
          {item.children.map((child) => (
            <SmartLink key={child.id} to={child.url} target={child.target} className={cn('block rounded-lg px-3 py-2 text-sm', isActive(child, pathname) ? 'font-semibold text-primary' : 'text-slate-600')}>
              {child.label}
            </SmartLink>
          ))}
        </div>
      )}
    </div>
  )
}

function SearchOverlay({ open, onClose }) {
  const navigate = useNavigate()
  const inputRef = useRef(null)
  const [term, setTerm] = useState('')

  useEffect(() => {
    if (!open) return undefined
    inputRef.current?.focus()
    const onKey = (e) => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-[70] bg-slate-900/60 backdrop-blur-sm" onClick={onClose}>
      <form
        role="search"
        onClick={(e) => e.stopPropagation()}
        onSubmit={(e) => {
          e.preventDefault()
          if (!term.trim()) return
          navigate(`/search?q=${encodeURIComponent(term.trim())}`)
          setTerm('')
          onClose()
        }}
        className="animate-fade-up mx-auto mt-24 flex w-[calc(100%-2rem)] max-w-2xl items-center gap-2 rounded-2xl bg-white p-2 shadow-2xl"
      >
        <Search className="ml-3 size-5 shrink-0 text-slate-400" />
        <input
          ref={inputRef}
          value={term}
          onChange={(e) => setTerm(e.target.value)}
          type="search"
          aria-label="Search"
          placeholder="Search destinations, universities, courses…"
          className="h-12 min-w-0 flex-1 bg-transparent text-base text-slate-800 placeholder:text-slate-400 focus:outline-none"
        />
        <Button type="submit" aria-label="Search">
          <ArrowRight className="size-4" />
        </Button>
      </form>
    </div>
  )
}

export default function Navbar() {
  const { menu, setting } = useSite()
  const { pathname } = useLocation()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [searchOpen, setSearchOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const [lastPath, setLastPath] = useState(pathname)
  const items = menu('header')
  const ctaText = setting('header.header_cta_text')
  const ctaUrl = setting('header.header_cta_url', '/book-appointment')
  const phone = setting('contact.contact_phone')
  const searchEnabled = setting('header.header_search_enabled', false)

  // Close the drawer on navigation (state derived during render, no effect needed).
  if (lastPath !== pathname) {
    setLastPath(pathname)
    setMobileOpen(false)
  }

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [mobileOpen])

  return (
    <>
      <header className={cn('sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur transition-shadow', scrolled && 'shadow-[0_6px_24px_-12px_rgb(17_12_46/0.18)]')}>
        {/* Wider than .container-page: the menu is admin-defined and can hold many items. */}
        <div className="mx-auto flex h-16 w-full max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:h-[76px] lg:px-8">
          <SiteLogo nameClassName={setting('general.site_logo') ? 'xl:max-[1399px]:hidden' : undefined} />

          <nav className="hidden items-center xl:flex" aria-label="Main">
            {items.map((item) => (
              <DesktopItem key={item.id} item={item} pathname={pathname} />
            ))}
          </nav>

          <div className="flex items-center gap-1.5 sm:gap-3">
            {searchEnabled && (
              <button type="button" onClick={() => setSearchOpen(true)} className="grid size-10 place-items-center rounded-full text-primary hover:bg-primary-50" aria-label="Search">
                <Search className="size-5" />
              </button>
            )}
            {ctaText && (
              <Button to={ctaUrl} size="sm" className="h-10 px-4 max-sm:hidden">
                {ctaText} <ArrowRight className="size-4" />
              </Button>
            )}
            <button
              type="button"
              className="grid size-10 place-items-center rounded-full text-slate-700 hover:bg-slate-100 xl:hidden"
              onClick={() => setMobileOpen(true)}
              aria-label="Open menu"
              aria-expanded={mobileOpen}
            >
              <Menu className="size-6" />
            </button>
          </div>
        </div>
      </header>

      <SearchOverlay open={searchOpen} onClose={() => setSearchOpen(false)} />

      {/* Mobile drawer: outside <header>, whose backdrop-filter would otherwise trap position:fixed. */}
      <div className={cn('fixed inset-0 z-[60] overflow-hidden xl:hidden', mobileOpen ? 'visible' : 'invisible')} aria-hidden={!mobileOpen}>
        <div className={cn('absolute inset-0 bg-slate-900/50 transition-opacity', mobileOpen ? 'opacity-100' : 'opacity-0')} onClick={() => setMobileOpen(false)} />
        <div className={cn('absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl transition-transform duration-300', mobileOpen ? 'translate-x-0' : 'translate-x-full')}>
          <div className="flex h-16 items-center justify-between border-b border-slate-100 px-4">
            <SiteLogo />
            <button type="button" onClick={() => setMobileOpen(false)} className="grid size-11 place-items-center rounded-full hover:bg-slate-100" aria-label="Close menu">
              <X className="size-6" />
            </button>
          </div>
          <nav className="flex-1 space-y-1 overflow-y-auto p-4" aria-label="Mobile">
            {items.map((item) => (
              <MobileItem key={item.id} item={item} pathname={pathname} />
            ))}
          </nav>
          <div className="space-y-3 border-t border-slate-100 p-4">
            {ctaText && (
              <Button to={ctaUrl} className="w-full">
                {ctaText} <ArrowRight className="size-4" />
              </Button>
            )}
            {phone && (
              <Button to={telHref(phone)} variant="outline" className="w-full">
                <Phone className="size-4" /> {phone}
              </Button>
            )}
            <SocialLinks className="justify-center pt-1" itemClassName="size-10 bg-primary-50 text-primary hover:bg-primary hover:text-white" />
          </div>
        </div>
      </div>
    </>
  )
}
