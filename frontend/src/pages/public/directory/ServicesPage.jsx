import { useQuery } from '@tanstack/react-query'
import { ArrowRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import { toneAt } from '@/components/cards'
import ListingLayout from '@/components/directory/ListingLayout'
import AppIcon from '@/components/ui/AppIcon'
import Image from '@/components/ui/Image'
import { EmptyState, ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { cn } from '@/lib/utils'
import { directoryService } from '@/services/publicService'

export function ServiceCard({ service, index }) {
  return (
    <Link to={`/services/${service.slug}`} className="group flex h-full flex-col rounded-xl border border-slate-100 bg-lavender p-4 shadow-card transition hover:-translate-y-1 hover:shadow-card-hover">
      <div className="flex items-start gap-3">
        <span className={cn('grid size-12 shrink-0 place-items-center rounded-xl', toneAt(index))}>
          <AppIcon name={service.icon} fallback="sparkles" className="size-6" />
        </span>
        <h3 className="pt-1 text-[15px] leading-snug font-semibold">{service.title}</h3>
      </div>
      {service.short_description && <p className="mt-3 text-sm leading-relaxed text-slate-600">{service.short_description}</p>}
      <Image src={service.image} alt="" className="mt-4 aspect-[16/9] w-full rounded-lg" />
      <span className="mt-4 flex items-center justify-between text-xs font-semibold text-primary">
        Learn More
        <span className="grid size-7 place-items-center rounded-full bg-primary-100 transition group-hover:bg-primary group-hover:text-white">
          <ArrowRight className="size-3.5" />
        </span>
      </span>
    </Link>
  )
}

export default function ServicesPage() {
  const query = useQuery({ queryKey: ['services'], queryFn: directoryService.services })

  return (
    <ListingLayout slug="services">
      {query.isPending ? (
        <LoadingSkeleton count={8} className="lg:grid-cols-4" />
      ) : query.isError ? (
        <ErrorState error={query.error} onRetry={query.refetch} />
      ) : !query.data.length ? (
        <EmptyState title="No services yet" />
      ) : (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {query.data.map((s, i) => <ServiceCard key={s.id} service={s} index={i} />)}
        </div>
      )}
    </ListingLayout>
  )
}
