import { useQuery } from '@tanstack/react-query'
import { CalendarCheck } from 'lucide-react'
import { UniversityCard } from '@/components/directory/Cards'
import FilterSidebar from '@/components/directory/FilterSidebar'
import ListingLayout from '@/components/directory/ListingLayout'
import FormSelect from '@/components/forms/FormSelect'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import Pagination from '@/components/ui/Pagination'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { useSite } from '@/context/SiteContext'
import { useFilterParams } from '@/hooks/useFilterParams'
import { cn } from '@/lib/utils'
import { directoryService } from '@/services/publicService'

const SORTS = [
  { value: 'featured', label: 'Featured' },
  { value: 'ranking', label: 'World Ranking' },
  { value: 'name', label: 'Name (A–Z)' },
  { value: 'fee_low', label: 'Tuition: Low to High' },
  { value: 'fee_high', label: 'Tuition: High to Low' },
]

/** Horizontal country chips (single-select shortcut for the destination filter). */
export function CountryTabs({ options = [], selected = [], onSelect }) {
  if (!options.length) return null
  const chip = (active) =>
    cn('flex shrink-0 items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition', active ? 'bg-primary text-white shadow-glow' : 'text-slate-600 hover:bg-primary-50 hover:text-primary')
  return (
    <div className="container-page relative z-10 -mt-7">
      <div className="flex gap-1 overflow-x-auto rounded-2xl border border-slate-100 bg-white p-2 shadow-card-hover">
        <button type="button" className={chip(!selected.length)} onClick={() => onSelect([])}>All</button>
        {options.map((o) => (
          <button key={o.value} type="button" className={chip(selected.length === 1 && selected[0] === o.value)} onClick={() => onSelect([o.value])}>
            <Flag code={o.country_code} className="h-3.5 w-5" /> {o.label}
          </button>
        ))}
      </div>
    </div>
  )
}

export default function UniversitiesPage() {
  const { setting } = useSite()
  const { params, apiParams, setParams, clear } = useFilterParams(['destination', 'type', 'subject', 'level'])
  const query = useQuery({ queryKey: ['universities', apiParams], queryFn: () => directoryService.universities(apiParams), placeholderData: (p) => p })
  const meta = query.data?.meta
  const facets = meta?.facets

  return (
    <>
      <ListingLayout
        slug="universities"
        belowHero={facets && <CountryTabs options={facets.destinations} selected={params.destination} onSelect={(destination) => setParams({ destination })} />}
      >
        <div className="grid gap-8 lg:grid-cols-[270px_1fr]">
          <FilterSidebar
            title="Search Universities"
            search={params.search}
            onSearch={(search) => setParams({ search })}
            searchPlaceholder="Search by university name…"
            params={params}
            onChange={setParams}
            onClear={clear}
            groups={facets ? [
              { key: 'destination', title: 'Destination', options: facets.destinations },
              { key: 'type', title: 'University Type', options: facets.types },
              { key: 'subject', title: 'Subject Area', options: facets.subjects },
              { key: 'level', title: 'Degree Level', options: facets.levels },
            ] : []}
            range={facets && { title: 'Tuition Fee Range (Per Year)', max: facets.fee_range.max, value: params.fee_max ? Number(params.fee_max) : null, onChange: (fee_max) => setParams({ fee_max }) }}
            footer={
              <>
                <Button to="/apply" className="w-full">{setting('forms.apply_button', 'Apply')}</Button>
                <Button to="/book-appointment" variant="secondary" className="w-full"><CalendarCheck className="size-4" /> {setting('forms.enquiry_button')}</Button>
              </>
            }
          />
          <div className="min-w-0">
            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
              <p className="text-sm text-slate-500" aria-live="polite">
                {meta ? (meta.total ? `Showing ${meta.from} – ${meta.to} of ${meta.total} universities` : '') : '…'}
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
              <EmptyState title="No universities match your filters" description="Try removing a filter or searching for something else." action={<Button variant="outline" size="sm" onClick={clear}>Clear filters</Button>} />
            ) : (
              <>
                <div className={cn('grid gap-5 sm:grid-cols-2 xl:grid-cols-3', query.isFetching && 'opacity-60')}>
                  {query.data.data.map((u) => <UniversityCard key={u.id} university={u} />)}
                </div>
                <Pagination className="mt-8" meta={meta} onPageChange={(page) => setParams({ page })} />
              </>
            )}
          </div>
        </div>
      </ListingLayout>
    </>
  )
}
