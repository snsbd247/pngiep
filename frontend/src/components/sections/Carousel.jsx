import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { A11y, Autoplay, Navigation, Pagination } from 'swiper/modules'
import { Swiper, SwiperSlide } from 'swiper/react'
import 'swiper/css'
import 'swiper/css/pagination'
import { cn } from '@/lib/utils'

const DEFAULT_BREAKPOINTS = {
  0: { slidesPerView: 1.15, spaceBetween: 16 },
  640: { slidesPerView: 2, spaceBetween: 20 },
  1024: { slidesPerView: 3, spaceBetween: 24 },
}

/**
 * Reusable Swiper carousel with round side arrows (inside the container on mobile).
 * <Carousel items={list} renderItem={(item) => <Card {...item} />} breakpoints={…} />
 */
export default function Carousel({ items = [], renderItem, breakpoints = DEFAULT_BREAKPOINTS, autoplay = false, dots = false, arrows = true, light = false, className }) {
  // Callback refs in state: Swiper re-initialises navigation once both buttons exist.
  const [prevEl, setPrevEl] = useState(null)
  const [nextEl, setNextEl] = useState(null)
  if (!items.length) return null

  const arrowCls = cn(
    'absolute top-1/2 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-full shadow-card transition disabled:opacity-0',
    light ? 'bg-white/90 text-primary hover:bg-white' : 'bg-white text-primary hover:bg-primary hover:text-white',
  )

  return (
    <div className={cn('relative', arrows && 'px-0 md:px-12', className)}>
      <Swiper
        modules={[Navigation, Pagination, Autoplay, A11y]}
        breakpoints={breakpoints}
        autoplay={autoplay ? { delay: 4500, disableOnInteraction: false, pauseOnMouseEnter: true } : false}
        pagination={dots ? { clickable: true } : false}
        key={arrows && prevEl && nextEl ? 'nav' : 'no-nav'}
        navigation={arrows && prevEl && nextEl ? { prevEl, nextEl } : false}
        className={cn('!py-2', dots && '!pb-10')}
      >
        {items.map((item, i) => (
          <SwiperSlide key={item.id ?? i} className="!h-auto">
            {renderItem(item, i)}
          </SwiperSlide>
        ))}
      </Swiper>
      {arrows && (
        <>
          <button ref={setPrevEl} type="button" className={cn(arrowCls, 'left-0 hidden md:grid')} aria-label="Previous">
            <ChevronLeft className="size-5" />
          </button>
          <button ref={setNextEl} type="button" className={cn(arrowCls, 'right-0 hidden md:grid')} aria-label="Next">
            <ChevronRight className="size-5" />
          </button>
        </>
      )}
    </div>
  )
}
