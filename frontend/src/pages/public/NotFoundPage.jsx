import { ArrowLeft, Compass } from 'lucide-react'
import Seo from '@/components/Seo'
import Button from '@/components/ui/Button'

export default function NotFoundPage() {
  return (
    <section className="bg-lavender">
      <Seo title="Page not found" noindex />
      <div className="container-page flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
        <span className="mb-6 grid size-20 place-items-center rounded-3xl bg-white text-primary shadow-card">
          <Compass className="size-10" />
        </span>
        <p className="text-brand-gradient text-7xl font-extrabold">404</p>
        <h1 className="mt-4 text-2xl sm:text-3xl">We couldn't find that page</h1>
        <p className="mt-3 max-w-md text-slate-500">The page may have moved or no longer exists.</p>
        <Button to="/" variant="gradient" className="mt-8">
          <ArrowLeft className="size-4" /> Back to home
        </Button>
      </div>
    </section>
  )
}
