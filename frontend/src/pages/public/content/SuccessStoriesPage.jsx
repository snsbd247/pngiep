import { useQuery } from '@tanstack/react-query'
import { SidebarBlocks, StoryCard } from '@/components/content/ContentWidgets'
import StoryForm from '@/components/content/StoryForm'
import ListingLayout from '@/components/directory/ListingLayout'
import Flag from '@/components/ui/Flag'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { contentService } from '@/services/publicService'

export default function SuccessStoriesPage() {
  const { params, apiParams, setParams } = useFilterParams()
  const query = useQuery({ queryKey: ['stories', apiParams], queryFn: () => contentService.stories(apiParams), placeholderData: (p) => p })
  const meta = query.data?.meta
  const chip = (active) => cn('flex shrink-0 items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition', active ? 'bg-primary text-white shadow-glow' : 'text-slate-600 hover:bg-primary-50 hover:text-primary')

  return (
    <ListingLayout slug="success-stories">
      {({ sidebarBlocks }) => (
        <div className="grid gap-8 lg:grid-cols-[1fr_320px]">
          <div className="min-w-0">
            <div className="mb-6 flex flex-col gap-3 2xl:flex-row 2xl:items-center">
              <div className="flex flex-1 gap-1 overflow-x-auto" role="tablist" aria-label="Country">
                <button type="button" role="tab" aria-selected={!params.destination} className={chip(!params.destination)} onClick={() => setParams({ destination: '' })}>All Stories</button>
                {(meta?.destinations ?? []).map((d) => (
                  <button key={d.value} type="button" role="tab" aria-selected={params.destination === d.value} className={chip(params.destination === d.value)} onClick={() => setParams({ destination: d.value })}>
                    <Flag code={d.country_code} className="h-3.5 w-5" /> {d.label}
                  </button>
                ))}
              </div>
              <SearchBar className="2xl:w-64" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search success stories…" />
            </div>
            {query.isPending ? (
              <LoadingSkeleton count={6} />
            ) : query.isError ? (
              <ErrorState error={query.error} onRetry={query.refetch} />
            ) : !query.data.data.length ? (
              <EmptyState title="No stories found" description="Try another country or search term." />
            ) : (
              <>
                <div className={cn('grid gap-5 sm:grid-cols-2 xl:grid-cols-3', query.isFetching && 'opacity-60')}>
                  {query.data.data.map((s) => <StoryCard key={s.id} story={s} />)}
                </div>
                <Pagination className="mt-8" meta={meta} onPageChange={(page) => setParams({ page })} />
              </>
            )}
          </div>
          <aside className="space-y-6">
            <StoryForm destinations={meta?.destinations ?? []} />
            <SidebarBlocks blocks={sidebarBlocks} />
          </aside>
        </div>
      )}
    </ListingLayout>
  )
}
