import { ArrowRight } from 'lucide-react'
import HighlightText from '@/components/ui/HighlightText'
import SmartLink from '@/components/ui/SmartLink'
import { cn } from '@/lib/utils'

/**
 * Eyebrow + highlighted title + subtitle, with an optional "View all →" link on the right.
 * All text comes from the CMS.
 */
export default function SectionHeader({ eyebrow, title, subtitle, linkText, linkUrl, align = 'left', light = false, as: Heading = 'h2', className }) {
  if (!eyebrow && !title && !subtitle) return null
  const centered = align === 'center'

  return (
    <div className={cn('mb-8 flex flex-col gap-3 md:mb-10', centered ? 'items-center text-center' : 'sm:flex-row sm:items-end sm:justify-between', className)}>
      <div className={cn(centered && 'max-w-2xl')}>
        {eyebrow && <p className={cn('mb-2 text-xs font-bold tracking-[0.16em] uppercase', light ? 'text-primary-200' : 'text-primary')}>{eyebrow}</p>}
        {title && (
          <Heading className={cn('text-2xl leading-tight sm:text-3xl lg:text-[2.1rem]', light && 'text-white')}>
            <HighlightText text={title} highlightClassName={light ? 'text-primary-200' : 'text-primary'} />
          </Heading>
        )}
        {subtitle && <p className={cn('mt-2 text-sm leading-relaxed sm:text-base', light ? 'text-white/75' : 'text-slate-500')}>{subtitle}</p>}
      </div>
      {linkText && linkUrl && (
        <SmartLink to={linkUrl} className={cn('group inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold', light ? 'text-white' : 'text-primary')}>
          {linkText} <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
        </SmartLink>
      )}
    </div>
  )
}
