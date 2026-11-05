import { useQuery } from '@tanstack/react-query'
import { ArrowRight, Building2, CalendarDays, ExternalLink, Globe, Landmark, MapPin, Users } from 'lucide-react'
import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { blockAnchor } from '@/components/blocks/PageBlocks'
import { VideoCard } from '@/components/cards'
import { UniversityCard } from '@/components/directory/Cards'
import EnquiryForm from '@/components/directory/EnquiryForm'
import { CheckList, LocationMap, QuickInfo } from '@/components/directory/SidebarCards'
import Breadcrumb from '@/components/layout/Breadcrumb'
import SectionHeader from '@/components/sections/SectionHeader'
import StatsBar from '@/components/sections/StatsBar'
import Seo from '@/components/Seo'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import { stripHighlight } from '@/components/ui/HighlightText'
import { useSite } from '@/context/SiteContext'
import { assetUrl, cn, formatCount } from '@/lib/utils'
import { directoryService } from '@/services/publicService'
import { DetailBody, DetailQuery } from './shared'

function Hero({ u }) {
  const { setting } = useSite()
  return (
    <section className="relative isolate overflow-hidden bg-secondary">
      {u.cover_image && <img src={assetUrl(u.cover_image)} alt="" className="absolute inset-0 -z-20 size-full object-cover" fetchPriority="high" />}
      <div className="absolute inset-0 -z-10 bg-gradient-to-r from-[#0b0726]/92 via-[#0b0726]/65 to-[#0b0726]/20" aria-hidden="true" />
      <div className="container-page pt-12 pb-24 md:pt-14 md:pb-28">
        <Breadcrumb light className="mb-6" items={[{ label: 'Universities', to: '/universities' }, { label: u.name }]} />
        <div className="flex flex-col gap-6 sm:flex-row sm:items-center">
          <span className="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl bg-white p-2 shadow-card sm:size-28">
            {u.logo ? <img src={assetUrl(u.logo)} alt={`${u.name} logo`} className="max-h-full max-w-full object-contain" /> : <Landmark className="size-10 text-primary" />}
          </span>
          <div className="max-w-3xl">
            <h1 className="text-3xl text-white sm:text-4xl lg:text-[2.75rem]">{u.name}</h1>
            <p className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white/85">
              {u.destination && <span className="flex items-center gap-1.5"><Flag code={u.destination.country_code} className="h-3.5 w-5" /> {u.destination.name}</span>}
              <span className="opacity-50">|</span>
              <span className="capitalize">{u.type} University</span>
              {u.established_year && <><span className="opacity-50">|</span><span>Established {u.established_year}</span></>}
            </p>
            {u.short_description && <p className="mt-3 text-sm text-white/75">{u.short_description}</p>}
            <div className="mt-6 flex flex-wrap gap-3">
              <Button to={`/apply?university=${u.slug}`}>{setting('forms.apply_button', 'Apply')} <ArrowRight className="size-4" /></Button>
              {u.website && <Button to={u.website} target="_blank" variant="white">Visit Official Website <ExternalLink className="size-4" /></Button>}
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}

/** Sticky in-page navigation built from the section titles. */
function SectionTabs({ sections }) {
  const tabs = sections.filter((s) => s.title && !['cta', 'faq'].includes(s.type))
  if (tabs.length < 3) return null
  return (
    <nav aria-label="On this page" className="sticky top-16 z-20 -mx-1 mb-2 overflow-x-auto bg-white/95 px-1 py-3 backdrop-blur lg:top-[76px]">
      <ul className="flex gap-2">
        {tabs.map((s) => (
          <li key={s.id}>
            <a href={`#${blockAnchor(s)}`} className="block rounded-lg border border-slate-200 px-3.5 py-2 text-xs font-semibold whitespace-nowrap text-slate-600 hover:border-primary hover:text-primary">
              {stripHighlight(s.title)}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  )
}

function About({ u }) {
  const [expanded, setExpanded] = useState(false)
  if (!u.about && !u.youtube_id) return null
  return (
    <section className="grid gap-6 py-6 md:grid-cols-[1fr_280px]">
      <div>
        <SectionHeader title="About the University" className="!mb-3" />
        {u.about && (
          <>
            <div className={cn('prose-content text-sm', !expanded && 'line-clamp-[8]')} dangerouslySetInnerHTML={{ __html: u.about }} />
            <button type="button" onClick={() => setExpanded((e) => !e)} className="mt-2 text-sm font-semibold text-primary">{expanded ? 'Show Less' : 'Show More'}</button>
          </>
        )}
      </div>
      {u.youtube_id && <VideoCard video={{ youtube_id: u.youtube_id, title: u.name }} className="self-start" />}
    </section>
  )
}

export default function UniversityDetailPage() {
  const { slug } = useParams()
  const query = useQuery({ queryKey: ['university', slug], queryFn: () => directoryService.university(slug) })

  return (
    <DetailQuery query={query}>
      {(u) => {
        const location = [u.city, u.destination?.name].filter(Boolean).join(', ')
        const courseNames = (u.sections.find((s) => s.type === 'related_courses')?.courses ?? []).map((c) => c.name)
        const stats = [
          u.world_ranking && { id: 1, icon: 'trophy', value: `#${u.world_ranking}`, label: 'World Ranking' },
          u.national_ranking && { id: 2, icon: 'landmark', value: `#${u.national_ranking}`, label: `${u.destination?.name ?? ''} Ranking` },
          u.student_count && { id: 3, icon: 'users', value: formatCount(u.student_count), label: 'Total Students' },
          u.international_student_count && { id: 4, icon: 'earth', value: formatCount(u.international_student_count), label: 'International Students' },
          u.courses_count && { id: 5, icon: 'briefcase-business', value: formatCount(u.courses_count), label: 'Courses Offered' },
        ].filter(Boolean)

        return (
          <>
            <Seo title={u.name} description={u.short_description} image={u.cover_image} seo={u.seo} />
            <Hero u={u} />
            <StatsBar items={stats.slice(0, 4)} overlap />
            <DetailBody
              sections={u.sections}
              intro={
                <>
                  <About u={u} />
                  <SectionTabs sections={u.sections} />
                </>
              }
              sidebar={
                <>
                  <EnquiryForm titleKey="enquiry_university_title" name={u.name} context={{ university: u.slug }} intakes={u.intakes ?? []} courseOptions={courseNames} />
                  <QuickInfo
                    title="Quick Information"
                    rows={[
                      { icon: MapPin, label: 'Location', value: location },
                      { icon: Landmark, label: 'Type', value: u.type && `${u.type[0].toUpperCase()}${u.type.slice(1)} University` },
                      { icon: CalendarDays, label: 'Established', value: u.established_year },
                      { icon: Users, label: 'Total Students', value: u.student_count && formatCount(u.student_count) },
                      { icon: Globe, label: 'International Students', value: u.international_student_count && formatCount(u.international_student_count) },
                      { icon: Building2, label: 'Campus', value: u.campus_type },
                      { icon: ExternalLink, label: 'Website', value: u.website?.replace(/^https?:\/\//, ''), href: u.website },
                    ]}
                  />
                  <CheckList title={`Why Choose ${u.name.replace(/^(The )?University of /, '')}?`} items={u.highlights ?? []} />
                  <LocationMap title="Location" query={location && `${u.name}, ${location}`} />
                </>
              }
              beforeBottom={
                u.related?.length > 0 && (
              <section className="section !pt-0">
                <div className="container-page">
                  <SectionHeader title={`More Universities in *${u.destination?.name ?? ''}*`} linkText="View All" linkUrl={`/universities?destination[]=${u.destination?.slug}`} />
                  <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {u.related.map((r) => <UniversityCard key={r.id} university={r} />)}
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
