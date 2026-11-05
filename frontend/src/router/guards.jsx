import { Loader2, ShieldX } from 'lucide-react'
import { Navigate, Outlet, useLocation } from 'react-router-dom'
import Button from '@/components/ui/Button'
import { useAuth } from '@/context/AuthContext'

function FullScreenLoader() {
  return (
    <div className="grid min-h-screen place-items-center bg-lavender">
      <Loader2 className="size-8 animate-spin text-primary" />
    </div>
  )
}

/** Admin area: requires a signed-in user. */
export function RequireAuth() {
  const { isAuthenticated, isLoading } = useAuth()
  const location = useLocation()

  if (isLoading) return <FullScreenLoader />
  if (!isAuthenticated) return <Navigate to="/admin/login" replace state={{ from: location }} />
  return <Outlet />
}

/** Login / password pages: redirect signed-in users to the dashboard. */
export function GuestOnly() {
  const { isAuthenticated, isLoading } = useAuth()
  if (isLoading) return <FullScreenLoader />
  if (isAuthenticated) return <Navigate to="/admin" replace />
  return <Outlet />
}

/** Per-page permission check. The API enforces the same rule server-side. */
export function RequirePermission({ permission, children }) {
  const { can } = useAuth()
  if (permission && !can(permission)) {
    return (
      <div className="flex flex-col items-center justify-center py-24 text-center">
        <span className="mb-4 grid size-14 place-items-center rounded-2xl bg-rose-50 text-rose-600">
          <ShieldX className="size-7" />
        </span>
        <h1 className="text-xl">Access denied</h1>
        <p className="mt-1 text-sm text-slate-500">You do not have permission to view this page.</p>
        <Button to="/admin" variant="outline" size="sm" className="mt-5">Back to dashboard</Button>
      </div>
    )
  }
  return children
}
