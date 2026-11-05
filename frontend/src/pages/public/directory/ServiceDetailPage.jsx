import { useQuery } from '@tanstack/react-query'
import { ArrowRight, CheckCircle2 } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { toneAt } from '@/components/cards'
import EnquiryForm from '@/components/directory/EnquiryForm'
import { SidebarCard } from '@/components/directory/SidebarCards'
import HeroBanner from '@/components/sections/HeroBanner'
import Seo from '@/components/Seo'
import AppIcon from '@/components/ui/AppIcon'
import Button from '@/components/ui/Button'
import { cn } from '@/lib/utils'
import { directoryService } from '@/services/publicService'
import { DetailBody, DetailQuery, ListingCta } from './shared'

export default function ServiceDetailPage() {
  const { slug } = useParams()
  const query = useQuery({ queryKey: ['service', slug], queryFn: () => directoryService.service(slug) })

  return (
    <DetailQuery query={query}>
      {(s) => (
        <>
          <Seo title={s.title} description={s.short_description} image={s.image} seo={s.seo} />
          <HeroBanner title={s.title} text={s.short_description} image={s.image} breadcrumbs={[{ label: 'Services', to: '/services' }, { label: s.title }]} />
          <DetailBody
            sections={s.sections}
            intro={
              <div className="space-y-6 py-6">
                {s.description && <div className="prose-content" dangerouslySetInnerHTML={{ __html: s.description }} />}
                {s.features?.length > 0 && (
                  <ul className="grid gap-3 sm:grid-cols-2">
                    {s.features.map((f) => (
                      <li key={f} className="flex items-start gap-2.5 rounded-xl border border-slate-100 bg-lavender p-4 text-sm font-medium text-ink">
                        <CheckCircle2 className="mt-0.5 size-4.5 shrink-0 text-primary" /> {f}
                      </li>
                    ))}
                  </ul>
                )}
                {s.cta_text && <Button to={s.cta_url}>{s.cta_text} <ArrowRight className="size-4" /></Button>}
              </div>
            }
            sidebar={
              <>
                <EnquiryForm titleKey="enquiry_service_title" name={s.title} context={{}} />
                {s.related?.length > 0 && (
                  <SidebarCard title="Other Services">
                    <ul className="space-y-2">
                      {s.related.map((r, i) => (
                        <li key={r.id}>
                          <Link to={`/services/${r.slug}`} className="flex items-center gap-3 rounded-xl bg-white p-2.5 text-sm font-medium text-ink shadow-sm hover:text-primary">
                            <span className={cn('grid size-9 shrink-0 place-items-center rounded-lg', toneAt(i))}><AppIcon name={r.icon} className="size-4" /></span>
                            {r.title}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </SidebarCard>
                )}
              </>
            }
          />
          <ListingCta slug="services" />
        </>
      )}
    </DetailQuery>
  )
}
