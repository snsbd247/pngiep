import { ArrowRight, BookOpen, CalendarDays, Clock, GraduationCap, Wallet } from 'lucide-react'
import { Link } from 'react-router-dom'
import Button from '@/components/ui/Button'
import Flag from '@/components/ui/Flag'
import Image from '@/components/ui/Image'
import { studyLevelLabel } from '@/config/studyLevels'
import { assetUrl, cn, formatCount, formatMoney } from '@/lib/utils'

function Stat({ value, label }) {
  if (!value) return null
  return (
    <div className="min-w-0 text-center">
      <p className="text-sm font-bold text-ink">{value}</p>
      <p className="truncate text-[11px] text-slate-500">{label}</p>
    </div>
  )
}

/** University card for list pages (image, logo, country, key stats, two actions). */
export function UniversityCard({ university }) {
  const url = `/universities/${university.slug}`
  return (
    <article className="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-100 bg-white shadow-card transition hover:-translate-y-1 hover:shadow-card-hover">
      <Link to={url} className="block overflow-hidden" tabIndex={-1} aria-hidden="true">
        <Image src={university.thumbnail || university.cover_image} alt="" className="aspect-[16/9] w-full transition duration-500 group-hover:scale-105" />
      </Link>
      <div className="relative flex flex-1 flex-col px-4 pb-4">
        <div className="-mt-8 mb-3 flex items-end gap-3">
          <span className="grid size-16 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-100 bg-white p-1.5 shadow-card">
            {university.logo ? (
              <img src={assetUrl(university.logo)} alt="" className="max-h-full max-w-full object-contain" loading="lazy" />
            ) : (
              <GraduationCap className="size-7 text-primary" />
            )}
          </span>
        </div>
        <h3 className="text-[15px] leading-snug font-semibold">
          <Link to={url} className="hover:text-primary">{university.name}</Link>
        </h3>
        {university.destination && (
          <p className="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
            <Flag code={university.destination.country_code} className="h-3 w-4" />
            {[university.city, university.destination.name].filter(Boolean).join(', ')}
          </p>
        )}
        <div className="my-4 grid grid-cols-3 gap-2 border-y border-slate-100 py-3">
          <Stat value={university.world_ranking ? `#${university.world_ranking}` : null} label="World Ranking" />
          <Stat value={university.student_count ? formatCount(university.student_count) : null} label="Students" />
          <Stat value={university.courses_count ? formatCount(university.courses_count) : null} label="Courses" />
        </div>
        <div className="mt-auto grid grid-cols-2 gap-2">
          <Button to={url} size="sm">View Details <ArrowRight className="size-3.5" /></Button>
          <Button to={`/courses?university[]=${university.slug}`} variant="secondary" size="sm">View Courses</Button>
        </div>
      </div>
    </article>
  )
}

/** Course card (image, level, university, duration / fee). */
export function CourseCard({ course, compact = false }) {
  return (
    <Link
      to={`/courses/${course.slug}`}
      className="group flex h-full flex-col overflow-hidden rounded-xl border border-slate-100 bg-white shadow-card transition hover:-translate-y-1 hover:shadow-card-hover"
    >
      <Image src={course.image} alt="" className={cn('w-full transition duration-500 group-hover:scale-105', compact ? 'aspect-[16/9]' : 'aspect-[16/10]')} />
      <div className="flex flex-1 flex-col p-4">
        <span className="mb-2 w-fit rounded-md bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary">{studyLevelLabel(course.study_level)}</span>
        <h3 className="line-clamp-2 text-sm leading-snug font-semibold">{course.name}</h3>
        {!compact && course.university && <p className="mt-1 line-clamp-1 text-xs text-slate-500">{course.university.name}</p>}
        <div className="mt-auto flex items-center justify-between gap-2 pt-3 text-xs text-slate-500">
          <span className="flex flex-wrap items-center gap-x-3 gap-y-1">
            {course.duration && <span className="flex items-center gap-1"><Clock className="size-3.5 text-primary" /> {course.duration}</span>}
            {!compact && course.tuition_fee && <span className="flex items-center gap-1"><Wallet className="size-3.5 text-primary" /> {formatMoney(course.tuition_fee, course.currency_code)}</span>}
          </span>
          <span className="grid size-7 shrink-0 place-items-center rounded-full bg-primary-50 text-primary transition group-hover:bg-primary group-hover:text-white">
            <ArrowRight className="size-3.5" />
          </span>
        </div>
      </div>
    </Link>
  )
}

/** Larger destination card for the destinations list page. */
export function DestinationListCard({ destination }) {
  return (
    <Link to={`/study-in-${destination.slug}`} className="group relative block overflow-hidden rounded-2xl shadow-card">
      <Image src={destination.thumbnail || destination.hero_image} alt="" className="aspect-[4/3] w-full transition duration-500 group-hover:scale-105" />
      <div className="absolute inset-0 bg-gradient-to-t from-[#0b0726]/90 via-[#0b0726]/30 to-transparent" />
      <div className="absolute inset-x-0 bottom-0 p-5 text-white">
        <p className="flex items-center gap-2 text-lg font-semibold">
          <Flag code={destination.country_code} className="h-4 w-6" /> {destination.name}
        </p>
        {destination.tagline && <p className="mt-1 line-clamp-2 text-sm text-white/80">{destination.tagline}</p>}
        <p className="mt-3 flex items-center gap-4 text-xs text-white/75">
          {destination.universities_count > 0 && <span className="flex items-center gap-1"><BookOpen className="size-3.5" /> {destination.universities_count} universities</span>}
          {destination.courses_count > 0 && <span className="flex items-center gap-1"><GraduationCap className="size-3.5" /> {destination.courses_count} courses</span>}
        </p>
      </div>
    </Link>
  )
}

/** Small info chip used in detail heroes. */
export function InfoChip({ icon: Icon = CalendarDays, children }) {
  if (!children) return null
  return (
    <span className="inline-flex items-center gap-1.5 rounded-md bg-white/15 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
      <Icon className="size-3.5" /> {children}
    </span>
  )
}

