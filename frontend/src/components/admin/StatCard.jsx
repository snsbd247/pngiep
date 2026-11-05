import { cn } from '@/lib/utils'

const TONES = {
  primary: 'bg-primary-50 text-primary',
  blue: 'bg-sky-50 text-sky-600',
  green: 'bg-emerald-50 text-emerald-600',
  amber: 'bg-amber-50 text-amber-600',
  rose: 'bg-rose-50 text-rose-600',
}

export default function StatCard({ label, value, icon: Icon, tone = 'primary', hint }) {
  return (
    <div className="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-2 text-3xl font-bold text-slate-900">{value ?? '—'}</p>
          {hint && <p className="mt-1 text-xs text-slate-400">{hint}</p>}
        </div>
        {Icon && (
          <span className={cn('grid size-11 place-items-center rounded-xl', TONES[tone])}>
            <Icon className="size-5" />
          </span>
        )}
      </div>
    </div>
  )
}
