import { useQuery } from '@tanstack/react-query'
import PageBlocks from '@/components/blocks/PageBlocks'
import HeroBanner from '@/components/sections/HeroBanner'
import SectionHeader from '@/components/sections/SectionHeader'
import Seo from '@/components/Seo'
import { stripHighlight } from '@/components/ui/HighlightText'
import { publicService } from '@/services/publicService'

/** Blocks that belong in a list page's right-hand column rather than the main flow. */
// eslint-disable-next-line react-refresh/only-export-components
export const isSidebarBlock = (s) => s.type === 'sidebar_card' || (s.type === 'statistics' && s.data?.style === 'sidebar')

/**
 * Built-in list page (services, universities, blog, gallery …). Hero and the sections
 * around the list come from the CMS page with the same slug (template "listing").
 * `children` is the API-driven list, placed where the "Main list" block is. It may be a
 * function receiving { sidebarBlocks, heading } for pages with a sidebar.
 */
export default function ListingLayout({ slug, children, heroAside, belowHero, headingInside = false }) {
  const page = useQuery({ queryKey: ['page', slug], queryFn: () => publicService.page(slug), retry: false })
  const data = page.data
  const title = stripHighlight(data?.title ?? '')
  const allSections = data?.sections ?? []
  const sidebarBlocks = allSections.filter(isSidebarBlock)
  const sections = allSections.filter((s) => !isSidebarBlock(s))
  const hasListing = sections.some((s) => s.type === 'listing')

  const list = (block) => {
    const heading = block && (block.eyebrow || block.title || block.subtitle) ? block : null
    return (
      <section className="section !pt-10">
        <div className="container-page">
          {heading && !headingInside && <SectionHeader eyebrow={heading.eyebrow} title={heading.title} subtitle={heading.subtitle} />}
          {typeof children === 'function' ? children({ sidebarBlocks, heading }) : children}
        </div>
      </section>
    )
  }

  return (
    <>
      <Seo title={title} description={data?.excerpt} image={data?.banner_image} seo={data?.seo} />
      {data ? (
        <HeroBanner title={data.title} subtitle={data.subtitle} text={data.excerpt} eyebrow={data.eyebrow} image={data.banner_image} breadcrumbs={[{ label: title }]} aside={heroAside} />
      ) : (
        <div className="h-56 bg-secondary" aria-busy={page.isPending} />
      )}
      {belowHero}
      {hasListing ? <PageBlocks blocks={sections} renderListing={list} /> : (
        <>
          {list(null)}
          <PageBlocks blocks={sections} />
        </>
      )}
    </>
  )
}
