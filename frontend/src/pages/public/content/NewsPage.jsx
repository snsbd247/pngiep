import { useQuery } from '@tanstack/react-query'
import { BlogCard } from '@/components/cards'
import { PopularPosts, SidebarBlocks } from '@/components/content/ContentWidgets'
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

/** Sidebar shared by the news list and article pages. */
export function NewsSidebar({ meta, sidebarBlocks = [] }) {
  return (
    <aside className="space-y-6">
      <SidebarBlocks blocks={sidebarBlocks.slice(0, 1)} />
      <PopularPosts title="Popular News" posts={meta?.popular} basePath="/news" />
      <SidebarBlocks blocks={sidebarBlocks.slice(1)} />
    </aside>
  )
}

export default function NewsPage() {
  const { params, apiParams, setParams } = useFilterParams()
  const query = useQuery({ queryKey: ['news', apiParams], queryFn: () => contentService.news(apiParams), placeholderData: (p) => p })
  const meta = query.data?.meta

  return (
    <ListingLayout slug="news">
      {({ sidebarBlocks }) => (
        <div className="grid gap-8 lg:grid-cols-[1fr_320px]">
          <div className="min-w-0">
            <div className="mb-6 grid gap-3 sm:grid-cols-[1fr_180px]">
              <SearchBar value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search news…" />
              <FormSelect aria-label="Sort by" value={params.sort ?? 'latest'} onChange={(e) => setParams({ sort: e.target.value })} options={SORTS} />
            </div>
            {query.isPending ? (
              <LoadingSkeleton count={6} />
            ) : query.isError ? (
              <ErrorState error={query.error} onRetry={query.refetch} />
            ) : !query.data.data.length ? (
              <EmptyState title="No news found" description={params.search ? 'Try another search term.' : 'Check back soon for the latest updates.'} />
            ) : (
              <>
                <div className={cn('grid gap-5 sm:grid-cols-2 xl:grid-cols-3', query.isFetching && 'opacity-60')}>
                  {query.data.data.map((n) => <BlogCard key={n.id} post={n} basePath="/news" />)}
                </div>
                <Pagination className="mt-8" meta={meta} onPageChange={(page) => setParams({ page })} />
              </>
            )}
          </div>
          <NewsSidebar meta={meta} sidebarBlocks={sidebarBlocks} />
        </div>
      )}
    </ListingLayout>
  )
}
