import { ArrowRight, CalendarDays, Check, Copy, Images, Link2, Mail } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import AppIcon from '@/components/ui/AppIcon'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import HighlightText from '@/components/ui/HighlightText'
import Image from '@/components/ui/Image'
import SocialIcon from '@/components/ui/SocialIcon'
import { useToast } from '@/components/ui/Toast'
import { assetUrl, cn, formatDate } from '@/lib/utils'
import { publicService } from '@/services/publicService'

/**
 * Category tabs with icons and counts, overlapping the hero (blog / gallery).
 * options: [{ value, label, icon, count }]; value '' = all.
 */
export function CategoryTabs({ options = [], allLabel, allIcon = 'layout-grid', allCount, value, onChange }) {
  const tabs = [{ value: '', label: allLabel, icon: allIcon, count: allCount }, ...options]
  return (
    <div className="container-page relative z-10 -mt-8">
      <div className="flex gap-1 overflow-x-auto rounded-2xl border border-slate-100 bg-white p-2 shadow-card-hover" role="tablist">
        {tabs.map((t) => {
          const active = (value ?? '') === t.value
          return (
            <button
              key={t.value || 'all'}
              type="button"
              role="tab"
              aria-selected={active}
              onClick={() => onChange(t.value)}
              className={cn(
                'flex min-w-28 shrink-0 flex-1 flex-col items-center gap-1.5 rounded-xl px-3 py-3 text-xs font-medium transition',
                active ? 'bg-primary text-white shadow-glow' : 'text-slate-600 hover:bg-primary-50 hover:text-primary',
              )}
            >
              <AppIcon name={t.icon} fallback="folder" className={cn('size-6', !active && 'text-primary')} />
              <span className="whitespace-nowrap">
                {t.label}
                {t.count !== undefined && t.count !== null && ` (${t.count})`}
              </span>
            </button>
          )
        })}
      </div>
    </div>
  )
}

function NewsletterInline({ buttonText }) {
  const toast = useToast()
  const [email, setEmail] = useState('')
  const [busy, setBusy] = useState(false)
  const submit = async (e) => {
    e.preventDefault()
    setBusy(true)
    try {
      const res = await publicService.subscribe({ email })
      toast.success(res.message)
      setEmail('')
    } catch (err) {
      toast.error(err.errors?.email?.[0] || err.message)
    } finally {
      setBusy(false)
    }
  }
  return (
    <form onSubmit={submit} className="mt-4 space-y-2.5">
      <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)} placeholder="Enter your email address" aria-label="Email address"
        className="h-11 w-full rounded-lg border-0 bg-white px-3 text-sm text-slate-700 focus:ring-4 focus:ring-white/40 focus:outline-none" />
      <Button type="submit" variant="outline-light" className="w-full" loading={busy}>{buttonText || 'Subscribe'} <ArrowRight className="size-4" /></Button>
    </form>
  )
}

/** CMS "sidebar promo card" block: text + button, image card, or newsletter sign-up. */
export function SidebarPromoCard({ block }) {
  const d = block.data ?? {}
  if (d.kind === 'list') {
    return (
      <section className="rounded-2xl border border-slate-100 bg-lavender p-5">
        {block.title && <h3 className="mb-4 text-base font-semibold">{block.title}</h3>}
        {block.subtitle && <p className="-mt-2 mb-4 text-xs text-slate-500">{block.subtitle}</p>}
        <ul className="space-y-4">
          {(d.items ?? []).map((item, i) => (
            <li key={i} className="flex gap-3">
              <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-primary shadow-sm"><AppIcon name={item.icon} fallback="check-circle-2" className="size-4.5" /></span>
              <div className="min-w-0">
                <p className="text-sm font-semibold text-ink">{item.title}</p>
                {item.text && <p className="mt-0.5 text-xs leading-relaxed text-slate-500">{item.text}</p>}
              </div>
            </li>
          ))}
        </ul>
      </section>
    )
  }
  if (d.image && d.kind !== 'newsletter') {
    return (
      <div className="relative isolate overflow-hidden rounded-2xl bg-secondary p-5 text-white shadow-card">
        <img src={assetUrl(d.image)} alt="" loading="lazy" className="absolute inset-0 -z-20 size-full object-cover" />
        <div className="absolute inset-0 -z-10 bg-gradient-to-r from-primary via-primary/85 to-primary/10" />
        <div className="max-w-[70%]">
          {block.title && <h3 className="text-xl leading-snug text-white"><HighlightText text={block.title} highlightClassName="text-primary-200" /></h3>}
          {block.subtitle && <p className="mt-2 text-xs leading-relaxed text-white/85">{block.subtitle}</p>}
        </div>
        {d.button_text && <Button to={d.button_url} variant="white" size="sm" className="mt-5">{d.button_text} <ArrowRight className="size-3.5" /></Button>}
      </div>
    )
  }
  return (
    <div className="rounded-2xl bg-primary p-5 text-white shadow-glow">
      <div className="flex items-start justify-between gap-3">
        {block.title && <h3 className="text-lg leading-snug text-white">{block.title}</h3>}
        {d.icon && <AppIcon name={d.icon} className="size-8 shrink-0 text-white/90" />}
      </div>
      {block.subtitle && <p className="mt-2 text-xs leading-relaxed text-white/80">{block.subtitle}</p>}
      {d.kind === 'newsletter' ? (
        <NewsletterInline buttonText={d.button_text} />
      ) : (
        d.button_text && <Button to={d.button_url} variant="white" size="sm" className="mt-4">{d.button_text} <ArrowRight className="size-3.5" /></Button>
      )}
    </div>
  )
}

/** Statistics block styled "sidebar" ("Success by Numbers"). */
export function SidebarStats({ block }) {
  const stats = block.statistics ?? []
  if (!stats.length) return null
  return (
    <section className="rounded-2xl border border-slate-100 bg-lavender p-5">
      {block.title && <h3 className="mb-4 text-base font-semibold">{block.title}</h3>}
      <ul className="space-y-4">
        {stats.map((s) => (
          <li key={s.id} className="flex items-center gap-3">
            <span className="grid size-11 shrink-0 place-items-center rounded-full bg-white text-primary shadow-sm"><AppIcon name={s.icon} className="size-5" /></span>
            <span>
              <span className="block text-lg font-bold text-ink">{s.value}{s.suffix}</span>
              <span className="block text-xs text-slate-500">{s.label}</span>
            </span>
          </li>
        ))}
      </ul>
    </section>
  )
}

/** Render a list page's sidebar blocks in order. */
export function SidebarBlocks({ blocks = [] }) {
  return blocks.map((b) => (b.type === 'sidebar_card' ? <SidebarPromoCard key={b.id} block={b} /> : <SidebarStats key={b.id} block={b} />))
}

/** Category list with counts (sidebar). */
export function CategoryList({ title, options = [], allLabel, allCount, value, onSelect, hrefFor }) {
  const items = [{ value: '', label: allLabel, count: allCount, icon: 'layout-grid' }, ...options]
  const cls = (active) => cn('flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition', active ? 'bg-white font-semibold text-primary shadow-sm' : 'text-slate-600 hover:bg-white hover:text-primary')
  return (
    <section className="rounded-2xl border border-slate-100 bg-lavender p-5">
      <h3 className="mb-3 text-base font-semibold">{title}</h3>
      <ul className="space-y-1">
        {items.map((o) => {
          const active = (value ?? '') === o.value
          const body = (
            <>
              <AppIcon name={o.icon} fallback="folder" className="size-4.5 shrink-0 text-primary" />
              <span className="flex-1">{o.label}</span>
              {o.count !== undefined && <span className="text-xs text-slate-400">{o.count}</span>}
            </>
          )
          return (
            <li key={o.value || 'all'}>
              {hrefFor ? <Link to={hrefFor(o.value)} className={cls(active)}>{body}</Link> : <button type="button" onClick={() => onSelect(o.value)} className={cls(active)}>{body}</button>}
            </li>
          )
        })}
      </ul>
    </section>
  )
}

export function PopularPosts({ title, posts = [] }) {
  if (!posts.length) return null
  return (
    <section className="rounded-2xl border border-slate-100 bg-lavender p-5">
      <h3 className="mb-4 text-base font-semibold">{title}</h3>
      <ul className="space-y-4">
        {posts.map((p) => (
          <li key={p.id}>
            <Link to={`/blog/${p.slug}`} className="group flex gap-3">
              <Image src={p.featured_image} alt="" className="size-16 shrink-0 rounded-lg" />
              <span className="min-w-0">
                <span className="line-clamp-2 text-sm font-medium text-ink group-hover:text-primary">{p.title}</span>
                <span className="mt-1 flex items-center gap-1 text-xs text-slate-500"><CalendarDays className="size-3.5 text-primary" /> {formatDate(p.published_at)}</span>
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </section>
  )
}

/** Share links (no third-party scripts) + copy link. */
export function ShareButtons({ title, url = window.location.href }) {
  const [copied, setCopied] = useState(false)
  const u = encodeURIComponent(url)
  const t = encodeURIComponent(title)
  const links = [
    ['facebook', `https://www.facebook.com/sharer/sharer.php?u=${u}`, 'Share on Facebook'],
    ['x', `https://twitter.com/intent/tweet?url=${u}&text=${t}`, 'Share on X'],
    ['linkedin', `https://www.linkedin.com/sharing/share-offsite/?url=${u}`, 'Share on LinkedIn'],
  ]
  const btn = 'grid size-10 place-items-center rounded-full bg-primary-50 text-primary transition hover:bg-primary hover:text-white'
  return (
    <div className="flex flex-wrap items-center gap-2">
      <span className="mr-1 text-sm font-semibold text-ink">Share:</span>
      {links.map(([icon, href, label]) => (
        <a key={icon} href={href} target="_blank" rel="noopener noreferrer" className={btn} aria-label={label} title={label}><SocialIcon name={icon} /></a>
      ))}
      <a href={`https://wa.me/?text=${t}%20${u}`} target="_blank" rel="noopener noreferrer" className={btn} aria-label="Share on WhatsApp" title="Share on WhatsApp"><Link2 className="size-4" /></a>
      <a href={`mailto:?subject=${t}&body=${u}`} className={btn} aria-label="Share by email" title="Share by email"><Mail className="size-4" /></a>
      <button
        type="button"
        className={btn}
        aria-label="Copy link"
        title="Copy link"
        onClick={async () => {
          try {
            await navigator.clipboard.writeText(url)
            setCopied(true)
            setTimeout(() => setCopied(false), 2000)
          } catch {
            /* clipboard unavailable */
          }
        }}
      >
        {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
      </button>
    </div>
  )
}

export function StoryCard({ story }) {
  const university = story.university?.name || story.university_name
  return (
    <Link to={`/success-stories/${story.slug}`} className="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-100 bg-white shadow-card transition hover:-translate-y-1 hover:shadow-card-hover">
      <div className="relative">
        <Image src={story.photo} alt={story.student_name} className="aspect-[4/3] w-full object-top transition duration-500 group-hover:scale-105" />
        {story.destination && (
          <span className="absolute bottom-3 left-3 flex items-center gap-1.5 rounded-md bg-white px-2 py-1 text-xs font-medium text-ink shadow">
            <Flag code={story.destination.country_code} className="h-3 w-4" /> {story.destination.name}
          </span>
        )}
      </div>
      <div className="flex flex-1 flex-col p-4">
        <h3 className="text-[15px] font-semibold">{story.student_name}</h3>
        {story.course_name && <p className="text-xs text-slate-600">{story.course_name}</p>}
        {university && <p className="text-xs text-slate-500">{university}</p>}
        {story.quote && <p className="mt-3 line-clamp-4 text-sm leading-relaxed text-slate-600 italic">“{story.quote}”</p>}
        <span className="mt-auto inline-flex items-center gap-1 pt-4 text-xs font-semibold text-primary">Read Full Story <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" /></span>
      </div>
    </Link>
  )
}

export function AlbumCard({ album, onOpen }) {
  return (
    <button type="button" onClick={onOpen} className="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-100 bg-white text-left shadow-card transition hover:-translate-y-1 hover:shadow-card-hover">
      <div className="relative">
        <Image src={album.cover_image} alt="" className="aspect-[4/3] w-full transition duration-500 group-hover:scale-105" />
        <span className="absolute right-2.5 bottom-2.5 flex items-center gap-1 rounded-md bg-black/60 px-2 py-0.5 text-[11px] font-medium text-white">
          <Images className="size-3.5" /> {album.images_count}
        </span>
      </div>
      <div className="p-3.5">
        <h3 className="line-clamp-1 text-sm font-semibold">{album.title}</h3>
        {album.category && <span className="mt-1.5 inline-block rounded-md bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary">{album.category.name}</span>}
      </div>
    </button>
  )
}
