import { RotateCcw, ServerCrash } from 'lucide-react'
import { isRouteErrorResponse, useRouteError } from 'react-router-dom'
import { APP_BASE } from '@/lib/base'

/**
 * Router-level error boundary (the "500" page). Deliberately self-contained:
 * it must render even if the providers or layout are what crashed.
 */
export default function ErrorPage() {
  const error = useRouteError()
  const isChunkError = /Failed to fetch dynamically imported module|Importing a module script failed/i.test(error?.message ?? '')
  const status = isRouteErrorResponse(error) ? error.status : 500

  if (import.meta.env.DEV) console.error(error)

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-lavender px-4 text-center">
      <span className="mb-6 grid size-20 place-items-center rounded-3xl bg-white text-rose-500 shadow-card">
        <ServerCrash className="size-10" />
      </span>
      <p className="text-6xl font-extrabold text-slate-900">{status}</p>
      <h1 className="mt-4 text-2xl">{isChunkError ? 'A new version is available' : 'Something went wrong'}</h1>
      <p className="mt-3 max-w-md text-slate-500">
        {isChunkError ? 'Please reload the page to get the latest version.' : 'An unexpected error occurred. Please try again in a moment.'}
      </p>
      <div className="mt-8 flex gap-3">
        <button type="button" onClick={() => window.location.reload()} className="inline-flex h-11 items-center gap-2 rounded-full bg-primary px-5 text-sm font-semibold text-white">
          <RotateCcw className="size-4" /> Reload
        </button>
        <a href={`${APP_BASE}/`} className="inline-flex h-11 items-center rounded-full border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700">
          Home
        </a>
      </div>
    </div>
  )
}
