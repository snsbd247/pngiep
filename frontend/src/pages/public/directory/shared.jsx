import { useQuery } from '@tanstack/react-query'
import PageBlocks from '@/components/blocks/PageBlocks'
import CTASection from '@/components/sections/CTASection'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { publicService } from '@/services/publicService'
import NotFoundPage from '../NotFoundPage'

/** Section types that always span the full page width below the two-column area. */
const FULL_WIDTH = ['faq', 'cta', 'partners', 'testimonials', 'timeline', 'team', 'checklist']

/**
 * Two-column detail body: compact sections in the main column, a sidebar on the
 * right, then full-width sections (FAQs, CTA …) underneath.
 */
export function DetailBody({ sections = [], intro, sidebar, beforeBottom }) {
  const main = sections.filter((s) => !FULL_WIDTH.includes(s.type))
  const bottom = sections.filter((s) => FULL_WIDTH.includes(s.type))

  return (
    <>
      <div className="container-page grid gap-8 py-10 lg:grid-cols-[1fr_340px] xl:grid-cols-[1fr_360px]">
        <div className="min-w-0">
          {intro}
          <PageBlocks blocks={main} compact />
        </div>
        <aside className="space-y-6 lg:pt-6">{sidebar}</aside>
      </div>
      {beforeBottom}
      <PageBlocks blocks={bottom} />
    </>
  )
}

/** Loading / 404 / error handling shared by detail pages. */
export function DetailQuery({ query, children }) {
  if (query.isPending) return <LoadingSkeleton variant="page" />
  if (query.error?.isNotFound) return <NotFoundPage />
  if (query.isError) {
    return (
      <div className="container-page py-20">
        <ErrorState error={query.error} onRetry={query.refetch} />
      </div>
    )
  }
  return children(query.data)
}

/** The CTA block of a built-in list page, reused at the bottom of related detail pages. */
export function ListingCta({ slug }) {
  const page = useQuery({ queryKey: ['page', slug], queryFn: () => publicService.page(slug), retry: false })
  const cta = page.data?.sections?.find((s) => s.type === 'cta')
  if (!cta) return null
  const d = cta.data ?? {}
  return (
    <CTASection
      eyebrow={cta.eyebrow}
      title={cta.title}
      subtitle={cta.subtitle}
      primary={{ text: d.primary_text, url: d.primary_url }}
      secondary={{ text: d.secondary_text, url: d.secondary_url }}
      backgroundImage={d.background_image}
      sideImage={d.side_image}
    />
  )
}
