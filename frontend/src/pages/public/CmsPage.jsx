import { useQuery } from '@tanstack/react-query'
import { useParams } from 'react-router-dom'
import PageBlocks from '@/components/blocks/PageBlocks'
import HeroBanner from '@/components/sections/HeroBanner'
import Seo from '@/components/Seo'
import { stripHighlight } from '@/components/ui/HighlightText'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { publicService } from '@/services/publicService'
import NotFoundPage from './NotFoundPage'

/**
 * Any published CMS page: About Us, Privacy Policy, Terms, and pages the admin creates.
 * The slug comes from the URL (/:slug) or is fixed by the route (e.g. /about).
 */
export default function CmsPage({ slug: fixedSlug }) {
  const params = useParams()
  const slug = fixedSlug ?? params.slug
  const query = useQuery({ queryKey: ['page', slug], queryFn: () => publicService.page(slug) })

  if (query.isPending) return <LoadingSkeleton variant="page" />
  if (query.error?.isNotFound) return <NotFoundPage />
  if (query.isError) {
    return (
      <div className="container-page py-20">
        <ErrorState error={query.error} onRetry={query.refetch} />
      </div>
    )
  }

  const page = query.data
  const title = stripHighlight(page.title)

  return (
    <>
      <Seo title={title} description={page.excerpt || page.subtitle} image={page.banner_image} seo={page.seo} />
      <HeroBanner
        title={page.title}
        subtitle={page.subtitle}
        text={page.excerpt}
        eyebrow={page.eyebrow}
        image={page.banner_image}
        breadcrumbs={[{ label: title }]}
      />
      {page.content && (
        <section className="section">
          <div className="container-page max-w-4xl">
            {/* Purified server-side before storage. */}
            <div className="prose-content" dangerouslySetInnerHTML={{ __html: page.content }} />
          </div>
        </section>
      )}
      <PageBlocks blocks={page.sections} />
    </>
  )
}
