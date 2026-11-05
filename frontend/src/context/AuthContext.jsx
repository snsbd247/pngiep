import { useQuery, useQueryClient } from '@tanstack/react-query'
import { createContext, useCallback, useContext, useEffect, useMemo } from 'react'
import { Outlet } from 'react-router-dom'
import { onUnauthorized } from '@/lib/api'
import { authService } from '@/services/authService'

const AuthContext = createContext(null)
const ME_KEY = ['auth', 'me']

/**
 * Mounted only for /admin routes, so public visitors never query the session.
 * Admin session state. The session itself lives in an HttpOnly Laravel cookie;
 * the browser never stores tokens.
 */
export function AuthProvider({ children }) {
  const queryClient = useQueryClient()

  const { data: user, isLoading } = useQuery({
    queryKey: ME_KEY,
    queryFn: authService.session,
    staleTime: 5 * 60 * 1000,
    retry: false,
  })

  useEffect(
    () =>
      onUnauthorized(() => {
        if (queryClient.getQueryData(ME_KEY)) queryClient.setQueryData(ME_KEY, null)
      }),
    [queryClient],
  )

  const login = useCallback(
    async (credentials) => {
      const me = await authService.login(credentials)
      queryClient.setQueryData(ME_KEY, me)
      return me
    },
    [queryClient],
  )

  const logout = useCallback(async () => {
    try {
      await authService.logout()
    } finally {
      queryClient.setQueryData(ME_KEY, null)
      queryClient.removeQueries({ predicate: (q) => q.queryKey[0] === 'admin' })
    }
  }, [queryClient])

  const setUser = useCallback((me) => queryClient.setQueryData(ME_KEY, me), [queryClient])

  const value = useMemo(() => {
    const permissions = new Set(user?.permissions ?? [])
    return {
      user: user ?? null,
      isLoading,
      isAuthenticated: !!user,
      can: (permission) => !!user && (user.is_super_admin || permissions.has(permission)),
      canAny: (list = []) => !!user && (user.is_super_admin || list.some((p) => permissions.has(p))),
      login,
      logout,
      setUser,
    }
  }, [user, isLoading, login, logout, setUser])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

/** Route element that provides auth to all nested admin routes. */
export function AuthRoot() {
  return (
    <AuthProvider>
      <Outlet />
    </AuthProvider>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
