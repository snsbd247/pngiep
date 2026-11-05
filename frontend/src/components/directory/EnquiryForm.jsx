import { CheckCircle2, Mail, MessageSquare, Phone, User } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '@/components/ui/Button'
import { STUDY_LEVELS } from '@/config/studyLevels'
import { useSite } from '@/context/SiteContext'
import { cn } from '@/lib/utils'
import { publicService } from '@/services/publicService'

const field = (invalid) =>
  cn(
    'h-11 w-full rounded-lg border bg-white pr-3 pl-10 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-4 focus:outline-none',
    invalid ? 'border-rose-400 focus:ring-rose-200' : 'border-transparent focus:ring-white/40',
  )

function Field({ icon: Icon, error, children }) {
  return (
    <div>
      <div className="relative">
        {Icon && <Icon className="pointer-events-none absolute top-3.5 left-3.5 size-4 text-slate-400" />}
        {children}
      </div>
      {error && <p className="mt-1 text-xs font-medium text-rose-200">{error}</p>}
    </div>
  )
}

/**
 * Purple "get free guidance" form on destination / university / course pages.
 * Creates a Lead via POST /enquiries. Titles come from Settings > Forms.
 * context: { destination?, university?, course? } slugs; intakes / courses for the selects.
 */
export default function EnquiryForm({ titleKey = 'enquiry_destination_title', name, context = {}, intakes = [], courseOptions = [] }) {
  const { setting } = useSite()
  const [sent, setSent] = useState('')
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm({ defaultValues: { name: '', email: '', phone: '', study_level: '', intake: '', interest: '', message: '', website: '' } })

  const onSubmit = async (values) => {
    try {
      const res = await publicService.enquire({ ...values, ...context, page_url: window.location.href })
      setSent(res.message)
    } catch (e) {
      if (e.isValidation) Object.entries(e.errors).forEach(([k, m]) => setError(k, { message: m[0] }))
      else setError('root', { message: e.message })
    }
  }

  const title = String(setting(`forms.${titleKey}`, '')).replaceAll('{name}', name ?? '')

  return (
    <div className="rounded-2xl bg-primary p-5 text-white shadow-glow sm:p-6">
      {title && <h2 className="text-lg text-white">{title}</h2>}
      {setting('forms.enquiry_subtitle') && <p className="mt-1 text-sm text-white/80">{setting('forms.enquiry_subtitle')}</p>}

      {sent ? (
        <p className="mt-5 flex items-start gap-2 rounded-xl bg-white/15 p-4 text-sm"><CheckCircle2 className="size-5 shrink-0" /> {sent}</p>
      ) : (
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="mt-5 space-y-3">
          {/* Honeypot: hidden from people, filled by bots. */}
          <input type="text" tabIndex={-1} autoComplete="off" className="hidden" aria-hidden="true" {...register('website')} />
          <Field icon={User} error={errors.name?.message}>
            <input className={field(errors.name)} placeholder="Full Name" aria-label="Full Name" autoComplete="name" {...register('name', { required: 'Please enter your name.' })} />
          </Field>
          <Field icon={Mail} error={errors.email?.message}>
            <input type="email" className={field(errors.email)} placeholder="Email Address" aria-label="Email Address" autoComplete="email"
              {...register('email', { required: 'Please enter your email.', pattern: { value: /^\S+@\S+\.\S+$/, message: 'Enter a valid email.' } })} />
          </Field>
          <Field icon={Phone} error={errors.phone?.message}>
            <input type="tel" className={field(errors.phone)} placeholder="Phone Number" aria-label="Phone Number" autoComplete="tel"
              {...register('phone', { required: 'Please enter your phone number.', pattern: { value: /^[0-9+()\-\s]{6,30}$/, message: 'Enter a valid phone number.' } })} />
          </Field>
          <Field>
            <select className={cn(field(), 'pl-3')} aria-label="Current education level" {...register('study_level')}>
              <option value="">Select Study Level</option>
              {STUDY_LEVELS.map((l) => <option key={l.value} value={l.value}>{l.label}</option>)}
            </select>
          </Field>
          {courseOptions.length > 0 && (
            <Field>
              <select className={cn(field(), 'pl-3')} aria-label="Interested course" {...register('interest')}>
                <option value="">Interested Course (Optional)</option>
                {courseOptions.map((c) => <option key={c} value={c}>{c}</option>)}
              </select>
            </Field>
          )}
          {intakes.length > 0 && (
            <Field>
              <select className={cn(field(), 'pl-3')} aria-label="Preferred intake" {...register('intake')}>
                <option value="">Preferred Intake</option>
                {intakes.map((i) => <option key={i} value={i}>{i}</option>)}
              </select>
            </Field>
          )}
          <Field icon={MessageSquare} error={errors.message?.message}>
            <textarea rows={3} className={cn(field(errors.message), 'h-auto py-2.5')} placeholder="Your Message (Optional)" aria-label="Your Message" {...register('message')} />
          </Field>
          {errors.root && <p className="text-xs font-medium text-rose-200">{errors.root.message}</p>}
          <Button type="submit" variant="white" className="w-full" loading={isSubmitting}>
            {setting('forms.enquiry_button', 'Submit')}
          </Button>
        </form>
      )}
    </div>
  )
}
