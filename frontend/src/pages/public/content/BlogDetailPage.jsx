import { useQuery } from '@tanstack/react-query'
import { CalendarDays, Clock, Eye, Tag, User } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
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
import { BlogSidebar } from './BlogPage'

export default function BlogDetailPage() {
  const { slug } = useParams()
  const query = useQuery({ queryKey: ['blog', slug], queryFn: () => contentService.blog(slug) })
  const listing = useQuery({ queryKey: ['page', 'blog'], queryFn: () => publicService.page('blog'), retry: false })
  const sidebarBlocks = (listing.data?.sections ?? []).filter(isSidebarBlock)
  const wrapped = { ...query, data: query.data?.data }

  return (
    <DetailQuery query={wrapped}>
      {(post) => {
        const meta = query.data.meta
        return (
          <>
            <Seo title={post.title} description={post.excerpt} image={post.featured_image} seo={post.seo} />
            <section className="bg-lavender">
              <div className="container-page py-10 md:py-14">
                <Breadcrumb className="mb-5" items={[{ label: 'Blog', to: '/blog' }, { label: post.title }]} />
                {post.category && (
                  <Link to={`/blog?category=${post.category.slug}`} className="mb-3 inline-block rounded-md bg-primary px-2.5 py-1 text-xs font-semibold text-white">{post.category.name}</Link>
                )}
                <h1 className="max-w-4xl text-3xl leading-tight sm:text-4xl lg:text-5xl">{post.title}</h1>
                <p className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
                  {post.author_name && <span className="flex items-center gap-1.5"><User className="size-4 text-primary" /> {post.author_name}</span>}
                  {post.published_at && <span className="flex items-center gap-1.5"><CalendarDays className="size-4 text-primary" /> {formatDate(post.published_at, { day: 'numeric', month: 'long', year: 'numeric' })}</span>}
                  <span className="flex items-center gap-1.5"><Clock className="size-4 text-primary" /> {post.reading_minutes} min read</span>
                  {post.views_count > 0 && <span className="flex items-center gap-1.5"><Eye className="size-4 text-primary" /> {post.views_count.toLocaleString()} views</span>}
                </p>
              </div>
            </section>

            <div className="container-page grid gap-10 py-10 lg:grid-cols-[1fr_320px]">
              <article className="min-w-0">
                {post.featured_image && <Image src={post.featured_image} alt={post.title} eager className="mb-8 aspect-[16/9] w-full rounded-2xl" />}
                {post.content && <div className="prose-content text-[15px]" dangerouslySetInnerHTML={{ __html: post.content }} />}
                <div className="mt-10 flex flex-col gap-5 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                  {post.tags?.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2">
                      <Tag className="size-4 text-primary" />
                      {post.tags.map((t) => (
                        <Link key={t.id} to={`/blog?tag=${t.slug}`} className="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary hover:bg-primary hover:text-white">{t.name}</Link>
                      ))}
                    </div>
                  )}
                  <ShareButtons title={post.title} />
                </div>
              </article>
              <BlogSidebar meta={meta} sidebarBlocks={sidebarBlocks} category={post.category?.slug ?? ''} hrefFor={(value) => (value ? `/blog?category=${value}` : '/blog')} />
            </div>

            {post.related?.length > 0 && (
              <section className="pb-12">
                <div className="container-page">
                  <SectionHeader title="Related *Posts*" linkText="View All" linkUrl="/blog" />
                  <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {post.related.map((r) => <BlogCard key={r.id} post={r} />)}
                  </div>
                </div>
              </section>
            )}
            <ListingCta slug="blog" />
          </>
        )
      }}
    </DetailQuery>
  )
}
