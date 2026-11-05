import { Suspense, useEffect } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { LoadingSkeleton } from '@/components/ui/States'
import Footer from './Footer'
import Navbar from './Navbar'
import TopBar from './TopBar'

export default function PublicLayout() {
  const { pathname } = useLocation()

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'instant' })
  }, [pathname])

  return (
    <div className="flex min-h-screen flex-col">
      <a href="#main" className="sr-only z-[100] rounded-full bg-primary px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        Skip to content
      </a>
      <TopBar />
      <Navbar />
      <main id="main" className="flex-1">
        <Suspense fallback={<LoadingSkeleton variant="page" className="py-8" />}>
          <Outlet />
        </Suspense>
      </main>
      <Footer />
    </div>
  )
}
