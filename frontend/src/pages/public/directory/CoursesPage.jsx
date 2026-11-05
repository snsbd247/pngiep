import { useQuery } from '@tanstack/react-query'
import { CourseCard } from '@/components/directory/Cards'
import FilterSidebar from '@/components/directory/FilterSidebar'
import ListingLayout from '@/components/directory/ListingLayout'
import FormSelect from '@/components/forms/FormSelect'
import Button from '@/components/ui/Button'
import Pagination from '@/components/ui/Pagination'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useSite } from '@/context/SiteContext'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { directoryService } from '@/services/publicService'
import { CountryTabs } from './UniversitiesPage'

const SORTS = [
  { value: 'featured', label: 'Featured' },
  { value: 'name', label: 'Name (A–Z)' },
  { value: 'fee_low', label: 'Tuition: Low to High' },
  { value: 'fee_high', label: 'Tuition: High to Low' },
  { value: 'duration', label: 'Shortest Duration' },
]

const DURATIONS = [
  { value: '12', label: 'Up to 1 year' },
  { value: '24', label: 'Up to 2 years' },
  { value: '36', label: 'Up to 3 years' },
  { value: '48', label: 'Up to 4 years' },
]

export default function CoursesPage() {
  const { setting } = useSite()
  const { params, apiParams, setParams, clear } = useFilterParams(['destination', 'university', 'level', 'subject', 'degree'])
  const query = useQuery({ queryKey: ['courses', apiParams], queryFn: () => directoryService.courses(apiParams), placeholderData: (p) => p })
  const meta = query.data?.meta
  const facets = meta?.facets

  return (
    <ListingLayout
      slug="courses"
      belowHero={facets && <CountryTabs options={facets.destinations} selected={params.destination} onSelect={(destination) => setParams({ destination })} />}
    >
      <div className="grid gap-8 lg:grid-cols-[270px_1fr]">
        <FilterSidebar
          title="Search Courses"
          search={params.search}
          onSearch={(search) => setParams({ search })}
          searchPlaceholder="Course, subject or degree…"
          params={params}
          onChange={setParams}
          onClear={clear}
          groups={facets ? [
            { key: 'level', title: 'Study Level', options: facets.levels },
            { key: 'subject', title: 'Subject Area', options: facets.subjects },
            { key: 'degree', title: 'Degree Type', options: facets.degrees },
            { key: 'destination', title: 'Destination', options: facets.destinations },
          ] : []}
          range={facets && { title: 'Tuition Fee (Per Year)', max: facets.fee_range.max, value: params.fee_max ? Number(params.fee_max) : null, onChange: (fee_max) => setParams({ fee_max }) }}
          footer={
            <>
              <FormSelect aria-label="Duration" placeholder="Any duration" value={params.duration_max ?? ''} onChange={(e) => setParams({ duration_max: e.target.value })} options={DURATIONS} />
              <Button to="/book-appointment" className="w-full">{setting('forms.enquiry_button')}</Button>
            </>
          }
        />
        <div className="min-w-0">
          {params.university?.length > 0 && (
            <p className="mb-3 text-sm text-slate-600">
              Filtered by university · <button type="button" className="font-semibold text-primary" onClick={() => setParams({ university: [] })}>show all</button>
            </p>
          )}
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-slate-500" aria-live="polite">
              {meta ? (meta.total ? `Showing ${meta.from} – ${meta.to} of ${meta.total} courses` : '') : '…'}
            </p>
            <label className="flex items-center gap-2 text-sm text-slate-500">
              Sort by:
              <FormSelect className="w-48" aria-label="Sort by" value={params.sort ?? 'featured'} onChange={(e) => setParams({ sort: e.target.value })} options={SORTS} />
            </label>
          </div>
          {query.isPending ? (
            <LoadingSkeleton count={6} className="xl:grid-cols-3" />
          ) : query.isError ? (
            <ErrorState error={query.error} onRetry={query.refetch} />
          ) : !query.data.data.length ? (
            <EmptyState title="No courses match your filters" description="Try removing a filter or searching for something else." action={<Button variant="outline" size="sm" onClick={clear}>Clear filters</Button>} />
          ) : (
            <>
              <div className={cn('grid gap-5 sm:grid-cols-2 xl:grid-cols-3', query.isFetching && 'opacity-60')}>
                {query.data.data.map((c) => <CourseCard key={c.id} course={c} />)}
              </div>
              <Pagination className="mt-8" meta={meta} onPageChange={(page) => setParams({ page })} />
            </>
          )}
        </div>
      </div>
    </ListingLayout>
  )
}
