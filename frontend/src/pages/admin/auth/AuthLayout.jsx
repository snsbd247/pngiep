import { GraduationCap } from 'lucide-react'
import { Helmet } from 'react-helmet-async'
import { useSite } from '@/context/SiteContext'
import { useAdminTheme } from '@/hooks/useAdminTheme'

export default function AuthLayout({ title, subtitle, children }) {
  useAdminTheme()
  const { setting } = useSite()
  const name = setting('general.site_name', '')
  const logo = setting('general.site_logo')

  return (
    <div className="grid min-h-screen bg-lavender lg:grid-cols-2">
      <Helmet>
        <title>{`${title} | Admin`}</title>
        <meta name="robots" content="noindex, nofollow" />
      </Helmet>
      <div className="relative hidden overflow-hidden bg-brand-gradient lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div className="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full border-[60px] border-white/10" aria-hidden="true" />
        <div className="pointer-events-none absolute -bottom-32 -left-20 size-96 rounded-full bg-white/10 blur-3xl" aria-hidden="true" />
        <div className="relative flex items-center gap-3 text-white">
          <span className="grid size-11 place-items-center rounded-xl bg-white/15 backdrop-blur">
            <GraduationCap className="size-6" />
          </span>
          <span className="text-lg font-bold">{name}</span>
        </div>
        <div className="relative max-w-md text-white">
          <h2 className="text-4xl leading-tight text-white">{setting('general.site_tagline', '')}</h2>
        </div>
        <p className="relative text-sm text-white/60">Admin Panel</p>
      </div>

      <div className="flex items-center justify-center px-4 py-12 sm:px-8">
        <div className="w-full max-w-md">
          <div className="mb-8 lg:hidden">
            {logo ? <img src={logo} alt={name} className="h-11 w-auto" /> : <span className="text-xl font-bold text-slate-900">{name}</span>}
          </div>
          <div className="rounded-3xl border border-slate-200/70 bg-white p-6 shadow-card sm:p-8">
            <h1 className="text-2xl">{title}</h1>
            {subtitle && <p className="mt-1.5 text-sm text-slate-500">{subtitle}</p>}
            <div className="mt-7">{children}</div>
          </div>
        </div>
      </div>
    </div>
  )
}
