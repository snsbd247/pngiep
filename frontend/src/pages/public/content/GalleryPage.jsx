import { useQuery } from '@tanstack/react-query'
import { PlayCircle } from 'lucide-react'
import { useState } from 'react'
import { AlbumCard, CategoryList, CategoryTabs, SidebarBlocks } from '@/components/content/ContentWidgets'
import ListingLayout from '@/components/directory/ListingLayout'
import HighlightText from '@/components/ui/HighlightText'
import Lightbox from '@/components/ui/Lightbox'
import Pagination from '@/components/ui/Pagination'
import SearchBar from '@/components/ui/SearchBar'
import SmartLink from '@/components/ui/SmartLink'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useToast } from '@/components/ui/Toast'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { contentService } from '@/services/publicService'

export default function GalleryPage() {
  const toast = useToast()
  const { params, apiParams, setParams } = useFilterParams()
  const query = useQuery({ queryKey: ['gallery-photos', apiParams], queryFn: () => contentService.photos(apiParams), placeholderData: (p) => p })
  const categories = query.data?.meta?.categories ?? []
  const total = categories.reduce((n, c) => n + c.count, 0)
  const [viewer, setViewer] = useState({ images: [], index: null })

  const openAlbum = async (album) => {
    try {
      const data = await contentService.album(album.slug)
      setViewer({ images: data.images, index: 0 })
    } catch (e) {
      toast.error(e.message)
    }
  }

  return (
    <>
    <Lightbox images={viewer.images} index={viewer.index} onChange={(index) => setViewer((v) => ({ ...v, index }))} onClose={() => setViewer({ images: [], index: null })} />
    <ListingLayout
      slug="gallery"
      headingInside
      belowHero={categories.length > 0 && (
        <CategoryTabs allLabel="All Photos" allIcon="images" allCount={total} options={categories} value={params.category ?? ''} onChange={(category) => setParams({ category })} />
      )}
    >
      {({ sidebarBlocks, heading }) => (
        <div className="grid gap-8 lg:grid-cols-[1fr_320px]">
          <div className="min-w-0">
            <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              {heading?.title && <h2 className="text-2xl sm:text-3xl"><HighlightText text={heading.title} /></h2>}
              <SearchBar className="sm:w-72" value={params.search ?? ''} onSearch={(search) => setParams({ search })} placeholder="Search gallery…" />
            </div>
            {query.isPending ? (
              <LoadingSkeleton count={6} />
            ) : query.isError ? (
              <ErrorState error={query.error} onRetry={query.refetch} />
            ) : !query.data.data.length ? (
              <EmptyState title="No albums found" description="Try another category or search term." />
            ) : (
              <>
                <div className={cn('grid gap-5 sm:grid-cols-2 xl:grid-cols-3', query.isFetching && 'opacity-60')}>
                  {query.data.data.map((a) => <AlbumCard key={a.id} album={a} onOpen={() => openAlbum(a)} />)}
                </div>
                <Pagination className="mt-8" meta={query.data.meta} onPageChange={(page) => setParams({ page })} />
              </>
            )}
          </div>
          <aside className="space-y-6">
            {sidebarBlocks.slice(0, 1).map((b) => <SidebarBlocks key={b.id} blocks={[b]} />)}
            <CategoryList title="Gallery Categories" allLabel="All Photos" allCount={total} options={categories.map((c) => ({ ...c }))} value={params.category ?? ''} onSelect={(category) => setParams({ category })} />
            <SmartLink to="/gallery/videos" className="flex items-center gap-3 rounded-2xl border border-slate-100 bg-lavender p-4 text-sm font-semibold text-primary hover:bg-primary-50">
              <PlayCircle className="size-6" /> Video Gallery
            </SmartLink>
            <SidebarBlocks blocks={sidebarBlocks.slice(1)} />
          </aside>
        </div>
      )}
    </ListingLayout>
    </>
  )
}
