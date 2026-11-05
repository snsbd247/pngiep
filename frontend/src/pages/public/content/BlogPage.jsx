import { useQuery } from '@tanstack/react-query'
import { BlogCard } from '@/components/cards'
import { CategoryList, CategoryTabs, PopularPosts, SidebarBlocks } from '@/components/content/ContentWidgets'
import ListingLayout from '@/components/directory/ListingLayout'
import FormSelect from '@/components/forms/FormSelect'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { contentService } from '@/services/publicService'

const SORTS = [
  { value: 'latest', label: 'Latest First' },
  { value: 'oldest', label: 'Oldest First' },
  { value: 'popular', label: 'Most Popular' },
]

/** Sidebar shared by the blog list and post pages. */
export function BlogSidebar({ meta, sidebarBlocks = [], category, onCategory, hrefFor }) {
  return (
    <aside className="space-y-6">
      <SidebarBlocks blocks={sidebarBlocks.slice(0, 1)} />
      <PopularPosts title="Popular Posts" posts={meta?.popular} />
      <CategoryList title="Categories" allLabel="All Posts" allCount={meta?.all_count} options={meta?.categories ?? []} value={category} onSelect={onCategory} hrefFor={hrefFor} />
      <SidebarBlocks blocks={sidebarBlocks.slice(1)} />
    </aside>
  )
}

export default function BlogPage() {
  const { params, apiParams, setParams } = useFilterParams()
  const query = useQuery({ queryKey: ['blogs', apiParams], queryFn: () => contentService.blogs(apiParams), placeholderData: (p) => p })
  const meta = query.data?.meta

  return (
    <ListingLayout
      slug="blog"
      belowHero={meta && (
        <CategoryTabs allLabel="All Posts" allIcon="newspaper" allCount={meta.all_count} options={meta.categories} value={params.category ?? ''} onChange={(category) => setParams({ category })} />
      )}
    >
      {({ sidebarBlocks }) => (
        <div className="grid gap-8 lg:grid-cols-[1fr_320px]">
          <div className="min-w-0">
            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_180px_180px_170px]">
              <SearchBar value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search articles, news or notices…" />
              <FormSelect aria-label="Category" placeholder="All Categories" value={params.category ?? ''} onChange={(e) => setParams({ category: e.target.value })} options={meta?.categories ?? []} />
              <FormSelect aria-label="Destination" placeholder="All Destinations" value={params.destination ?? ''} onChange={(e) => setParams({ destination: e.target.value })} options={meta?.destinations ?? []} />
              <FormSelect aria-label="Sort by" value={params.sort ?? 'latest'} onChange={(e) => setParams({ sort: e.target.value })} options={SORTS} />
            </div>
            {query.isPending ? (
              <LoadingSkeleton count={6} />
            ) : query.isError ? (
              <ErrorState error={query.error} onRetry={query.refetch} />
            ) : !query.data.data.length ? (
              <EmptyState title="No posts found" description="Try another category or search term." />
            ) : (
              <>
                <div className={cn('grid gap-5 sm:grid-cols-2 xl:grid-cols-3', query.isFetching && 'opacity-60')}>
                  {query.data.data.map((p) => <BlogCard key={p.id} post={p} />)}
                </div>
                <Pagination className="mt-8" meta={meta} onPageChange={(page) => setParams({ page })} />
              </>
            )}
          </div>
          <BlogSidebar meta={meta} sidebarBlocks={sidebarBlocks} category={params.category ?? ''} onCategory={(category) => setParams({ category })} />
        </div>
      )}
    </ListingLayout>
  )
}
