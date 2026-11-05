import Breadcrumb from '@/components/layout/Breadcrumb'
import HighlightText from '@/components/ui/HighlightText'
import { assetUrl, cn } from '@/lib/utils'

/**
 * Inner-page hero: full-bleed photo with a dark gradient, breadcrumb, title, subtitle
 * and optional body text / actions. `children` render below (e.g. an overlapping stats bar).
 */
export default function HeroBanner({ title, subtitle, text, eyebrow, image, breadcrumbs, actions, aside, className, children }) {
  const bg = assetUrl(image)

  return (
    <section className={cn('relative isolate overflow-hidden bg-secondary', className)}>
      {bg && <img src={bg} alt="" className="absolute inset-0 -z-20 size-full object-cover" fetchPriority="high" />}
      <div
        className={cn('absolute inset-0 -z-10', bg ? 'bg-gradient-to-r from-[#0b0726]/90 via-[#0b0726]/60 to-[#0b0726]/10' : 'bg-brand-gradient')}
        aria-hidden="true"
      />
      <div className="container-page flex items-center justify-between gap-8 py-14 md:py-20 lg:min-h-[300px] lg:py-24">
        <div className="max-w-2xl">
          {breadcrumbs && <Breadcrumb items={breadcrumbs} light className="mb-4" />}
          {eyebrow && <p className="mb-3 text-xs font-bold tracking-[0.18em] text-primary-200 uppercase">{eyebrow}</p>}
          {title && (
            <h1 className="text-3xl leading-tight text-white sm:text-4xl lg:text-[3.25rem]">
              <HighlightText text={title} highlightClassName="text-primary-200" />
            </h1>
          )}
          {subtitle && <p className="mt-3 text-base font-medium text-white sm:text-lg">{subtitle}</p>}
          {text && <p className="mt-3 max-w-xl text-sm leading-relaxed text-white/80">{text}</p>}
          {actions && <div className="mt-7 flex flex-wrap gap-3">{actions}</div>}
        </div>
        {aside && <div className="hidden lg:block">{aside}</div>}
      </div>
      {children}
    </section>
  )
}
