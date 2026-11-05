import AppIcon from '@/components/ui/AppIcon'
import { cn } from '@/lib/utils'

/** Row of statistics ({ icon, value, suffix, label }) in a soft lavender card. */
export default function StatsBar({ items = [], overlap = false, className }) {
  if (!items.length) return null

  return (
    <div className={cn('container-page relative z-10', overlap && '-mt-10 md:-mt-12', className)}>
      <div className={cn('grid grid-cols-2 gap-x-4 gap-y-6 rounded-2xl border border-primary-100 px-5 py-6 sm:px-8 lg:grid-cols-4', overlap ? 'bg-white shadow-card-hover' : 'bg-lavender-200')}>
        {items.map((stat) => (
          <div key={stat.id} className="flex items-center gap-3 sm:gap-4 lg:justify-center">
            {stat.icon && (
              <span className="grid size-11 shrink-0 place-items-center rounded-full bg-white text-primary shadow-sm sm:size-14">
                <AppIcon name={stat.icon} className="size-6 sm:size-7" />
              </span>
            )}
            <div className="min-w-0">
              <p className="text-xl font-bold text-ink sm:text-2xl lg:text-[1.75rem]">
                {stat.value}
                {stat.suffix}
              </p>
              <p className="text-xs text-slate-500 sm:text-sm">{stat.label}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
