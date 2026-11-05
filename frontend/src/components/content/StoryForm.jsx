import { BookOpen, CheckCircle2, GraduationCap, Landmark, Mail, MessageSquare, User } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '@/components/ui/Button'
import { useSite } from '@/context/SiteContext'
import { cn } from '@/lib/utils'
import { contentService } from '@/services/publicService'

const input = (invalid) =>
  cn('h-11 w-full rounded-lg border bg-white pr-3 pl-10 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-4 focus:outline-none',
    invalid ? 'border-rose-400 focus:ring-rose-200' : 'border-transparent focus:ring-white/40')

/** "Share your success story" (saved as a draft for admin review). Text from Settings > Forms. */
export default function StoryForm({ destinations = [] }) {
  const { setting } = useSite()
  const [sent, setSent] = useState('')
  const { register, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm()

  const onSubmit = async (values) => {
    try {
      const res = await contentService.submitStory(values)
      setSent(res.message)
    } catch (e) {
      if (e.isValidation) Object.entries(e.errors).forEach(([k, m]) => setError(k, { message: m[0] }))
      else setError('root', { message: e.message })
    }
  }

  const err = (name) => errors[name] && <p className="mt-1 text-xs text-rose-200">{errors[name].message}</p>

  return (
    <div className="rounded-2xl bg-primary p-5 text-white shadow-glow">
      {setting('forms.story_form_title') && <h3 className="text-lg text-white">{setting('forms.story_form_title')}</h3>}
      {setting('forms.story_form_subtitle') && <p className="mt-1 text-xs text-white/80">{setting('forms.story_form_subtitle')}</p>}
      {sent ? (
        <p className="mt-4 flex items-start gap-2 rounded-xl bg-white/15 p-4 text-sm"><CheckCircle2 className="size-5 shrink-0" /> {sent}</p>
      ) : (
        <form onSubmit={handleSubmit(onSubmit)} noValidate className="mt-4 space-y-2.5">
          <input type="text" tabIndex={-1} autoComplete="off" className="hidden" aria-hidden="true" {...register('website')} />
          <div className="relative"><User className="absolute top-3.5 left-3.5 size-4 text-slate-400" />
            <input className={input(errors.name)} placeholder="Your Name" aria-label="Your Name" {...register('name', { required: 'Please enter your name.' })} />{err('name')}</div>
          <div className="relative"><Mail className="absolute top-3.5 left-3.5 size-4 text-slate-400" />
            <input type="email" className={input(errors.email)} placeholder="Email Address" aria-label="Email Address" {...register('email', { required: 'Please enter your email.', pattern: { value: /^\S+@\S+\.\S+$/, message: 'Enter a valid email.' } })} />{err('email')}</div>
          {destinations.length > 0 && (
            <div className="relative"><GraduationCap className="absolute top-3.5 left-3.5 size-4 text-slate-400" />
              <select className={input()} aria-label="Study Destination" {...register('destination')}>
                <option value="">Study Destination</option>
                {destinations.map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
              </select></div>
          )}
          <div className="relative"><Landmark className="absolute top-3.5 left-3.5 size-4 text-slate-400" />
            <input className={input()} placeholder="University Name" aria-label="University Name" {...register('university')} /></div>
          <div className="relative"><BookOpen className="absolute top-3.5 left-3.5 size-4 text-slate-400" />
            <input className={input()} placeholder="Course Name" aria-label="Course Name" {...register('course')} /></div>
          <div className="relative"><MessageSquare className="absolute top-3.5 left-3.5 size-4 text-slate-400" />
            <textarea rows={4} className={cn(input(errors.story), 'h-auto py-2.5')} placeholder="Your Story (Brief)" aria-label="Your Story"
              {...register('story', { required: 'Please tell us your story.', minLength: { value: 20, message: 'Please write at least 20 characters.' } })} />{err('story')}</div>
          {errors.root && <p className="text-xs text-rose-200">{errors.root.message}</p>}
          <Button type="submit" variant="white" className="w-full" loading={isSubmitting}>{setting('forms.story_form_button', 'Submit')}</Button>
        </form>
      )}
    </div>
  )
}
