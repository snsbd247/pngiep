import { ArrowRight } from 'lucide-react'
import Button from '@/components/ui/Button'
import HighlightText from '@/components/ui/HighlightText'
import { assetUrl, cn } from '@/lib/utils'

/**
 * Purple call-to-action banner. All copy and links come from CMS data.
 * primary / secondary: { text, url }
 */
export default function CTASection({ eyebrow, title, subtitle, primary, secondary, backgroundImage, sideImage, className }) {
  if (!title) return null

  return (
    <section className={cn('py-10 md:py-14', className)}>
      <div className="container-page">
        <div className="relative isolate overflow-hidden rounded-2xl bg-primary px-6 py-10 shadow-glow sm:px-10 md:py-12 lg:flex lg:items-center lg:gap-8 lg:px-12">
          {backgroundImage && <img src={assetUrl(backgroundImage)} alt="" loading="lazy" className="absolute inset-0 -z-20 size-full object-cover opacity-30 mix-blend-luminosity" />}
          <div className="absolute inset-0 -z-10 bg-gradient-to-r from-primary via-primary/90 to-primary-400/70" aria-hidden="true" />
          <div className="flex-1">
            {eyebrow && <p className="mb-2 text-xs font-semibold tracking-[0.18em] text-white/80 uppercase">{eyebrow}</p>}
            <h2 className="text-2xl leading-tight text-white sm:text-3xl lg:text-[2.1rem]">
              <HighlightText text={title} highlightClassName="text-primary-200" />
            </h2>
            {subtitle && <p className="mt-2 max-w-2xl text-sm text-white/80 sm:text-base">{subtitle}</p>}
          </div>
          {sideImage && <img src={assetUrl(sideImage)} alt="" loading="lazy" className="absolute bottom-0 left-[58%] hidden h-[115%] w-auto object-contain object-bottom xl:block" />}
          {(primary?.text || secondary?.text) && (
            <div className="relative mt-7 flex flex-wrap gap-3 lg:mt-0 lg:shrink-0 lg:flex-col xl:flex-row">
              {primary?.text && (
                <Button to={primary.url} variant="white" size="lg">
                  {primary.text} <ArrowRight className="size-4" />
                </Button>
              )}
              {secondary?.text && (
                <Button to={secondary.url} variant="outline-light" size="lg">
                  {secondary.text} <ArrowRight className="size-4" />
                </Button>
              )}
            </div>
          )}
        </div>
      </div>
    </section>
  )
}
