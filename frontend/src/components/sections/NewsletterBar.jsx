import { CheckCircle2, Mail } from 'lucide-react'
import { useState } from 'react'
import Button from '@/components/ui/Button'
import HighlightText from '@/components/ui/HighlightText'
import { useToast } from '@/components/ui/Toast'
import { publicService } from '@/services/publicService'

/** Newsletter signup strip. Copy (title, subtitle, button, placeholder) comes from the CMS. */
export default function NewsletterBar({ title, subtitle, buttonLabel, placeholder }) {
  const toast = useToast()
  const [email, setEmail] = useState('')
  const [state, setState] = useState({ loading: false, done: '', error: '' })

  const submit = async (e) => {
    e.preventDefault()
    if (!/^\S+@\S+\.\S+$/.test(email)) {
      setState((s) => ({ ...s, error: 'Please enter a valid email address.' }))
      return
    }
    setState({ loading: true, done: '', error: '' })
    try {
      const res = await publicService.subscribe({ email })
      toast.success(res.message)
      setEmail('')
      setState({ loading: false, done: res.message, error: '' })
    } catch (err) {
      setState({ loading: false, done: '', error: err.errors?.email?.[0] || err.message })
    }
  }

  return (
    <section className="bg-lavender-200">
      <div className="container-page flex flex-col gap-6 py-8 md:flex-row md:items-center md:justify-between md:py-10">
        <div className="flex items-center gap-4">
          <span className="grid size-14 shrink-0 place-items-center rounded-2xl bg-primary text-white shadow-glow">
            <Mail className="size-7" />
          </span>
          <div>
            {title && <h2 className="text-xl sm:text-2xl"><HighlightText text={title} /></h2>}
            {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
          </div>
        </div>
        {state.done ? (
          <p className="flex items-center gap-2 font-medium text-emerald-700"><CheckCircle2 className="size-5" /> {state.done}</p>
        ) : (
          <form onSubmit={submit} noValidate className="w-full md:max-w-md">
            <div className="flex gap-2">
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder={placeholder || 'Email'}
                aria-label={placeholder || 'Email'}
                aria-invalid={!!state.error}
                className="h-12 min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-4 text-sm focus:border-primary focus:ring-4 focus:ring-primary-100 focus:outline-none"
              />
              <Button type="submit" size="lg" loading={state.loading}>{buttonLabel || 'Subscribe'}</Button>
            </div>
            {state.error && <p className="mt-2 text-xs font-medium text-rose-600">{state.error}</p>}
          </form>
        )}
      </div>
    </section>
  )
}
