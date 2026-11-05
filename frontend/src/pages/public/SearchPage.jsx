import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Search } from 'lucide-react'
import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import HeroBanner from '@/components/sections/HeroBanner'
import Seo from '@/components/Seo'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import Image from '@/components/ui/Image'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { publicService } from '@/services/publicService'

const GROUP_TITLES = { destinations: 'Destinations', universities: 'Universities', courses: 'Courses', services: 'Services', success_stories: 'Success Stories', blog: 'Blog & Notices', pages: 'Pages' }

export default function SearchPage() {
  const [params, setParams] = useSearchParams()
  const q = (params.get('q') ?? '').trim()
  const [term, setTerm] = useState(q)
  const query = useQuery({ queryKey: ['search', q], queryFn: () => publicService.search(q), enabled: q.length >= 2 })
  const groups = query.data?.data ?? {}

  return (
    <>
      <Seo title={q ? `Search: ${q}` : 'Search'} noindex />
      <HeroBanner title="Search" breadcrumbs={[{ label: 'Search' }]}>
        <form
          role="search"
          className="container-page -mt-8 pb-12 md:-mt-12"
          onSubmit={(e) => {
            e.preventDefault()
            setParams(term.trim() ? { q: term.trim() } : {})
          }}
        >
          <div className="flex max-w-2xl gap-2 rounded-2xl bg-white p-2 shadow-2xl">
            <Search className="mt-3.5 ml-3 size-5 shrink-0 text-slate-400" />
            <input value={term} onChange={(e) => setTerm(e.target.value)} type="search" aria-label="Search" placeholder="Search destinations, universities, courses…" className="h-12 min-w-0 flex-1 bg-transparent text-base focus:outline-none" />
            <Button type="submit" size="lg">Search</Button>
          </div>
        </form>
      </HeroBanner>

      <section className="section">
        <div className="container-page">
          {q.length < 2 ? (
            <EmptyState icon={Search} title="What are you looking for?" description="Type at least 2 characters." />
          ) : query.isPending ? (
            <LoadingSkeleton variant="table" />
          ) : query.isError ? (
            <ErrorState error={query.error} onRetry={query.refetch} />
          ) : !query.data.meta.total ? (
            <EmptyState icon={Search} title={`No results for “${q}”`} description="Check the spelling or try a broader term." />
          ) : (
            <div className="space-y-10">
              <p className="text-sm text-slate-500">{query.data.meta.total} results for “<span className="font-semibold text-ink">{q}</span>”</p>
              {Object.entries(groups).map(([key, items]) => (
                <section key={key}>
                  <h2 className="mb-4 text-xl">{GROUP_TITLES[key] ?? key}</h2>
                  <ul className="grid gap-3 md:grid-cols-2">
                    {items.map((item) => (
                      <li key={`${key}-${item.id}`}>
                        <Link to={item.url} className="group flex items-center gap-4 rounded-xl border border-slate-100 bg-white p-3 shadow-card hover:border-primary-200">
                          <Image src={item.image} alt="" className="size-16 shrink-0 rounded-lg object-cover" />
                          <span className="min-w-0 flex-1">
                            <span className="flex items-center gap-2 font-semibold text-ink group-hover:text-primary">
                              {item.country_code && <Flag code={item.country_code} className="h-3 w-4" />} {item.title}
                            </span>
                            {item.text && <span className="line-clamp-1 text-sm text-slate-500">{item.text}</span>}
                          </span>
                          <ArrowRight className="size-4 shrink-0 text-primary" />
                        </Link>
                      </li>
                    ))}
                  </ul>
                </section>
              ))}
            </div>
          )}
        </div>
      </section>
    </>
  )
}
