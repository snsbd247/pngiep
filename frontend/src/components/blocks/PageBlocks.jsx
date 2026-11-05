import { ArrowRight, CheckCircle2, MapPin, Play } from 'lucide-react'
import { useState } from 'react'
import { TeamCard, VideoCard, VideoModal, toneAt } from '@/components/cards'
import { CourseCard, UniversityCard } from '@/components/directory/Cards'
import CTASection from '@/components/sections/CTASection'
import Carousel from '@/components/sections/Carousel'
import FAQAccordion from '@/components/sections/FAQAccordion'
import PartnerLogoSlider from '@/components/sections/PartnerLogoSlider'
import SectionHeader from '@/components/sections/SectionHeader'
import StatsBar from '@/components/sections/StatsBar'
import AppIcon from '@/components/ui/AppIcon'
import Button from '@/components/ui/Button'
import HighlightText from '@/components/ui/HighlightText'
import Image from '@/components/ui/Image'
import SmartLink from '@/components/ui/SmartLink'
import { studyLevelLabel } from '@/config/studyLevels'
import { useSite } from '@/context/SiteContext'
import { cn, youtubeId } from '@/lib/utils'
import { FeatureGrid, PhotoGrid, QuoteCarousel, SplitChecklist } from './shared'

/**
 * Layout shell for a block. Full pages: padded section + container.
 * `compact` (inside a detail page's main column): no container, tighter spacing.
 */
function Shell({ compact, id, className, tone, children }) {
  if (compact) {
    return (
      <section id={id} className={cn('scroll-mt-28 py-6', tone && 'my-4 rounded-2xl bg-lavender px-5 sm:px-6', className)}>
        {children}
      </section>
    )
  }
  return (
    <section id={id} className={cn('section scroll-mt-24', tone && 'bg-lavender', className)}>
      <div className="container-page">{children}</div>
    </section>
  )
}

const head = (b, extra = {}) => ({ eyebrow: b.eyebrow, title: b.title, subtitle: b.subtitle, ...extra })
const link = (b) => ({ linkText: b.data?.link_text, linkUrl: b.data?.link_url })

/** Server-purified rich text. */
function Html({ html, className }) {
  if (!html) return null
  return <div className={cn('prose-content', className)} dangerouslySetInnerHTML={{ __html: html }} />
}

function RichText({ block, compact }) {
  return (
    <Shell compact={compact} id={block.anchor}>
      <div className={cn(!compact && block.data?.width !== 'half' && 'max-w-4xl')}>
        <SectionHeader {...head(block)} className="!mb-4" />
        <Html html={block.data?.body} />
      </div>
    </Shell>
  )
}

function TextImages({ block, compact }) {
  const images = block.data?.images ?? []
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block)} className="!mb-4" />
      <Html html={block.data?.body} className="text-sm sm:text-[15px]" />
      {images.length > 0 && (
        <div className={cn('mt-5 grid gap-3', images.length === 2 ? 'grid-cols-2' : images.length >= 3 ? 'grid-cols-2 sm:grid-cols-3' : '')}>
          {images.slice(0, 4).map((src, i) => (
            <Image key={i} src={src} alt="" className={cn('aspect-[4/3] w-full rounded-xl', i === 3 && 'sm:hidden')} />
          ))}
        </div>
      )}
    </Shell>
  )
}

function Intro({ block, compact }) {
  const d = block.data ?? {}
  const images = d.images ?? []
  const [videoOpen, setVideoOpen] = useState(false)
  const videoId = youtubeId(d.video_url)

  return (
    <Shell compact={compact} id={block.anchor}>
      <div className={cn('grid items-center gap-10 lg:gap-14', !compact && 'lg:grid-cols-2')}>
        <div>
          {block.eyebrow && <p className="mb-2 text-xs font-bold tracking-[0.16em] text-primary uppercase">{block.eyebrow}</p>}
          {block.title && (
            <h2 className="text-2xl leading-tight sm:text-3xl lg:text-[2.1rem]">
              <HighlightText text={block.title} />
            </h2>
          )}
          <Html html={d.body} className="mt-4 text-sm sm:text-[15px]" />
          <div className="mt-6 flex flex-wrap items-center gap-6">
            {d.button_text && (
              <Button to={d.button_url}>
                {d.button_text} <ArrowRight className="size-4" />
              </Button>
            )}
            {videoId && (
              <button type="button" onClick={() => setVideoOpen(true)} className="flex items-center gap-3 text-left">
                <span className="grid size-12 place-items-center rounded-full border-4 border-primary-100 bg-primary text-white">
                  <Play className="ml-0.5 size-5 fill-current" />
                </span>
                <span>
                  {d.video_label && <span className="block text-sm font-semibold text-ink">{d.video_label}</span>}
                  {d.video_duration && <span className="block text-xs text-slate-500">{d.video_duration}</span>}
                </span>
              </button>
            )}
          </div>
          {videoId && <VideoModal open={videoOpen} onClose={() => setVideoOpen(false)} youtubeId={videoId} title={d.video_label} />}
        </div>
        {images.length > 0 && (
          <div className={cn('grid gap-4', images.length > 1 && 'grid-cols-[1.15fr_1fr]')}>
            <Image src={images[0]} alt="" className={cn('w-full rounded-2xl shadow-card', images.length > 1 ? 'h-full min-h-72' : 'aspect-[4/3]')} />
            {images.length > 1 && (
              <div className="grid gap-4">
                {images.slice(1, 3).map((src, i) => (
                  <Image key={i} src={src} alt="" className="aspect-[4/3] w-full rounded-2xl shadow-card" />
                ))}
              </div>
            )}
          </div>
        )}
      </div>
    </Shell>
  )
}

function Statistics({ block, compact }) {
  if (block.data?.style === 'overlap') return <StatsBar items={block.statistics} overlap />
  if (compact) return <div className="py-4"><StatsBar items={block.statistics} className="!px-0" /></div>
  return (
    <div className="pb-10">
      <StatsBar items={block.statistics} />
    </div>
  )
}

function FeatureCards({ block, compact }) {
  const d = block.data ?? {}
  const items = d.items ?? []

  if (d.style === 'bar') {
    // Overlapping highlights bar directly under a hero banner.
    return (
      <div className="container-page relative z-10 -mt-8 mb-4 md:-mt-10">
        <div className="grid gap-4 rounded-2xl border border-primary-100 bg-white p-5 shadow-card-hover sm:grid-cols-2 lg:grid-cols-4 lg:divide-x lg:divide-slate-100">
          {items.map((item, i) => (
            <div key={i} className="flex items-center gap-3 lg:px-4">
              <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary"><AppIcon name={item.icon} className="size-5" /></span>
              <div className="min-w-0">
                <p className="text-sm font-semibold text-ink">{item.title}</p>
                {item.text && <p className="text-xs text-slate-500">{item.text}</p>}
              </div>
            </div>
          ))}
        </div>
      </div>
    )
  }

  if (d.style === 'large') {
    return (
      <Shell compact={compact} id={block.anchor} className={!compact && 'pt-0'}>
        <SectionHeader {...head(block)} />
        <div className="grid gap-5 md:grid-cols-2">
          {items.map((item, i) => (
            <div key={i} className="relative flex gap-5 overflow-hidden rounded-2xl border border-primary-100 bg-lavender-200 p-6 sm:p-8">
              <AppIcon name={item.icon} className={cn('size-10 shrink-0', i % 2 ? 'text-blue-600' : 'text-primary')} />
              <div>
                <h3 className="text-xl">{item.title}</h3>
                {item.text && <p className="mt-2 max-w-md text-sm leading-relaxed text-slate-600">{item.text}</p>}
              </div>
            </div>
          ))}
        </div>
      </Shell>
    )
  }

  if (d.style === 'stats') {
    return (
      <Shell compact={compact} id={block.anchor}>
        <SectionHeader {...head(block)} />
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          {items.map((item, i) => (
            <div key={i} className="flex items-center gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-card">
              <span className={cn('grid size-11 shrink-0 place-items-center rounded-xl', toneAt(i + 3))}><AppIcon name={item.icon} className="size-5" /></span>
              <div className="min-w-0">
                <p className="text-xl font-bold text-ink">{item.title}</p>
                {item.text && <p className="text-[11px] leading-tight text-slate-500">{item.text}</p>}
              </div>
            </div>
          ))}
        </div>
      </Shell>
    )
  }

  if (d.style === 'steps') {
    return (
      <Shell compact={compact} id={block.anchor} tone>
        <div className={cn('grid gap-8', !compact && 'lg:grid-cols-[260px_1fr] lg:items-center')}>
          <SectionHeader {...head(block)} className="!mb-0" />
          <ol className="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
            {items.map((item, i) => (
              <li key={i} className="relative text-center">
                <span className="mx-auto grid size-16 place-items-center rounded-full border border-primary-100 bg-white text-primary shadow-card">
                  <AppIcon name={item.icon} className="size-7" />
                </span>
                {i < items.length - 1 && <ArrowRight className="absolute top-6 -right-4 hidden size-4 text-primary-300 lg:block" aria-hidden="true" />}
                <p className="mt-3 text-xs font-bold text-primary">{String(i + 1).padStart(2, '0')}</p>
                <h3 className="mt-1 text-sm font-semibold">{item.title}</h3>
                {item.text && <p className="mt-1 text-xs text-slate-500">{item.text}</p>}
              </li>
            ))}
          </ol>
        </div>
      </Shell>
    )
  }

  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block)} />
      <FeatureGrid items={items} columns={items.length % 4 === 0 ? 4 : 3} />
    </Shell>
  )
}

function FeaturesWithImage({ block, compact }) {
  const d = block.data ?? {}
  return (
    <Shell compact={compact} id={block.anchor}>
      <div className={cn('grid items-center gap-10', !compact && 'lg:grid-cols-[1fr_360px] xl:grid-cols-[1fr_420px]')}>
        <div>
          <SectionHeader {...head(block)} />
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {(d.items ?? []).map((item, i) => (
              <div key={i} className="flex gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-card">
                <span className={cn('grid size-11 shrink-0 place-items-center rounded-xl', toneAt(i))}>
                  <AppIcon name={item.icon} className="size-5" />
                </span>
                <div>
                  <h3 className="text-sm font-semibold">{item.title}</h3>
                  {item.text && <p className="mt-1 text-xs leading-relaxed text-slate-500">{item.text}</p>}
                </div>
              </div>
            ))}
          </div>
        </div>
        {d.image && !compact && (
          <div className="relative overflow-hidden rounded-3xl">
            <Image src={d.image} alt="" className="aspect-[4/5] w-full" />
            {d.image_caption && (
              <p className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-secondary/90 to-transparent px-6 pt-16 pb-6 text-right text-2xl leading-snug font-semibold whitespace-pre-line text-white italic">
                {d.image_caption}
              </p>
            )}
          </div>
        )}
      </div>
    </Shell>
  )
}

function Checklist({ block }) {
  return <SplitChecklist title={block.title} subtitle={block.subtitle} image={block.data?.image} items={block.data?.items ?? []} />
}

function List({ block, compact }) {
  const items = block.data?.items ?? []
  if (!items.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block)} className="!mb-4" />
      <ul className="space-y-2.5">
        {items.map((item, i) => (
          <li key={i} className="flex items-start gap-2.5 text-sm text-slate-700">
            <CheckCircle2 className="mt-0.5 size-4.5 shrink-0 text-primary" />
            {item.text}
          </li>
        ))}
      </ul>
    </Shell>
  )
}

function Table({ block, compact }) {
  const d = block.data ?? {}
  const rows = d.rows ?? []
  if (!rows.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block)} className="!mb-4" />
      <div className="overflow-hidden rounded-xl border border-slate-200">
        <table className="w-full text-left text-sm">
          {(d.column_1 || d.column_2) && (
            <thead className="bg-lavender-200 text-xs font-semibold text-ink">
              <tr>
                <th scope="col" className="px-4 py-3">{d.column_1}</th>
                <th scope="col" className="px-4 py-3">{d.column_2}</th>
              </tr>
            </thead>
          )}
          <tbody className="divide-y divide-slate-100">
            {rows.map((row, i) => (
              <tr key={i} className="bg-white">
                <td className="px-4 py-2.5 text-slate-700">{row.label}</td>
                <td className="px-4 py-2.5 font-medium text-ink">{row.value}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Shell>
  )
}

function Gallery({ block, compact }) {
  const images = (block.data?.images ?? []).map((src, i) => ({ id: i, image: src }))
  if (!images.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block, link(block))} />
      <PhotoGrid photos={images} />
    </Shell>
  )
}

function RelatedUniversities({ block, compact }) {
  const items = block.universities ?? []
  if (!items.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block, link(block))} />
      <Carousel
        items={items}
        breakpoints={compact
          ? { 0: { slidesPerView: 1.2, spaceBetween: 12 }, 640: { slidesPerView: 2, spaceBetween: 16 }, 1024: { slidesPerView: 3, spaceBetween: 16 } }
          : { 0: { slidesPerView: 1.2, spaceBetween: 12 }, 640: { slidesPerView: 2, spaceBetween: 16 }, 1024: { slidesPerView: 3, spaceBetween: 20 }, 1280: { slidesPerView: 4, spaceBetween: 20 } }}
        renderItem={(u) => <UniversityCard university={u} />}
      />
    </Shell>
  )
}

function RelatedCourses({ block, compact }) {
  const courses = block.courses ?? []
  const levels = [...new Set(courses.map((c) => c.study_level))]
  const [level, setLevel] = useState('')
  if (!courses.length) return null
  const visible = level ? courses.filter((c) => c.study_level === level) : courses
  const tab = (active) => cn('rounded-lg px-4 py-2 text-xs font-semibold transition', active ? 'bg-primary text-white' : 'bg-lavender-200 text-slate-600 hover:text-primary')

  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block, link(block))} className="!mb-5" />
      {levels.length > 1 && (
        <div className="mb-5 flex flex-wrap gap-2" role="tablist" aria-label="Study level">
          <button type="button" role="tab" aria-selected={!level} className={tab(!level)} onClick={() => setLevel('')}>All Courses</button>
          {levels.map((l) => (
            <button key={l} type="button" role="tab" aria-selected={level === l} className={tab(level === l)} onClick={() => setLevel(l)}>
              {studyLevelLabel(l)}
            </button>
          ))}
        </div>
      )}
      <div className={cn('grid gap-4 sm:grid-cols-2', compact ? 'lg:grid-cols-4' : 'lg:grid-cols-4')}>
        {visible.map((c) => (
          <CourseCard key={c.id} course={c} compact />
        ))}
      </div>
    </Shell>
  )
}

function StoryVideos({ block, compact }) {
  const videos = block.videos ?? []
  if (!videos.length) return null
  return (
    <Shell compact={compact} id={block.anchor} tone>
      <SectionHeader {...head(block, link(block))} />
      <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {videos.map((v) => (
          <VideoCard key={v.id} video={{ ...v, category: v.subtitle ? { name: v.subtitle } : null }} />
        ))}
      </div>
    </Shell>
  )
}

function Timeline({ block, compact }) {
  const items = block.timeline ?? []
  if (!items.length) return null
  return (
    <Shell compact={compact} id={block.anchor} className={!compact && 'bg-gradient-to-b from-lavender to-white'}>
      <SectionHeader {...head(block)} />
      <ol className="relative grid gap-8 sm:grid-cols-2 lg:grid-cols-5 lg:gap-4">
        <span className="absolute top-[86px] right-[10%] left-[10%] hidden h-0.5 bg-primary-200 lg:block" aria-hidden="true" />
        {items.map((item) => (
          <li key={item.id} className="relative flex flex-col items-center text-center">
            <span className="grid size-16 place-items-center rounded-full border border-primary-100 bg-white text-primary shadow-card">
              <AppIcon name={item.icon} fallback="milestone" className="size-7" />
            </span>
            <span className="relative z-10 my-3 size-3 rounded-full bg-primary ring-4 ring-primary-100" />
            <span className="rounded-md bg-primary px-3 py-1 text-xs font-bold text-white">{item.year}</span>
            <h3 className="mt-3 max-w-44 text-sm font-medium text-slate-700">{item.title}</h3>
            {item.description && <p className="mt-1 text-xs text-slate-500">{item.description}</p>}
          </li>
        ))}
      </ol>
    </Shell>
  )
}

function Team({ block, compact }) {
  const team = block.team ?? []
  if (!team.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block, link(block))} />
      <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {team.map((m) => (
          <TeamCard key={m.id} member={m} />
        ))}
      </div>
    </Shell>
  )
}

function Partners({ block, compact }) {
  if (!block.partners?.length) return null
  return (
    <Shell compact={compact} id={block.anchor || 'partners'} className={!compact && 'pt-0'}>
      <SectionHeader {...head(block)} />
      <PartnerLogoSlider partners={block.partners} />
    </Shell>
  )
}

function Testimonials({ block, compact }) {
  const items = (block.testimonials ?? []).map((t) => ({ ...t, student_name: t.name, quote: t.content, university_name: t.university, course_name: t.course }))
  if (!items.length) return null
  return (
    <Shell compact={compact} id={block.anchor} tone>
      <SectionHeader {...head(block)} />
      <QuoteCarousel items={items} />
    </Shell>
  )
}

function Faq({ block, compact }) {
  if (!block.faqs?.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block, link(block))} />
      <FAQAccordion faqs={block.faqs} />
    </Shell>
  )
}

/** Map (Settings > Contact > map embed URL, else the first office address) + office cards. */
function Offices({ block, compact }) {
  const { setting } = useSite()
  const offices = block.data?.items ?? []
  const query = offices[0]?.address || setting('contact.contact_address')
  const embed = setting('contact.map_embed_url') || (query ? `https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed` : null)
  if (!embed && !offices.length) return null
  return (
    <Shell compact={compact} id={block.anchor}>
      <SectionHeader {...head(block)} />
      <div className={cn('grid gap-6', offices.length && embed && 'lg:grid-cols-[1.4fr_1fr]')}>
        {embed && (
          <div className="overflow-hidden rounded-2xl border border-slate-100 bg-slate-100 shadow-card">
            <iframe title="Office map" src={embed} loading="lazy" referrerPolicy="no-referrer-when-downgrade" className="h-80 w-full lg:h-full lg:min-h-96" allowFullScreen />
          </div>
        )}
        {offices.length > 0 && (
          <ul className="space-y-4">
            {offices.map((o, i) => (
              <li key={i} className="flex gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-card">
                {o.image ? <Image src={o.image} alt="" className="size-20 shrink-0 rounded-xl object-cover" /> : <span className="grid size-12 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary"><MapPin className="size-5" /></span>}
                <div className="min-w-0">
                  <h3 className="text-base font-semibold">{o.name}</h3>
                  {o.address && <p className="mt-1 text-sm whitespace-pre-line text-slate-500">{o.address}</p>}
                  {o.directions_url && (
                    <SmartLink to={o.directions_url} className="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                      Get Directions <ArrowRight className="size-3.5" />
                    </SmartLink>
                  )}
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </Shell>
  )
}

function Cta({ block }) {
  const d = block.data ?? {}
  return (
    <CTASection
      eyebrow={block.eyebrow}
      title={block.title}
      subtitle={block.subtitle}
      primary={{ text: d.primary_text, url: d.primary_url }}
      secondary={{ text: d.secondary_text, url: d.secondary_url }}
      backgroundImage={d.background_image}
      sideImage={d.side_image}
    />
  )
}

const BLOCKS = {
  rich_text: RichText,
  text_images: TextImages,
  intro: Intro,
  statistics: Statistics,
  feature_cards: FeatureCards,
  features_with_image: FeaturesWithImage,
  checklist: Checklist,
  list: List,
  table: Table,
  gallery: Gallery,
  related_universities: RelatedUniversities,
  related_courses: RelatedCourses,
  story_videos: StoryVideos,
  timeline: Timeline,
  team: Team,
  partners: Partners,
  testimonials: Testimonials,
  faq: Faq,
  offices: Offices,
  cta: Cta,
}

/** URL-safe anchor from a section title (used by in-page tab navigation). */
// eslint-disable-next-line react-refresh/only-export-components
export const blockAnchor = (block) =>
  `s-${String(block.title ?? block.type).replaceAll('*', '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`

/**
 * Renders ordered CMS blocks. Consecutive half-width blocks sit side by side.
 * renderListing(block): content for the "Main list" placeholder on list pages.
 */
export default function PageBlocks({ blocks = [], compact = false, renderListing }) {
  const groups = []
  blocks.forEach((block) => {
    const half = block.data?.width === 'half'
    const last = groups.at(-1)
    if (half && last?.half && last.blocks.length < 2) last.blocks.push(block)
    else groups.push({ half, blocks: [block] })
  })

  const render = (block, asCompact = compact) => {
    if (block.type === 'listing') return renderListing ? <div key={block.id}>{renderListing(block)}</div> : null
    const Block = BLOCKS[block.type]
    return Block ? <Block key={block.id} block={{ ...block, anchor: blockAnchor(block) }} compact={asCompact} /> : null
  }

  return groups.map((group, i) =>
    group.half && group.blocks.length === 2 ? (
      // A pair shares one container; each half renders compact inside it.
      <div key={i} className={cn('grid gap-x-10 md:grid-cols-2', !compact && 'container-page py-4')}>
        {group.blocks.map((b) => render(b, true))}
      </div>
    ) : (
      group.blocks.map((b) => render(b))
    ),
  )
}
