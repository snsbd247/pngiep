import { QueryClientProvider } from '@tanstack/react-query'
import { Suspense } from 'react'
import { HelmetProvider } from 'react-helmet-async'
import { RouterProvider } from 'react-router-dom'
import { ToastProvider } from '@/components/ui/Toast'
import { SiteProvider } from '@/context/SiteContext'
import { queryClient } from '@/lib/queryClient'
import { router } from '@/router'

export default function App() {
  return (
    <HelmetProvider>
      <QueryClientProvider client={queryClient}>
        <ToastProvider>
          <SiteProvider>
            <Suspense fallback={null}>
              <RouterProvider router={router} />
            </Suspense>
          </SiteProvider>
        </ToastProvider>
      </QueryClientProvider>
    </HelmetProvider>
  )
}
