import { useQuery } from '@tanstack/react-query'
import { ArrowRight, BadgeCheck, BookOpen, CalendarDays, CheckCircle2, ChevronRight, Earth, GraduationCap, Landmark, Quote, User } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { VideoCard } from '@/components/cards'
import { StoryCard } from '@/components/content/ContentWidgets'
import { QuickInfo, SidebarCard } from '@/components/directory/SidebarCards'
import Breadcrumb from '@/components/layout/Breadcrumb'
import SectionHeader from '@/components/sections/SectionHeader'
import Seo from '@/components/Seo'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import Image from '@/components/ui/Image'
import { PhotoGrid } from '@/components/blocks/shared'
import { studyLevelLabel } from '@/config/studyLevels'
import { assetUrl } from '@/lib/utils'
import { contentService } from '@/services/publicService'
import { DetailQuery, ListingCta } from '../directory/shared'

export default function SuccessStoryDetailPage() {
  const { slug } = useParams()
  const query = useQuery({ queryKey: ['story', slug], queryFn: () => contentService.story(slug) })

  return (
    <DetailQuery query={query}>
      {(s) => {
        const uni = s.university
        const universityName = uni?.name || s.university_name
        const course = s.course?.name || s.course_name
        const gallery = (s.gallery ?? []).map((image, i) => ({ id: i, image }))
        return (
          <>
            <Seo title={`${s.student_name} – ${universityName ?? ''}`} description={s.excerpt || s.quote} image={s.cover_image || s.photo} seo={s.seo} />
            <section className="relative isolate overflow-hidden bg-secondary">
              {s.cover_image && <img src={assetUrl(s.cover_image)} alt="" className="absolute inset-0 -z-20 size-full object-cover" fetchPriority="high" />}
              <div className="absolute inset-0 -z-10 bg-gradient-to-r from-[#0b0726]/92 via-[#0b0726]/65 to-[#0b0726]/20" aria-hidden="true" />
              <div className="container-page py-12 md:py-16">
                <Breadcrumb light className="mb-6" items={[{ label: 'Success Stories', to: '/success-stories' }, { label: s.student_name }]} />
                <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                  <Image src={s.photo} alt={s.student_name} className="size-28 shrink-0 rounded-2xl object-top shadow-card ring-4 ring-white/20 sm:size-32" />
                  <div>
                    <h1 className="text-3xl text-white sm:text-5xl">{s.student_name}</h1>
                    {course && <p className="mt-2 text-lg font-medium text-white/90">{course}</p>}
                    {(universityName || s.destination) && (
                      <p className="mt-1 flex items-center gap-2 text-white/85">
                        {s.destination && <Flag code={s.destination.country_code} className="h-4 w-6" />}
                        {[universityName, s.destination?.name].filter(Boolean).join(', ')}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            </section>

            <div className="container-page grid gap-8 py-10 lg:grid-cols-[1fr_340px]">
              <div className="min-w-0 space-y-10">
                {s.story && (
                  <section>
                    <SectionHeader title="The *Journey*" className="!mb-3" />
                    <div className="prose-content" dangerouslySetInnerHTML={{ __html: s.story }} />
                  </section>
                )}
                {gallery.length > 0 && <PhotoGrid photos={gallery} className="sm:grid-cols-4" />}
                {s.quote && (
                  <section>
                    <SectionHeader title="In Their *Words*" className="!mb-3" />
                    <blockquote className="relative rounded-2xl border border-primary-100 bg-lavender p-6 pl-16 text-[15px] leading-relaxed text-slate-700 italic">
                      <Quote className="absolute top-6 left-5 size-7 fill-primary text-primary" />
                      “{s.quote}”
                      <footer className="mt-3 text-right text-sm font-semibold text-primary not-italic">— {s.student_name}</footer>
                    </blockquote>
                  </section>
                )}
                {s.youtube_id && (
                  <section>
                    <SectionHeader title="Video *Testimonial*" className="!mb-3" />
                    <VideoCard video={{ youtube_id: s.youtube_id, title: s.student_name, thumbnail: s.cover_image }} />
                  </section>
                )}
              </div>

              <aside className="space-y-6">
                <SidebarCard>
                  {uni?.logo && <img src={assetUrl(uni.logo)} alt={uni.name} className="mx-auto mb-4 h-14 w-auto object-contain" />}
                  <QuickInfo rows={[
                    { icon: User, label: 'Student Name', value: s.student_name },
                    { icon: Earth, label: 'Country', value: s.destination?.name },
                    { icon: Landmark, label: 'University', value: universityName },
                    { icon: BookOpen, label: 'Course', value: course },
                    { icon: GraduationCap, label: 'Study Level', value: s.study_level && studyLevelLabel(s.study_level) },
                    { icon: CalendarDays, label: 'Intake', value: s.intake },
                    { icon: BadgeCheck, label: 'Status', value: s.outcome },
                  ]} />
                  <Button to="/book-appointment" className="mt-5 w-full">Start Your Journey <ArrowRight className="size-4" /></Button>
                </SidebarCard>

                {uni && (
                  <SidebarCard title={`About ${uni.name}`}>
                    <Image src={uni.thumbnail || uni.cover_image} alt="" className="aspect-[16/9] w-full rounded-xl" />
                    {uni.short_description && <p className="mt-3 text-sm text-slate-600">{uni.short_description}</p>}
                    {uni.highlights?.length > 0 && (
                      <ul className="mt-3 space-y-1.5">
                        {uni.highlights.slice(0, 4).map((h) => (
                          <li key={h} className="flex items-start gap-2 text-sm text-slate-700"><CheckCircle2 className="mt-0.5 size-4 shrink-0 text-primary" /> {h}</li>
                        ))}
                      </ul>
                    )}
                    <Link to={`/universities/${uni.slug}`} className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary">View University Details <ArrowRight className="size-4" /></Link>
                  </SidebarCard>
                )}

                <SidebarCard title="Related Information">
                  <ul className="space-y-2">
                    {[
                      s.destination && [`Study in ${s.destination.name}`, `/study-in-${s.destination.slug}`],
                      ['Other Success Stories', '/success-stories'],
                      ['Popular Courses', s.destination ? `/courses?destination[]=${s.destination.slug}` : '/courses'],
                      ['Scholarships', '/blog?category=scholarships'],
                    ].filter(Boolean).map(([label, to]) => (
                      <li key={label}>
                        <Link to={to} className="flex items-center justify-between rounded-xl bg-white px-3.5 py-3 text-sm font-medium text-ink shadow-sm hover:text-primary">
                          {label} <ChevronRight className="size-4 text-primary" />
                        </Link>
                      </li>
                    ))}
                  </ul>
                </SidebarCard>
              </aside>
            </div>

            {s.related?.length > 0 && (
              <section className="pb-12">
                <div className="container-page">
                  <SectionHeader title="More *Success Stories*" linkText="View All Stories" linkUrl="/success-stories" />
                  <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {s.related.map((r) => <StoryCard key={r.id} story={r} />)}
                  </div>
                </div>
              </section>
            )}
            <ListingCta slug="success-stories" />
          </>
        )
      }}
    </DetailQuery>
  )
}
