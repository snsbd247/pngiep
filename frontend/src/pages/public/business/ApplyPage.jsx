import { useQuery } from '@tanstack/react-query'
import { FileText, Mail, Paperclip, Phone, Plus, Send, Trash2, User } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useSearchParams } from 'react-router-dom'
import { SidebarBlocks } from '@/components/content/ContentWidgets'
import ListingLayout from '@/components/directory/ListingLayout'
import { AgreeCheckbox, Field, Honeypot, SelectInput, SuccessPanel, TextArea, TextInput } from '@/components/public/FormControls'
import { applyServerErrors } from '@/lib/forms'
import Button from '@/components/ui/Button'
import { STUDY_LEVELS } from '@/config/studyLevels'
import { useSite } from '@/context/SiteContext'
import { cn, formatBytes } from '@/lib/utils'
import { bookingService, directoryService, inquiryService } from '@/services/publicService'

// Mirrors the server rules (InquiryController): the server re-validates everything.
const DOC_TYPES = [
  { value: 'passport', label: 'Passport' },
  { value: 'academic_certificate', label: 'Academic Certificate' },
  { value: 'transcript', label: 'Transcript' },
  { value: 'ielts', label: 'IELTS / English Test' },
  { value: 'cv', label: 'CV / Resume' },
  { value: 'other', label: 'Other' },
]
const ACCEPT = '.pdf,.jpg,.jpeg,.png,.doc,.docx'
const EXT = /\.(pdf|jpe?g|png|docx?)$/i
const MAX_BYTES = 5 * 1024 * 1024
const MAX_FILES = 10

function Documents({ docs, setDocs, errors }) {
  const input = useRef(null)
  const [localError, setLocalError] = useState('')

  const add = (files) => {
    setLocalError('')
    const next = [...docs]
    for (const file of files) {
      if (next.length >= MAX_FILES) { setLocalError(`You can upload up to ${MAX_FILES} documents.`); break }
      if (!EXT.test(file.name)) { setLocalError(`${file.name}: only PDF, JPG, PNG or Word files are allowed.`); continue }
      if (file.size > MAX_BYTES) { setLocalError(`${file.name}: each file must be 5 MB or smaller.`); continue }
      next.push({ id: `${file.name}-${file.size}-${Math.random()}`, type: next.length === 0 ? 'passport' : 'other', file })
    }
    setDocs(next)
  }

  return (
    <div>
      <div
        role="button"
        tabIndex={0}
        onClick={() => input.current?.click()}
        onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && input.current?.click()}
        onDragOver={(e) => e.preventDefault()}
        onDrop={(e) => { e.preventDefault(); add(e.dataTransfer.files) }}
        className="flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-primary-200 bg-primary-50/40 px-4 py-7 text-center transition hover:border-primary hover:bg-primary-50"
      >
        <Paperclip className="size-7 text-primary" />
        <p className="mt-2 text-sm font-semibold text-ink">Drag & drop or click to upload</p>
        <p className="mt-1 text-xs text-slate-500">PDF, JPG, PNG or Word · max 5 MB each · up to {MAX_FILES} files</p>
        <input ref={input} type="file" multiple accept={ACCEPT} className="hidden" onChange={(e) => { add(e.target.files); e.target.value = '' }} />
      </div>
      {localError && <p className="mt-2 text-xs font-medium text-rose-600">{localError}</p>}
      {docs.length > 0 && (
        <ul className="mt-4 space-y-2">
          {docs.map((d, i) => {
            const err = errors?.[`documents.${i}.file`]?.[0] ?? errors?.[`documents.${i}.type`]?.[0]
            return (
              <li key={d.id} className={cn('rounded-xl border p-3', err ? 'border-rose-300 bg-rose-50/50' : 'border-slate-200')}>
                <div className="flex flex-wrap items-center gap-3">
                  <FileText className="size-5 shrink-0 text-primary" />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-ink">{d.file.name}</p>
                    <p className="text-xs text-slate-400">{formatBytes(d.file.size)}</p>
                  </div>
                  <div className="w-44 shrink-0">
                    <SelectInput aria-label={`Document type for ${d.file.name}`} className="h-9 text-xs" value={d.type}
                      onChange={(e) => setDocs(docs.map((x) => (x.id === d.id ? { ...x, type: e.target.value } : x)))}>
                      {DOC_TYPES.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </SelectInput>
                  </div>
                  <button type="button" onClick={() => setDocs(docs.filter((x) => x.id !== d.id))} className="grid size-9 place-items-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" aria-label={`Remove ${d.file.name}`}>
                    <Trash2 className="size-4" />
                  </button>
                </div>
                {err && <p className="mt-1 text-xs font-medium text-rose-600">{err}</p>}
              </li>
            )
          })}
        </ul>
      )}
      {docs.length > 0 && docs.length < MAX_FILES && (
        <Button variant="ghost" size="sm" className="mt-2" onClick={() => input.current?.click()}><Plus className="size-4" /> Add another document</Button>
      )}
    </div>
  )
}

function ApplyForm() {
  const { setting } = useSite()
  const [search] = useSearchParams()
  const [docs, setDocs] = useState([])
  const [docErrors, setDocErrors] = useState(null)
  const [progress, setProgress] = useState(0)
  const [done, setDone] = useState(null)
  const { register, handleSubmit, setError, setValue, watch, formState: { errors, isSubmitting } } = useForm({
    defaultValues: { name: '', email: '', phone: '', destination: search.get('destination') ?? '', university: search.get('university') ?? '', course: search.get('course') ?? '', study_level: '', intake: '', message: '', agree: false, website: '' },
  })
  const destination = watch('destination')
  const university = watch('university')
  const course = watch('course')

  const options = useQuery({ queryKey: ['booking-options'], queryFn: bookingService.options, staleTime: 60_000 })
  const universities = useQuery({
    queryKey: ['apply-universities', destination],
    queryFn: () => directoryService.universities({ destination: [destination], per_page: 48, sort: 'name' }),
    enabled: !!destination,
  })
  const courses = useQuery({
    queryKey: ['apply-courses', university],
    queryFn: () => directoryService.courses({ university: [university], per_page: 48, sort: 'name' }),
    enabled: !!university,
  })
  const courseDetail = useQuery({ queryKey: ['course', course], queryFn: () => directoryService.course(course), enabled: !!course, retry: false })
  const universityDetail = useQuery({ queryKey: ['university', university], queryFn: () => directoryService.university(university), enabled: !!university, retry: false })

  // Deep links (?course= / ?university=) fill in the levels above them.
  useEffect(() => {
    const u = courseDetail.data?.university?.slug
    if (u && !university) setValue('university', u)
  }, [courseDetail.data]) // eslint-disable-line react-hooks/exhaustive-deps
  useEffect(() => {
    const d = universityDetail.data?.destination?.slug
    if (d && !destination) setValue('destination', d)
  }, [universityDetail.data]) // eslint-disable-line react-hooks/exhaustive-deps

  const intakes = courseDetail.data?.intakes ?? []

  const onSubmit = async (values) => {
    setDocErrors(null)
    const fd = new FormData()
    Object.entries(values).forEach(([k, v]) => {
      if (k === 'agree') fd.append(k, v ? '1' : '0')
      else if (v) fd.append(k, v)
    })
    docs.forEach((d, i) => {
      fd.append(`documents[${i}][type]`, d.type)
      fd.append(`documents[${i}][file]`, d.file)
    })
    try {
      const res = await inquiryService.apply(fd, (e) => e.total && setProgress(Math.round((e.loaded / e.total) * 100)))
      setDone({ message: res.message, reference: res.data?.reference })
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } catch (e) {
      if (e.isValidation) setDocErrors(Object.fromEntries(Object.entries(e.errors).filter(([k]) => k.startsWith('documents'))))
      applyServerErrors(e, setError, (k) => (k.startsWith('documents') ? 'root' : k))
    } finally {
      setProgress(0)
    }
  }

  if (done) {
    return (
      <SuccessPanel title="Application submitted">
        <p>{done.message}</p>
        {done.reference && <p className="mt-2">Your reference: <strong className="text-ink">{done.reference}</strong></p>}
        <div className="mt-5 flex flex-wrap justify-center gap-3">
          <Button variant="outline" to="/book-appointment">Book a Consultation</Button>
          <Button to="/">Back to Home</Button>
        </div>
      </SuccessPanel>
    )
  }

  const uniList = universities.data?.data ?? []
  const courseList = courses.data?.data ?? []

  return (
    <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-6">
      <Honeypot {...register('website')} />
      <section className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
        <h2 className="text-xl">Personal Details</h2>
        <div className="mt-5 grid gap-4 sm:grid-cols-2">
          <Field label="Full Name" required icon={User} error={errors.name?.message}>
            <TextInput icon invalid={!!errors.name} autoComplete="name" placeholder="As shown on your passport" {...register('name', { required: 'Please enter your name.' })} />
          </Field>
          <Field label="Email Address" required icon={Mail} error={errors.email?.message}>
            <TextInput icon type="email" invalid={!!errors.email} autoComplete="email" placeholder="you@example.com"
              {...register('email', { required: 'Please enter your email.', pattern: { value: /^\S+@\S+\.\S+$/, message: 'Enter a valid email.' } })} />
          </Field>
          <Field label="Phone / WhatsApp" required icon={Phone} error={errors.phone?.message}>
            <TextInput icon type="tel" invalid={!!errors.phone} autoComplete="tel" placeholder="Phone number with country code"
              {...register('phone', { required: 'Please enter your phone number.', pattern: { value: /^[0-9+()\-\s]{6,30}$/, message: 'Enter a valid phone number.' } })} />
          </Field>
          <Field label="Study Level" error={errors.study_level?.message}>
            <SelectInput {...register('study_level')}>
              <option value="">Select level</option>
              {STUDY_LEVELS.map((l) => <option key={l.value} value={l.value}>{l.label}</option>)}
            </SelectInput>
          </Field>
        </div>
      </section>

      <section className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
        <h2 className="text-xl">Program of Interest</h2>
        <div className="mt-5 grid gap-4 sm:grid-cols-2">
          <Field label="Destination">
            {/* Controlled: options arrive asynchronously, deep-linked values must survive that. */}
            <SelectInput value={destination} onChange={(e) => { setValue('destination', e.target.value); setValue('university', ''); setValue('course', '') }}>
              <option value="">Select destination</option>
              {(options.data?.destinations ?? []).map((d) => <option key={d.id} value={d.slug}>{d.name}</option>)}
            </SelectInput>
          </Field>
          <Field label="University" hint={!destination ? 'Choose a destination first.' : undefined}>
            <SelectInput disabled={!destination} value={university} onChange={(e) => { setValue('university', e.target.value); setValue('course', '') }}>
              <option value="">{universities.isFetching ? 'Loading…' : 'Select university (optional)'}</option>
              {university && !uniList.some((u) => u.slug === university) && <option value={university}>{universityDetail.data?.name ?? courseDetail.data?.university?.name ?? university}</option>}
              {uniList.map((u) => <option key={u.slug} value={u.slug}>{u.name}</option>)}
            </SelectInput>
          </Field>
          <Field label="Course" hint={!university ? 'Choose a university first.' : undefined}>
            <SelectInput disabled={!university} value={course} onChange={(e) => setValue('course', e.target.value)}>
              <option value="">{courses.isFetching ? 'Loading…' : 'Select course (optional)'}</option>
              {course && !courseList.some((c) => c.slug === course) && <option value={course}>{courseDetail.data?.name ?? course}</option>}
              {courseList.map((c) => <option key={c.slug} value={c.slug}>{c.name}</option>)}
            </SelectInput>
          </Field>
          <Field label="Preferred Intake">
            {intakes.length ? (
              <SelectInput {...register('intake')}>
                <option value="">Select intake</option>
                {intakes.map((i) => <option key={i} value={i}>{i}</option>)}
              </SelectInput>
            ) : (
              <TextInput placeholder="e.g. September 2027" maxLength={60} {...register('intake')} />
            )}
          </Field>
        </div>
        <Field label="Anything else we should know?" className="mt-4">
          <TextArea rows={3} placeholder="Grades, English test scores, budget, questions…" {...register('message', { maxLength: { value: 5000, message: 'Too long.' } })} />
        </Field>
      </section>

      <section className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
        <h2 className="text-xl">Documents</h2>
        <p className="mt-1 text-sm text-slate-500">Upload what you have now; you can send the rest later. Files are stored securely and only our counsellors can see them.</p>
        <div className="mt-5"><Documents docs={docs} setDocs={setDocs} errors={docErrors} /></div>
      </section>

      <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
        <AgreeCheckbox error={errors.agree?.message} {...register('agree', { required: 'Please accept the terms to continue.' })}>
          I confirm the information is correct and agree to the <Link to="/privacy-policy" className="text-primary hover:underline">privacy policy</Link>.
        </AgreeCheckbox>
        {errors.root && <p className="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{errors.root.message}</p>}
        {isSubmitting && progress > 0 && (
          <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100}>
            <div className="h-full bg-primary transition-all" style={{ width: `${progress}%` }} />
          </div>
        )}
        <Button type="submit" size="lg" className="mt-5 w-full sm:w-auto" loading={isSubmitting}>
          <Send className="size-4" /> {setting('forms.apply_button', 'Submit Application')}
        </Button>
      </div>
    </form>
  )
}

export default function ApplyPage() {
  return (
    <ListingLayout slug="apply">
      {({ sidebarBlocks }) => (
        <div className="grid gap-8 lg:grid-cols-[1fr_340px]">
          <div className="min-w-0"><ApplyForm /></div>
          <aside className="space-y-6"><SidebarBlocks blocks={sidebarBlocks} /></aside>
        </div>
      )}
    </ListingLayout>
  )
}
