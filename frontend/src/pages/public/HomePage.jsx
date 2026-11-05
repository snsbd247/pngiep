import { useQuery } from '@tanstack/react-query'
import Seo from '@/components/Seo'
import { ErrorState, LoadingSkeleton, Skeleton } from '@/components/ui/States'
import { publicService } from '@/services/publicService'
import { HOME_SECTIONS } from './home/HomeSections'

function HomeSkeleton() {
  return (
    <div aria-busy="true" aria-label="Loading">
      <Skeleton className="h-[480px] w-full rounded-none" />
      <div className="container-page -mt-10 space-y-10">
        <Skeleton className="h-28 w-full rounded-2xl" />
        <LoadingSkeleton count={5} className="lg:grid-cols-5" />
      </div>
    </div>
  )
}

/** Homepage: renders the admin-ordered, enabled sections from GET /homepage. */
export default function HomePage() {
  const query = useQuery({ queryKey: ['homepage'], queryFn: publicService.homepage })

  if (query.isPending) return <HomeSkeleton />
  if (query.isError) {
    return (
      <div className="container-page py-20">
        <ErrorState error={query.error} onRetry={query.refetch} />
      </div>
    )
  }

  const sections = query.data.sections
  const context = { destinations: sections.find((s) => s.key === 'destinations')?.destinations ?? [] }

  return (
    <>
      <Seo />
      {sections.map((section, i) => {
        const Component = HOME_SECTIONS[section.key]
        return Component ? <Component key={section.key} section={section} previousKey={sections[i - 1]?.key} context={context} /> : null
      })}
    </>
  )
}
