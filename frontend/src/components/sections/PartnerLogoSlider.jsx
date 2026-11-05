import { assetUrl } from '@/lib/utils'
import Carousel from './Carousel'

function PartnerLogo({ partner }) {
  const inner = partner.logo ? (
    <img src={assetUrl(partner.logo)} alt={partner.name} loading="lazy" className="max-h-14 w-auto max-w-full object-contain" />
  ) : (
    <span className="text-center text-sm leading-tight font-bold text-secondary sm:text-base">{partner.name}</span>
  )

  const cls = 'flex h-24 items-center justify-center rounded-xl border border-slate-100 bg-white px-5 transition hover:border-primary-200 hover:shadow-card'
  return partner.website ? (
    <a href={partner.website} target="_blank" rel="noopener noreferrer" className={cls} title={partner.name}>{inner}</a>
  ) : (
    <div className={cls} title={partner.name}>{inner}</div>
  )
}

/** Auto-scrolling partner logos. Without an uploaded logo the partner name is shown. */
export default function PartnerLogoSlider({ partners = [] }) {
  return (
    <Carousel
      items={partners}
      autoplay
      breakpoints={{ 0: { slidesPerView: 2.2, spaceBetween: 12 }, 640: { slidesPerView: 3, spaceBetween: 16 }, 1024: { slidesPerView: 5, spaceBetween: 20 }, 1280: { slidesPerView: 6, spaceBetween: 20 } }}
      renderItem={(partner) => <PartnerLogo partner={partner} />}
    />
  )
}
