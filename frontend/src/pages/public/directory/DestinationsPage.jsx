import { useQuery } from '@tanstack/react-query'
import { DestinationListCard } from '@/components/directory/Cards'
import ListingLayout from '@/components/directory/ListingLayout'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { directoryService } from '@/services/publicService'

export default function DestinationsPage() {
  const { params, apiParams, setParams } = useFilterParams()
  const query = useQuery({ queryKey: ['destinations', apiParams], queryFn: () => directoryService.destinations(apiParams), placeholderData: (p) => p })

  return (
    <ListingLayout slug="destinations">
      <SearchBar className="mb-8 max-w-md" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search destinations…" />
      {query.isPending ? (
        <LoadingSkeleton count={6} />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !query.data.data.length ? (
        <EmptyState title="No destinations found" description="Try a different search." />
      ) : (
        <>
          <div className={cn('grid gap-6 sm:grid-cols-2 lg:grid-cols-3', query.isFetching && 'opacity-60')}>
            {query.data.data.map((d) => (
              <DestinationListCard key={d.id} destination={d} />
            ))}
          </div>
          <Pagination className="mt-8" meta={query.data.meta} onPageChange={(page) => setParams({ page })} />
        </>
      )}
    </ListingLayout>
  )
}
