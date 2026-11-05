import { CheckCircle2, Info, TriangleAlert, X, XCircle } from 'lucide-react'
import { createContext, useCallback, useContext, useMemo, useRef, useState } from 'react'
import { cn } from '@/lib/utils'

const ToastContext = createContext(null)

const STYLES = {
  success: { icon: CheckCircle2, className: 'text-emerald-600' },
  error: { icon: XCircle, className: 'text-rose-600' },
  warning: { icon: TriangleAlert, className: 'text-amber-500' },
  info: { icon: Info, className: 'text-primary' },
}

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])
  const idRef = useRef(0)

  const dismiss = useCallback((id) => setToasts((list) => list.filter((t) => t.id !== id)), [])

  const show = useCallback(
    (type, message, duration = 4000) => {
      if (!message) return
      const id = ++idRef.current
      setToasts((list) => [...list.slice(-3), { id, type, message }])
      setTimeout(() => dismiss(id), duration)
    },
    [dismiss],
  )

  const toast = useMemo(
    () => ({
      success: (m) => show('success', m),
      error: (m) => show('error', m, 6000),
      warning: (m) => show('warning', m),
      info: (m) => show('info', m),
    }),
    [show],
  )

  return (
    <ToastContext.Provider value={toast}>
      {children}
      <div
        aria-live="polite"
        className="pointer-events-none fixed inset-x-4 bottom-4 z-[100] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:items-end"
      >
        {toasts.map((t) => {
          const { icon: Icon, className } = STYLES[t.type]
          return (
            <div
              key={t.id}
              role="status"
              className="animate-fade-up pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-card-hover"
            >
              <Icon className={cn('mt-0.5 size-5 shrink-0', className)} />
              <p className="flex-1 text-sm text-slate-700">{t.message}</p>
              <button
                type="button"
                onClick={() => dismiss(t.id)}
                className="text-slate-400 hover:text-slate-600"
                aria-label="Dismiss"
              >
                <X className="size-4" />
              </button>
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast must be used within ToastProvider')
  return ctx
}
