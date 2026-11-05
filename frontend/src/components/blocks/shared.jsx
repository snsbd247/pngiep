import { CheckCircle2 } from 'lucide-react'
import { useState } from 'react'
import { FeatureCard, PhotoCard, SuccessQuoteCard } from '@/components/cards'
import Carousel from '@/components/sections/Carousel'
import HighlightText from '@/components/ui/HighlightText'
import Image from '@/components/ui/Image'
import Lightbox from '@/components/ui/Lightbox'
import { cn } from '@/lib/utils'

/** Photo + purple panel with a checklist ("Why choose us"). */
export function SplitChecklist({ title, subtitle, image, items = [] }) {
  return (
    <section className="grid overflow-hidden bg-secondary lg:grid-cols-2">
      <Image src={image} alt="" className="h-64 w-full sm:h-80 lg:h-[440px]" />
      <div className="relative flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">
        <div className="pointer-events-none absolute -right-20 -bottom-20 size-72 rounded-full border-[40px] border-white/5" aria-hidden="true" />
        {title && (
          <h2 className="text-2xl text-white sm:text-3xl">
            <HighlightText text={title} highlightClassName="text-primary-200" />
          </h2>
        )}
        {subtitle && <p className="mt-2 text-sm text-white/75">{subtitle}</p>}
        <ul className="mt-6 space-y-3">
          {items.map((item, i) => (
            <li key={i} className="flex items-start gap-3 text-sm text-white/90 sm:text-[15px]">
              <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-primary-200" />
              {item.text}
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}

/** Responsive grid of icon feature cards. */
export function FeatureGrid({ items = [], columns = 5 }) {
  const cols = { 3: 'md:grid-cols-3', 4: 'lg:grid-cols-4', 5: 'md:grid-cols-3 lg:grid-cols-5' }[columns] ?? 'md:grid-cols-3'
  return (
    <div className={cn('grid gap-4 sm:grid-cols-2', cols)}>
      {items.map((item, i) => (
        <FeatureCard key={i} item={item} index={i} href={item.url} />
      ))}
    </div>
  )
}

/** Carousel of quote cards (success stories or testimonials). */
export function QuoteCarousel({ items = [] }) {
  return (
    <Carousel
      items={items}
      dots
      autoplay
      breakpoints={{ 0: { slidesPerView: 1, spaceBetween: 16 }, 768: { slidesPerView: 2, spaceBetween: 20 }, 1280: { slidesPerView: 3, spaceBetween: 24 } }}
      renderItem={(item) => <SuccessQuoteCard story={item} />}
    />
  )
}

/** Grid of photos with a lightbox. */
export function PhotoGrid({ photos = [], className }) {
  const [index, setIndex] = useState(null)
  return (
    <>
      <div className={cn('grid grid-cols-2 gap-3 sm:grid-cols-4', className)}>
        {photos.map((photo, i) => (
          <PhotoCard key={photo.id} photo={photo} onOpen={() => setIndex(i)} />
        ))}
      </div>
      <Lightbox images={photos} index={index} onChange={setIndex} onClose={() => setIndex(null)} />
    </>
  )
}
