import { QueryClient } from '@tanstack/react-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000,
      gcTime: 10 * 60 * 1000,
      refetchOnWindowFocus: false,
      // Don't retry client errors (404, 403, 422…); retry network/5xx once.
      retry: (failureCount, error) => (error?.status >= 400 && error?.status < 500 ? false : failureCount < 1),
    },
  },
})
