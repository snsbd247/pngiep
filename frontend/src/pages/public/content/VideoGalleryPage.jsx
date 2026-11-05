import { useQuery } from '@tanstack/react-query'
import { Clock } from 'lucide-react'
import { VideoCard } from '@/components/cards'
import { CategoryTabs } from '@/components/content/ContentWidgets'
import ListingLayout from '@/components/directory/ListingLayout'
import Pagination from '@/components/ui/Pagination'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { contentService } from '@/services/publicService'

export default function VideoGalleryPage() {
  const { params, apiParams, setParams } = useFilterParams()
  const query = useQuery({ queryKey: ['gallery-videos', apiParams], queryFn: () => contentService.videos(apiParams), placeholderData: (p) => p })
  const categories = query.data?.meta?.categories ?? []
  const total = categories.reduce((n, c) => n + c.count, 0)

  return (
    <ListingLayout
      slug="video-gallery"
      belowHero={categories.length > 0 && (
        <CategoryTabs allLabel="All Videos" allIcon="circle-play" allCount={total} options={categories} value={params.category ?? ''} onChange={(category) => setParams({ category })} />
      )}
    >
      {query.isPending ? (
        <LoadingSkeleton count={6} />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !query.data.data.length ? (
        <EmptyState title="No videos yet" />
      ) : (
        <>
          <div className={cn('grid gap-6 sm:grid-cols-2 lg:grid-cols-3', query.isFetching && 'opacity-60')}>
            {query.data.data.map((v) => (
              <div key={v.id}>
                <VideoCard video={v} />
                {v.duration && <p className="mt-1 flex items-center gap-1 text-xs text-slate-500"><Clock className="size-3.5" /> {v.duration}</p>}
              </div>
            ))}
          </div>
          <Pagination className="mt-8" meta={query.data.meta} onPageChange={(page) => setParams({ page })} />
        </>
      )}
    </ListingLayout>
  )
}
