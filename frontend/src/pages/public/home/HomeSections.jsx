import { ArrowRight, CalendarDays, Mail, MapPin, Play, User } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { A11y, Autoplay, EffectFade, Pagination } from 'swiper/modules'
import { Swiper, SwiperSlide } from 'swiper/react'
import 'swiper/css'
import 'swiper/css/effect-fade'
import 'swiper/css/pagination'
import { FeatureGrid, PhotoGrid, QuoteCarousel, SplitChecklist } from '@/components/blocks/shared'
import { BlogCard, DestinationCard, ServiceTile, UniversityLogoCard, VideoCard, VideoModal } from '@/components/cards'
import Carousel from '@/components/sections/Carousel'
import CTASection from '@/components/sections/CTASection'
import NewsletterBar from '@/components/sections/NewsletterBar'
import PartnerLogoSlider from '@/components/sections/PartnerLogoSlider'
import SectionHeader from '@/components/sections/SectionHeader'
import StatsBar from '@/components/sections/StatsBar'
import Button from '@/components/ui/Button'
import HighlightText from '@/components/ui/HighlightText'
import { assetUrl, cn, youtubeId } from '@/lib/utils'

/** Common header props from a homepage section record. */
const header = (s) => ({ eyebrow: s.eyebrow, title: s.title, subtitle: s.subtitle, linkText: s.button_text, linkUrl: s.button_url })

function HeroSlide({ slide, eager }) {
  const [videoOpen, setVideoOpen] = useState(false)
  const videoId = youtubeId(slide.video_url)

  return (
    <div className="relative isolate overflow-hidden bg-lavender">
      {slide.image && (
        <div className="absolute inset-y-0 right-0 -z-10 w-full md:w-[58%]">
          <img src={assetUrl(slide.image)} alt="" className="size-full object-cover object-center" fetchPriority={eager ? 'high' : 'auto'} loading={eager ? 'eager' : 'lazy'} />
          <div className="absolute inset-0 bg-gradient-to-r from-lavender via-lavender/80 to-transparent md:via-lavender/20" />
        </div>
      )}
      <div className="container-page flex min-h-[460px] items-center py-14 md:min-h-[520px] lg:min-h-[560px]">
        <div className="animate-fade-up max-w-xl">
          {slide.badge && (
            <span className="mb-5 inline-flex rounded-md border border-primary-200 bg-white/80 px-3 py-1 text-[11px] font-bold tracking-[0.14em] text-primary uppercase">
              {slide.badge}
            </span>
          )}
          <h1 className="text-4xl leading-[1.15] font-bold sm:text-5xl lg:text-[3.4rem]">
            <HighlightText text={slide.title} />
            {slide.highlight && <span className="block text-primary">{slide.highlight}</span>}
          </h1>
          {slide.subtitle && <p className="mt-5 max-w-md text-[15px] leading-relaxed text-slate-600">{slide.subtitle}</p>}
          <div className="mt-8 flex flex-wrap gap-3">
            {slide.primary_button_text && (
              <Button to={slide.primary_button_url} size="lg">
                {slide.primary_button_text} <ArrowRight className="size-4" />
              </Button>
            )}
            {slide.secondary_button_text && (
              <Button to={slide.secondary_button_url} variant="outline-primary" size="lg">
                {slide.secondary_button_text}
              </Button>
            )}
          </div>
        </div>
      </div>
      {videoId && (
        <>
          <button type="button" onClick={() => setVideoOpen(true)} className="absolute right-6 bottom-20 hidden items-center gap-3 text-left text-white lg:flex">
            <span className="grid size-14 place-items-center rounded-full border-2 border-white/70 bg-white/20 backdrop-blur transition hover:bg-white/30">
              <Play className="ml-0.5 size-6 fill-current" />
            </span>
            {slide.video_label && <span className="text-sm font-semibold drop-shadow">{slide.video_label}</span>}
          </button>
          <VideoModal open={videoOpen} onClose={() => setVideoOpen(false)} youtubeId={videoId} title={slide.video_label} />
        </>
      )}
    </div>
  )
}

function Hero({ section }) {
  const slides = section.slides ?? []
  if (!slides.length) return null
  if (slides.length === 1) return <HeroSlide slide={slides[0]} eager />

  return (
    <Swiper modules={[Autoplay, Pagination, EffectFade, A11y]} effect="fade" fadeEffect={{ crossFade: true }} autoplay={{ delay: 6000 }} pagination={{ clickable: true }} loop>
      {slides.map((slide, i) => (
        <SwiperSlide key={slide.id}>
          <HeroSlide slide={slide} eager={i === 0} />
        </SwiperSlide>
      ))}
    </Swiper>
  )
}

function Statistics({ section, previousKey }) {
  return <StatsBar items={section.statistics} overlap={previousKey === 'hero'} className={previousKey === 'hero' ? '' : 'pt-10'} />
}

function Destinations({ section }) {
  if (!section.destinations?.length) return null
  return (
    <section className="section">
      <div className="container-page">
        <SectionHeader {...header(section)} />
        <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
          {section.destinations.map((d) => (
            <DestinationCard key={d.id} destination={d} />
          ))}
        </div>
      </div>
    </section>
  )
}

function Services({ section }) {
  if (!section.services?.length) return null
  return (
    <section className="pb-12 md:pb-16 lg:pb-20">
      <div className="container-page">
        <SectionHeader {...header(section)} />
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7">
          {section.services.map((s, i) => (
            <ServiceTile key={s.id} service={s} index={i} />
          ))}
        </div>
      </div>
    </section>
  )
}

function Universities({ section }) {
  if (!section.universities?.length) return null
  const bg = section.data?.background_image
  return (
    <section className="relative isolate overflow-hidden bg-secondary py-12 md:py-16">
      {bg && <img src={assetUrl(bg)} alt="" loading="lazy" className="absolute inset-0 -z-20 size-full object-cover opacity-25" />}
      <div className="absolute inset-0 -z-10 bg-gradient-to-r from-secondary via-secondary/95 to-primary/80" aria-hidden="true" />
      <div className="container-page">
        <SectionHeader {...header(section)} light />
        <Carousel
          items={section.universities}
          light
          breakpoints={{ 0: { slidesPerView: 2, spaceBetween: 12 }, 640: { slidesPerView: 3, spaceBetween: 16 }, 1024: { slidesPerView: 5, spaceBetween: 16 }, 1280: { slidesPerView: 6, spaceBetween: 16 } }}
          renderItem={(u) => <UniversityLogoCard university={u} />}
        />
      </div>
    </section>
  )
}

function Pathways({ section }) {
  const items = section.data?.items ?? []
  if (!items.length) return null
  return (
    <section className="section">
      <div className="container-page">
        <SectionHeader {...header(section)} />
        <FeatureGrid items={items} columns={5} />
      </div>
    </section>
  )
}

function WhyChoose({ section }) {
  return <SplitChecklist title={section.title} subtitle={section.subtitle} image={section.data?.image} items={section.data?.items ?? []} />
}

function SuccessStories({ section }) {
  if (!section.stories?.length) return null
  return (
    <section className="section">
      <div className="container-page">
        <SectionHeader {...header(section)} />
        <QuoteCarousel items={section.stories} />
      </div>
    </section>
  )
}

function Testimonials({ section }) {
  if (!section.testimonials?.length) return null
  return (
    <section className="section bg-lavender">
      <div className="container-page">
        <SectionHeader {...header(section)} />
        <QuoteCarousel items={section.testimonials.map((t) => ({ ...t, student_name: t.name, quote: t.content, university_name: t.university, course_name: t.course }))} />
      </div>
    </section>
  )
}

function Gallery({ section }) {
  const photos = section.photos ?? []
  const videos = section.videos ?? []
  if (!photos.length && !videos.length) return null
  const d = section.data ?? {}

  return (
    <section className="pb-12 md:pb-16 lg:pb-20">
      <div className={cn('container-page grid gap-10', photos.length && videos.length && 'lg:grid-cols-[3fr_2fr]')}>
        {photos.length > 0 && (
          <div>
            <SectionHeader {...header(section)} className="!mb-6" />
            <PhotoGrid photos={photos} />
          </div>
        )}
        {videos.length > 0 && (
          <div>
            <SectionHeader eyebrow={d.video_eyebrow} title={d.video_title} subtitle={d.video_subtitle} linkText={d.video_button_text} linkUrl={d.video_button_url} className="!mb-6" />
            <div className="grid grid-cols-2 gap-3">
              {videos.map((v) => (
                <VideoCard key={v.id} video={v} />
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  )
}

function AppointmentMiniForm({ title, subtitle, button, destinations = [] }) {
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', destination: '', date: '' })
  const [today] = useState(() => new Date().toISOString().slice(0, 10))
  const field = 'h-11 w-full rounded-lg border-0 bg-white pr-3 pl-10 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-4 focus:ring-white/40 focus:outline-none'
  const set = (key) => (e) => setForm((f) => ({ ...f, [key]: e.target.value }))

  return (
    <form
      onSubmit={(e) => {
        e.preventDefault()
        const params = new URLSearchParams(Object.entries(form).filter(([, v]) => v))
        navigate(`/book-appointment${params.size ? `?${params}` : ''}`)
      }}
      className="flex h-full flex-col rounded-xl bg-primary p-6 text-white shadow-glow"
    >
      {title && <h3 className="text-xl text-white">{title}</h3>}
      {subtitle && <p className="mt-1 text-sm text-white/80">{subtitle}</p>}
      <div className="mt-5 space-y-3">
        <label className="relative block">
          <User className="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input className={field} placeholder="Full Name" aria-label="Full Name" value={form.name} onChange={set('name')} />
        </label>
        <label className="relative block">
          <Mail className="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input type="email" className={field} placeholder="Email Address" aria-label="Email Address" value={form.email} onChange={set('email')} />
        </label>
        {destinations.length > 0 && (
          <label className="relative block">
            <MapPin className="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
            <select className={field} aria-label="Destination" value={form.destination} onChange={set('destination')}>
              <option value="">Select Destination</option>
              {destinations.map((d) => (
                <option key={d.id} value={d.slug}>{d.name}</option>
              ))}
            </select>
          </label>
        )}
        <label className="relative block">
          <CalendarDays className="absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
          <input type="date" min={today} className={field} aria-label="Preferred Date" value={form.date} onChange={set('date')} />
        </label>
      </div>
      <Button type="submit" variant="white" className="mt-5 w-full">
        {button || 'Book Appointment'} <ArrowRight className="size-4" />
      </Button>
    </form>
  )
}

function LatestNews({ section, context }) {
  const posts = section.posts ?? []
  const d = section.data ?? {}
  if (!posts.length && !d.form_title) return null

  return (
    <section className="pb-12 md:pb-16 lg:pb-20">
      <div className="container-page grid gap-8 lg:grid-cols-[1fr_320px]">
        <div>
          <SectionHeader {...header(section)} />
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {posts.map((p) => (
              <BlogCard key={p.id} post={p} />
            ))}
          </div>
        </div>
        {d.form_title && <AppointmentMiniForm title={d.form_title} subtitle={d.form_subtitle} button={d.form_button} destinations={context.destinations} />}
      </div>
    </section>
  )
}

function Partners({ section }) {
  if (!section.partners?.length) return null
  return (
    <section className="pb-12 md:pb-16">
      <div className="container-page">
        <SectionHeader {...header(section)} />
        <PartnerLogoSlider partners={section.partners} />
      </div>
    </section>
  )
}

function Cta({ section }) {
  const d = section.data ?? {}
  return (
    <CTASection
      eyebrow={section.eyebrow}
      title={section.title}
      subtitle={section.subtitle}
      primary={{ text: d.primary_text, url: d.primary_url }}
      secondary={{ text: d.secondary_text, url: d.secondary_url }}
      backgroundImage={d.background_image}
      sideImage={d.side_image}
    />
  )
}

function Newsletter({ section }) {
  return <NewsletterBar title={section.title} subtitle={section.subtitle} buttonLabel={section.data?.button_label} placeholder={section.data?.placeholder} />
}

/** Homepage section key → component. Unknown keys are ignored. */
export const HOME_SECTIONS = {
  hero: Hero,
  statistics: Statistics,
  destinations: Destinations,
  services: Services,
  universities: Universities,
  pathways: Pathways,
  why_choose: WhyChoose,
  success_stories: SuccessStories,
  testimonials: Testimonials,
  gallery: Gallery,
  latest_news: LatestNews,
  partners: Partners,
  cta: Cta,
  newsletter: Newsletter,
}
