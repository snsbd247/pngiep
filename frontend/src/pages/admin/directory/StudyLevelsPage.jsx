import { useQueries } from '@tanstack/react-query'
import { ArrowRight, Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import PageHeader from '@/components/admin/PageHeader'
import Button from '@/components/ui/Button'
import { STUDY_LEVELS } from '@/config/studyLevels'
import { contentServices } from '@/services/admin'

/**
 * Courses › Study Levels. The levels are fixed (they drive the website's course filters
 * and study pathways); this page shows how many courses each one has.
 */
export default function StudyLevelsPage() {
  const counts = useQueries({
    queries: STUDY_LEVELS.map((l) => ({
      queryKey: ['admin', 'courses', { study_level: l.value, per_page: 1 }],
      queryFn: () => contentServices.courses.list({ study_level: l.value, per_page: 1 }),
    })),
  })

  return (
    <>
      <PageHeader title="Study Levels" description="Every course belongs to one study level. The levels power the website filters and the Study Pathways section." />
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {STUDY_LEVELS.map((l, i) => {
          const total = counts[i].data?.meta?.total
          return (
            <div key={l.value} className="flex items-center justify-between gap-4 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card">
              <div>
                <p className="font-semibold text-slate-800">{l.label}</p>
                <p className="mt-0.5 text-sm text-slate-500">{total === undefined ? '…' : `${total} course${total === 1 ? '' : 's'}`}</p>
              </div>
              <div className="flex shrink-0 gap-1">
                <Button to={`/admin/content/courses/new?study_level=${l.value}`} variant="ghost" size="icon" aria-label={`Add a ${l.label} course`}><Plus className="size-4" /></Button>
                <Link to={`/admin/content/courses?study_level=${l.value}`} className="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-primary hover:bg-primary-50">
                  Courses <ArrowRight className="size-4" />
                </Link>
              </div>
            </div>
          )
        })}
      </div>
    </>
  )
}
