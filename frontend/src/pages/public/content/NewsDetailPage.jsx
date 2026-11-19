import { useQuery } from '@tanstack/react-query'
import { CalendarDays, Clock, ExternalLink, Eye, User } from 'lucide-react'
import { useParams } from 'react-router-dom'
import { BlogCard } from '@/components/cards'
import { ShareButtons } from '@/components/content/ContentWidgets'
import { isSidebarBlock } from '@/components/directory/ListingLayout'
import Breadcrumb from '@/components/layout/Breadcrumb'
import SectionHeader from '@/components/sections/SectionHeader'
import Seo from '@/components/Seo'
import Image from '@/components/ui/Image'
import { formatDate } from '@/lib/utils'
import { contentService, publicService } from '@/services/publicService'
import { DetailQuery, ListingCta } from '../directory/shared'
import { NewsSidebar } from './NewsPage'

export default function NewsDetailPage() {
  const { slug } = useParams()
  const query = useQuery({ queryKey: ['news-item', slug], queryFn: () => contentService.newsItem(slug) })
  const listing = useQuery({ queryKey: ['page', 'news'], queryFn: () => publicService.page('news'), retry: false })
  const sidebarBlocks = (listing.data?.sections ?? []).filter(isSidebarBlock)
  const wrapped = { ...query, data: query.data?.data }

  return (
    <DetailQuery query={wrapped}>
      {(item) => (
        <>
          <Seo title={item.title} description={item.excerpt} image={item.featured_image} seo={item.seo} />
          <section className="bg-lavender">
            <div className="container-page py-10 md:py-14">
              <Breadcrumb className="mb-5" items={[{ label: 'News', to: '/news' }, { label: item.title }]} />
              <h1 className="max-w-4xl text-3xl leading-tight sm:text-4xl lg:text-5xl">{item.title}</h1>
              <p className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
                {item.author_name && <span className="flex items-center gap-1.5"><User className="size-4 text-primary" /> {item.author_name}</span>}
                {item.published_at && <span className="flex items-center gap-1.5"><CalendarDays className="size-4 text-primary" /> {formatDate(item.published_at, { day: 'numeric', month: 'long', year: 'numeric' })}</span>}
                <span className="flex items-center gap-1.5"><Clock className="size-4 text-primary" /> {item.reading_minutes} min read</span>
                {item.views_count > 0 && <span className="flex items-center gap-1.5"><Eye className="size-4 text-primary" /> {item.views_count.toLocaleString()} views</span>}
              </p>
            </div>
          </section>

          <div className="container-page grid gap-10 py-10 lg:grid-cols-[1fr_320px]">
            <article className="min-w-0">
              {item.featured_image && <Image src={item.featured_image} alt={item.title} eager className="mb-8 aspect-[16/9] w-full rounded-2xl" />}
              {item.content && <div className="prose-content text-[15px]" dangerouslySetInnerHTML={{ __html: item.content }} />}
              <div className="mt-10 flex flex-col gap-5 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                {item.source_url ? (
                  <a href={item.source_url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                    <ExternalLink className="size-4" /> Source: {item.source_name || new URL(item.source_url).hostname}
                  </a>
                ) : item.source_name ? (
                  <span className="text-sm text-slate-500">Source: {item.source_name}</span>
                ) : <span />}
                <ShareButtons title={item.title} />
              </div>
            </article>
            <NewsSidebar meta={query.data.meta} sidebarBlocks={sidebarBlocks} />
          </div>

          {item.related?.length > 0 && (
            <section className="pb-12">
              <div className="container-page">
                <SectionHeader title="More *News*" linkText="View All" linkUrl="/news" />
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                  {item.related.map((r) => <BlogCard key={r.id} post={r} basePath="/news" />)}
                </div>
              </div>
            </section>
          )}
          <ListingCta slug="news" />
        </>
      )}
    </DetailQuery>
  )
}
