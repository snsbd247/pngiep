import { useQuery } from '@tanstack/react-query'
import { ArrowRight, BriefcaseBusiness, CalendarDays, Coins, Landmark, Languages, Wallet } from 'lucide-react'
import { DestinationCard, VideoCard } from '@/components/cards'
import EnquiryForm from '@/components/directory/EnquiryForm'
import { QuickInfo } from '@/components/directory/SidebarCards'
import Breadcrumb from '@/components/layout/Breadcrumb'
import SectionHeader from '@/components/sections/SectionHeader'
import StatsBar from '@/components/sections/StatsBar'
import Seo from '@/components/Seo'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import { useSite } from '@/context/SiteContext'
import { assetUrl, formatMoneyRange, youtubeId } from '@/lib/utils'
import { directoryService } from '@/services/publicService'
import { DetailBody, DetailQuery } from './shared'

function Hero({ d }) {
  const { setting } = useSite()
  return (
    <section className="relative isolate overflow-hidden bg-secondary">
      {d.hero_image && <img src={assetUrl(d.hero_image)} alt="" className="absolute inset-0 -z-20 size-full object-cover" fetchPriority="high" />}
      <div className="absolute inset-0 -z-10 bg-gradient-to-r from-[#0b0726]/90 via-[#0b0726]/55 to-transparent" aria-hidden="true" />
      <div className="container-page pt-12 pb-24 md:pt-16 md:pb-28">
        <Breadcrumb light className="mb-5" items={[{ label: 'Destinations', to: '/destinations' }, { label: d.name }]} />
        <span className="mb-3 inline-flex items-center gap-2 rounded-md bg-white/90 px-3 py-1 text-xs font-bold tracking-wider text-primary uppercase">
          Study in <Flag code={d.country_code} className="h-4 w-6" />
        </span>
        <h1 className="text-4xl text-white sm:text-5xl">{d.name}</h1>
        {d.tagline && <p className="mt-3 max-w-xl text-white/85">{d.tagline}</p>}
        <div className="mt-7 flex flex-wrap gap-3">
          <Button to={`/apply?destination=${d.slug}`} size="lg">{setting('forms.apply_button', 'Apply')} <ArrowRight className="size-4" /></Button>
          <Button variant="white" size="lg" onClick={() => document.getElementById('enquiry')?.scrollIntoView({ behavior: 'smooth', block: 'start' })}>
            {setting('forms.enquiry_button')}
          </Button>
        </div>
      </div>
    </section>
  )
}

export default function DestinationDetailPage({ slug }) {
  const query = useQuery({ queryKey: ['destination', slug], queryFn: () => directoryService.destination(slug) })

  return (
    <DetailQuery query={query}>
      {(d) => {
        const video = youtubeId(d.video_url)
        const facts = (d.quick_facts ?? []).map((f, i) => ({ id: i, icon: f.icon, value: f.value, label: f.label }))
        return (
          <>
            <Seo title={`${d.name}`} description={d.short_description || d.tagline} image={d.hero_image} seo={d.seo} />
            <Hero d={d} />
            <StatsBar items={facts} overlap />
            <DetailBody
              sections={d.sections}
              intro={d.overview && <div className="prose-content py-6" dangerouslySetInnerHTML={{ __html: d.overview }} />}
              sidebar={
                <>
                  <div id="enquiry" className="scroll-mt-28">
                    <EnquiryForm name={d.name} context={{ destination: d.slug }} intakes={d.intakes ?? []} />
                  </div>
                  <QuickInfo
                    title="Quick Information"
                    rows={[
                      { icon: Landmark, label: 'Capital', value: d.capital },
                      { icon: Languages, label: 'Language', value: d.language },
                      { icon: CalendarDays, label: 'Popular Intake', value: d.intakes?.join(', ') },
                      { icon: Wallet, label: 'Average Tuition Fees', value: formatMoneyRange(d.tuition_fee_min, d.tuition_fee_max, d.currency_code) && `${formatMoneyRange(d.tuition_fee_min, d.tuition_fee_max, d.currency_code)} per year` },
                      { icon: Coins, label: 'Living Cost', value: formatMoneyRange(d.living_cost_min, d.living_cost_max, d.currency_code) && `${formatMoneyRange(d.living_cost_min, d.living_cost_max, d.currency_code)} per year` },
                      { icon: BriefcaseBusiness, label: 'Post-Study Work Visa', value: d.post_study_work },
                    ]}
                  />
                  {video && <VideoCard video={{ youtube_id: video, title: d.name }} />}
                </>
              }
              beforeBottom={
                d.related?.length > 0 && (
              <section className="section !pt-0">
                <div className="container-page">
                  <SectionHeader title="Other *Destinations*" linkText="View All" linkUrl="/destinations" />
                  <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {d.related.map((r) => <DestinationCard key={r.id} destination={r} />)}
                  </div>
                </div>
              </section>
            )
              }
            />
          </>
        )
      }}
    </DetailQuery>
  )
}
