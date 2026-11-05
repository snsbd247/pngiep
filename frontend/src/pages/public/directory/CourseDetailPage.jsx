import { useQuery } from '@tanstack/react-query'
import { ArrowRight, BookOpen, CalendarDays, Clock, GraduationCap, Languages, Landmark, MapPin, Wallet } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { CourseCard, InfoChip } from '@/components/directory/Cards'
import EnquiryForm from '@/components/directory/EnquiryForm'
import { CheckList, QuickInfo, SidebarCard } from '@/components/directory/SidebarCards'
import Breadcrumb from '@/components/layout/Breadcrumb'
import SectionHeader from '@/components/sections/SectionHeader'
import Seo from '@/components/Seo'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import { studyLevelLabel } from '@/config/studyLevels'
import { useSite } from '@/context/SiteContext'
import { assetUrl, formatMoney } from '@/lib/utils'
import { directoryService } from '@/services/publicService'
import { DetailQuery, ListingCta } from './shared'

const PERIOD = { year: 'per year', semester: 'per semester', total: 'total' }

export default function CourseDetailPage() {
  const { slug } = useParams()
  const { setting } = useSite()
  const query = useQuery({ queryKey: ['course', slug], queryFn: () => directoryService.course(slug) })

  return (
    <DetailQuery query={query}>
      {(c) => {
        const u = c.university
        const fee = c.tuition_fee ? `${formatMoney(c.tuition_fee, c.currency_code)} ${PERIOD[c.fee_period] ?? ''}` : null
        const hero = c.image || u?.cover_image
        return (
          <>
            <Seo title={`${c.name} – ${u?.name ?? ''}`} description={c.short_description} image={hero} seo={c.seo} />
            <section className="relative isolate overflow-hidden bg-secondary">
              {hero && <img src={assetUrl(hero)} alt="" className="absolute inset-0 -z-20 size-full object-cover" fetchPriority="high" />}
              <div className="absolute inset-0 -z-10 bg-gradient-to-r from-[#0b0726]/92 via-[#0b0726]/70 to-[#0b0726]/30" aria-hidden="true" />
              <div className="container-page py-12 md:py-16">
                <Breadcrumb light className="mb-5" items={[{ label: 'Courses', to: '/courses' }, { label: c.name }]} />
                <span className="mb-3 inline-flex rounded-md bg-white/90 px-2.5 py-1 text-xs font-bold text-primary">{studyLevelLabel(c.study_level)}</span>
                <h1 className="max-w-3xl text-3xl text-white sm:text-4xl">{c.name}</h1>
                {u && (
                  <p className="mt-2 flex items-center gap-2 text-white/85">
                    <Link to={`/universities/${u.slug}`} className="font-medium hover:underline">{u.name}</Link>
                    {c.destination && <><span className="opacity-50">·</span><Flag code={c.destination.country_code} className="h-3.5 w-5" /> {c.destination.name}</>}
                  </p>
                )}
                <div className="mt-5 flex flex-wrap gap-2">
                  <InfoChip icon={Clock}>{c.duration}</InfoChip>
                  <InfoChip icon={Wallet}>{fee}</InfoChip>
                  <InfoChip icon={CalendarDays}>{c.intakes?.join(', ')}</InfoChip>
                </div>
                <div className="mt-7 flex flex-wrap gap-3">
                  <Button to={`/apply?course=${c.slug}`} size="lg">{setting('forms.apply_button', 'Apply')} <ArrowRight className="size-4" /></Button>
                  <Button variant="white" size="lg" onClick={() => document.getElementById('enquiry')?.scrollIntoView({ behavior: 'smooth' })}>{setting('forms.enquiry_button')}</Button>
                </div>
              </div>
            </section>

            <div className="container-page grid gap-8 py-10 lg:grid-cols-[1fr_340px]">
              <div className="min-w-0 space-y-8">
                {c.description && (
                  <section>
                    <SectionHeader title="Course *Overview*" className="!mb-3" />
                    <div className="prose-content" dangerouslySetInnerHTML={{ __html: c.description }} />
                  </section>
                )}
                <section>
                  <SectionHeader title="Key *Information*" className="!mb-3" />
                  <dl className="grid overflow-hidden rounded-xl border border-slate-200 sm:grid-cols-2">
                    {[
                      ['Study Level', studyLevelLabel(c.study_level)], ['Degree', c.degree_type], ['Subject', c.subject], ['Duration', c.duration],
                      ['Tuition Fee', fee], ['Intakes', c.intakes?.join(', ')], ['IELTS Requirement', c.ielts_requirement && `${c.ielts_requirement} overall`],
                      ['Application Deadline', c.application_deadline],
                    ].filter(([, v]) => v).map(([label, value]) => (
                      <div key={label} className="border-b border-slate-100 bg-white px-4 py-3 sm:odd:border-r">
                        <dt className="text-xs text-slate-500">{label}</dt>
                        <dd className="text-sm font-semibold text-ink">{value}</dd>
                      </div>
                    ))}
                  </dl>
                </section>
                {c.entry_requirements && (
                  <section>
                    <SectionHeader title="Entry *Requirements*" className="!mb-3" />
                    <div className="prose-content" dangerouslySetInnerHTML={{ __html: c.entry_requirements }} />
                  </section>
                )}
              </div>
              <aside className="space-y-6">
                <div id="enquiry" className="scroll-mt-28">
                  <EnquiryForm titleKey="enquiry_course_title" name={c.name} context={{ course: c.slug }} intakes={c.intakes ?? []} />
                </div>
                <CheckList title="Course Highlights" items={c.highlights ?? []} />
                {u && (
                  <SidebarCard title="About the University">
                    <Link to={`/universities/${u.slug}`} className="flex items-center gap-3">
                      <span className="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-white p-1.5 shadow-sm">
                        {u.logo ? <img src={assetUrl(u.logo)} alt="" className="max-h-full max-w-full object-contain" /> : <Landmark className="size-6 text-primary" />}
                      </span>
                      <span className="font-semibold text-ink hover:text-primary">{u.name}</span>
                    </Link>
                    {u.short_description && <p className="mt-3 text-sm text-slate-600">{u.short_description}</p>}
                    <QuickInfo rows={[
                      { icon: MapPin, label: 'Location', value: [u.city, c.destination?.name].filter(Boolean).join(', ') },
                      { icon: GraduationCap, label: 'World Ranking', value: u.world_ranking && `#${u.world_ranking}` },
                      { icon: Languages, label: 'Type', value: u.type && `${u.type[0].toUpperCase()}${u.type.slice(1)}` },
                    ]} />
                    <Button to={`/universities/${u.slug}`} variant="outline-primary" size="sm" className="mt-4 w-full"><BookOpen className="size-4" /> View University</Button>
                  </SidebarCard>
                )}
              </aside>
            </div>

            {c.related?.length > 0 && (
              <section className="pb-12">
                <div className="container-page">
                  <SectionHeader title="Related *Courses*" linkText="View All Courses" linkUrl="/courses" />
                  <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {c.related.map((r) => <CourseCard key={r.id} course={r} />)}
                  </div>
                </div>
              </section>
            )}
            <ListingCta slug="courses" />
          </>
        )
      }}
    </DetailQuery>
  )
}
