import { ArrowRight, CalendarDays, Mail, MessageCircle, Play } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import AppIcon from '@/components/ui/AppIcon'
import Flag from '@/components/ui/Flag'
import Image from '@/components/ui/Image'
import Modal from '@/components/ui/Modal'
import SmartLink from '@/components/ui/SmartLink'
import SocialIcon from '@/components/ui/SocialIcon'
import { assetUrl, cn, formatDate, whatsappHref, youtubeEmbedUrl, youtubeThumbnail } from '@/lib/utils'

/** Soft icon background colours, cycled by index (design detail, not content). */
const TONES = [
  'bg-fuchsia-50 text-fuchsia-600',
  'bg-blue-50 text-blue-600',
  'bg-sky-50 text-sky-600',
  'bg-orange-50 text-orange-500',
  'bg-indigo-50 text-indigo-600',
  'bg-emerald-50 text-emerald-600',
  'bg-rose-50 text-rose-500',
  'bg-violet-50 text-violet-600',
]
// eslint-disable-next-line react-refresh/only-export-components
export const toneAt = (i) => TONES[i % TONES.length]

const cardBase = 'group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-100 bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-card-hover'

export function DestinationCard({ destination }) {
  return (
    <Link to={`/study-in-${destination.slug}`} className={cardBase}>
      <Image src={destination.thumbnail || destination.hero_image} alt={destination.name} className="aspect-[4/3] w-full transition duration-500 group-hover:scale-105" />
      <div className="relative flex flex-1 items-center gap-3 p-4 pt-5">
        {/* Wrapper carries the positioning: flag-icons' .fi forces position: relative. */}
        <span className="absolute -top-3 left-4 flex">
          <Flag code={destination.country_code} className="h-5 w-7 ring-2 ring-white" title={destination.name} />
        </span>
        <div className="min-w-0 flex-1">
          <h3 className="truncate text-sm font-semibold sm:text-[15px]">{destination.name}</h3>
          {destination.tagline && <p className="mt-0.5 line-clamp-2 text-xs text-slate-500">{destination.tagline}</p>}
        </div>
        <span className="grid size-8 shrink-0 place-items-center rounded-full bg-primary-50 text-primary transition group-hover:bg-primary group-hover:text-white">
          <ArrowRight className="size-4" />
        </span>
      </div>
    </Link>
  )
}

export function ServiceTile({ service, index = 0 }) {
  return (
    <Link to={`/services/${service.slug}`} className={cn(cardBase, 'items-center px-3 py-6 text-center')} title={service.short_description}>
      <span className={cn('mb-3 grid size-14 place-items-center rounded-2xl transition group-hover:scale-110', toneAt(index))}>
        <AppIcon name={service.icon} fallback="sparkles" className="size-7" />
      </span>
      <h3 className="text-[13px] leading-snug font-semibold sm:text-sm">{service.title}</h3>
    </Link>
  )
}

export function UniversityLogoCard({ university }) {
  return (
    <Link to={`/universities/${university.slug}`} className="group flex h-full flex-col items-center justify-center rounded-xl bg-white px-4 py-5 text-center shadow-card transition hover:-translate-y-1">
      <div className="flex h-16 w-full items-center justify-center">
        {university.logo ? (
          <img src={assetUrl(university.logo)} alt={university.name} loading="lazy" className="max-h-16 w-auto max-w-full object-contain" />
        ) : (
          <span className="line-clamp-2 text-sm leading-tight font-bold text-secondary">{university.name}</span>
        )}
      </div>
      {university.destination && (
        <p className="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500">
          <Flag code={university.destination.country_code} className="h-3 w-4" /> {university.destination.name}
        </p>
      )}
    </Link>
  )
}

export function FeatureCard({ item, index = 0, href, className }) {
  const body = (
    <>
      {item.icon && (
        <span className={cn('mb-4 grid size-12 place-items-center rounded-xl', toneAt(index))}>
          <AppIcon name={item.icon} className="size-6" />
        </span>
      )}
      <h3 className="text-[15px] font-semibold">{item.title}</h3>
      {item.text && <p className="mt-1.5 text-xs leading-relaxed text-slate-500 sm:text-[13px]">{item.text}</p>}
    </>
  )
  const cls = cn('flex h-full flex-col rounded-xl border border-slate-100 bg-white p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-card-hover', className)
  return href ? <SmartLink to={href} className={cls}>{body}</SmartLink> : <div className={cls}>{body}</div>
}

export function SuccessQuoteCard({ story }) {
  const university = story.university?.name || story.university_name
  const course = story.course?.name || story.course_name
  return (
    <article className="flex h-full gap-4 rounded-xl border border-slate-100 bg-white p-5 shadow-card">
      <Image src={story.photo} alt={story.student_name || story.name} className="size-16 shrink-0 rounded-full sm:size-20" />
      <div className="flex min-w-0 flex-1 flex-col">
        <blockquote className="text-sm leading-relaxed text-slate-600 italic">“{story.quote || story.content}”</blockquote>
        <div className="mt-auto flex items-end justify-between gap-2 pt-4">
          <div className="min-w-0">
            <p className="font-semibold text-ink">{story.student_name || story.name}</p>
            {course && <p className="truncate text-xs text-slate-500">{course}</p>}
            {university && <p className="truncate text-xs text-slate-500">{university}</p>}
          </div>
          <Flag code={story.destination?.country_code || story.country_code} className="h-4 w-6" />
        </div>
      </div>
    </article>
  )
}

export function BlogCard({ post }) {
  return (
    <Link to={`/blog/${post.slug}`} className={cardBase}>
      <div className="relative">
        <Image src={post.featured_image} alt={post.title} className="aspect-[16/10] w-full transition duration-500 group-hover:scale-105" />
        {post.category && <span className="absolute top-3 left-3 rounded-md bg-white px-2.5 py-1 text-[11px] font-semibold text-primary shadow">{post.category.name}</span>}
      </div>
      <div className="flex flex-1 flex-col p-4">
        {post.published_at && (
          <p className="flex items-center gap-1.5 text-xs text-slate-500"><CalendarDays className="size-3.5 text-primary" /> {formatDate(post.published_at)}</p>
        )}
        <h3 className="mt-2 line-clamp-2 text-[15px] leading-snug font-semibold">{post.title}</h3>
        {post.excerpt && <p className="mt-2 line-clamp-2 text-xs leading-relaxed text-slate-500">{post.excerpt}</p>}
        <span className="mt-auto inline-flex items-center gap-1 pt-3 text-xs font-semibold text-primary">
          Read More <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
        </span>
      </div>
    </Link>
  )
}

export function PhotoCard({ photo, onOpen }) {
  return (
    <button type="button" onClick={onOpen} className="group relative block aspect-[4/3] w-full overflow-hidden rounded-xl bg-slate-100" title={photo.title} aria-label={photo.title ? `View photo: ${photo.title}` : 'View photo'}>
      <Image src={photo.image} alt={photo.title || ''} className="size-full transition duration-500 group-hover:scale-110" />
      {photo.title && (
        <span className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 text-left text-xs font-medium text-white opacity-0 transition group-hover:opacity-100">{photo.title}</span>
      )}
    </button>
  )
}

/** YouTube video card; plays in a modal (privacy-enhanced embed, loaded only on click). */
export function VideoCard({ video, className }) {
  const [open, setOpen] = useState(false)
  const thumb = video.thumbnail || youtubeThumbnail(video.youtube_id)

  return (
    <>
      <button type="button" onClick={() => setOpen(true)} className={cn('group block w-full text-left', className)} aria-label={`Play ${video.title}`}>
        <div className="relative aspect-video overflow-hidden rounded-xl bg-slate-900">
          <Image src={thumb} alt={video.title} className="size-full opacity-90 transition duration-500 group-hover:scale-105" />
          <span className="absolute inset-0 grid place-items-center">
            <span className="grid size-14 place-items-center rounded-full bg-white/95 text-primary shadow-lg transition group-hover:scale-110">
              <Play className="ml-0.5 size-6 fill-current" />
            </span>
          </span>
        </div>
        <p className="mt-2 line-clamp-1 text-sm font-semibold text-ink">{video.title}</p>
        {video.category && <p className="text-xs text-slate-500">{video.category.name}</p>}
      </button>
      <VideoModal open={open} onClose={() => setOpen(false)} youtubeId={video.youtube_id} title={video.title} />
    </>
  )
}

export function VideoModal({ open, onClose, youtubeId, title }) {
  if (!youtubeId) return null
  return (
    <Modal open={open} onClose={onClose} title={title} size="xl" className="bg-black" >
      <div className="-mx-6 -my-5 aspect-video bg-black">
        {open && <iframe src={youtubeEmbedUrl(youtubeId)} title={title || 'Video'} allow="autoplay; encrypted-media; picture-in-picture" allowFullScreen className="size-full" />}
      </div>
    </Modal>
  )
}

export function TeamCard({ member }) {
  const links = member.social_links || {}
  const iconCls = 'grid size-7 place-items-center rounded-md bg-primary-50 text-primary transition hover:bg-primary hover:text-white'
  return (
    <div className="flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-3 shadow-card">
      {member.photo ? (
        <Image src={member.photo} alt={member.name} className="size-20 shrink-0 rounded-lg sm:size-24" />
      ) : (
        <span className="grid size-20 shrink-0 place-items-center rounded-lg bg-primary-50 text-2xl font-bold text-primary sm:size-24" aria-hidden="true">
          {member.name.split(' ').map((w) => w[0]).slice(0, 2).join('')}
        </span>
      )}
      <div className="min-w-0">
        <h3 className="truncate text-[15px] font-semibold">{member.name}</h3>
        {member.designation && <p className="text-xs text-slate-500">{member.designation}</p>}
        <div className="mt-3 flex gap-1.5">
          {links.linkedin && <a href={links.linkedin} target="_blank" rel="noopener noreferrer" className={iconCls} aria-label="LinkedIn"><SocialIcon name="linkedin" className="size-3.5" /></a>}
          {links.whatsapp && <a href={whatsappHref(links.whatsapp)} target="_blank" rel="noopener noreferrer" className={iconCls} aria-label="WhatsApp"><MessageCircle className="size-3.5" /></a>}
          {member.email && <a href={`mailto:${member.email}`} className={iconCls} aria-label="Email"><Mail className="size-3.5" /></a>}
        </div>
      </div>
    </div>
  )
}
