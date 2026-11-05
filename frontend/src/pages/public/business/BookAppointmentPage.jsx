import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ArrowLeft, ArrowRight, Briefcase, CalendarCheck, CalendarPlus, Check, Languages, Mail, Phone, User } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useSearchParams } from 'react-router-dom'
import { SidebarBlocks } from '@/components/content/ContentWidgets'
import ListingLayout from '@/components/directory/ListingLayout'
import { AppointmentSummary, MODES, MonthCalendar, SlotPicker, downloadIcs, formatLongDate, formatTime } from '@/components/public/BookingWidgets'
import { AgreeCheckbox, Field, Honeypot, SelectInput, TextArea, TextInput } from '@/components/public/FormControls'
import { applyServerErrors } from '@/lib/forms'
import AppIcon from '@/components/ui/AppIcon'
import Button from '@/components/ui/Button'
import { ErrorState, LoadingSkeleton } from '@/components/ui/States'
import { STUDY_LEVELS } from '@/config/studyLevels'
import { useSite } from '@/context/SiteContext'
import { assetUrl, cn } from '@/lib/utils'
import { bookingService } from '@/services/publicService'

const STEPS = ['Select Service', 'Choose Counsellor', 'Date & Time', 'Your Details', 'Confirmation']

function Stepper({ step, onJump }) {
  return (
    <ol className="mb-6 flex items-start overflow-x-auto pb-1" aria-label="Booking steps">
      {STEPS.map((label, i) => {
        const done = i < step
        const current = i === step
        return (
          <li key={label} className="flex min-w-[88px] flex-1 flex-col items-center text-center" aria-current={current ? 'step' : undefined}>
            <div className="flex w-full items-center">
              <span className={cn('h-0.5 flex-1', i === 0 ? 'invisible' : done || current ? 'bg-primary' : 'bg-slate-200')} />
              <button
                type="button"
                disabled={!done || step === 4}
                onClick={() => onJump(i)}
                className={cn('grid size-9 shrink-0 place-items-center rounded-full text-sm font-semibold transition',
                  done ? 'bg-primary text-white' : current ? 'bg-primary text-white ring-4 ring-primary-100' : 'bg-slate-100 text-slate-400')}
                aria-label={`Step ${i + 1}: ${label}`}
              >
                {done ? <Check className="size-4" /> : i + 1}
              </button>
              <span className={cn('h-0.5 flex-1', i === STEPS.length - 1 ? 'invisible' : done ? 'bg-primary' : 'bg-slate-200')} />
            </div>
            <span className={cn('mt-2 text-xs font-medium', current || done ? 'text-ink' : 'text-slate-400')}>{label}</span>
          </li>
        )
      })}
    </ol>
  )
}

function StepCard({ title, subtitle, children, footer }) {
  return (
    <div className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
      <h2 className="text-xl">{title}</h2>
      {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
      <div className="mt-5">{children}</div>
      {footer && <div className="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">{footer}</div>}
    </div>
  )
}

function Choice({ selected, onClick, children, className }) {
  return (
    <button
      type="button"
      onClick={onClick}
      aria-pressed={selected}
      className={cn('relative rounded-xl border-2 p-4 text-left transition', selected ? 'border-primary bg-primary-50' : 'border-slate-100 hover:border-primary-200 hover:bg-primary-50/50', className)}
    >
      {selected && <span className="absolute top-3 right-3 grid size-5 place-items-center rounded-full bg-primary text-white"><Check className="size-3" /></span>}
      {children}
    </button>
  )
}

function AdvisorCard({ counsellor }) {
  if (!counsellor) return null
  return (
    <section className="rounded-2xl border border-slate-100 bg-lavender p-5">
      <h3 className="mb-4 text-base font-semibold">Meet Your Advisor</h3>
      <div className="flex items-center gap-3">
        {counsellor.photo ? (
          <img src={assetUrl(counsellor.photo)} alt="" className="size-16 shrink-0 rounded-full object-cover ring-4 ring-white" />
        ) : (
          <span className="grid size-16 shrink-0 place-items-center rounded-full bg-white text-primary"><User className="size-7" /></span>
        )}
        <div className="min-w-0">
          <p className="font-semibold text-ink">{counsellor.name}</p>
          {counsellor.designation && <p className="text-xs text-primary">{counsellor.designation}</p>}
          {counsellor.experience_years > 0 && <p className="mt-0.5 flex items-center gap-1 text-xs text-slate-500"><Briefcase className="size-3" /> {counsellor.experience_years}+ years experience</p>}
        </div>
      </div>
      {counsellor.bio && <p className="mt-4 text-sm leading-relaxed text-slate-600">{counsellor.bio}</p>}
      {counsellor.languages?.length > 0 && (
        <p className="mt-3 flex items-start gap-1.5 text-xs text-slate-500"><Languages className="mt-px size-3.5 shrink-0 text-primary" /> {counsellor.languages.join(', ')}</p>
      )}
    </section>
  )
}

export default function BookAppointmentPage() {
  const { setting } = useSite()
  const queryClient = useQueryClient()
  const [search] = useSearchParams()
  const options = useQuery({ queryKey: ['booking-options'], queryFn: bookingService.options, staleTime: 60_000 })

  const [step, setStep] = useState(0)
  const [serviceId, setServiceId] = useState(null)
  const [counsellorId, setCounsellorId] = useState(null)
  const [month, setMonth] = useState('')
  const [date, setDate] = useState('')
  const [time, setTime] = useState('')
  const [slotError, setSlotError] = useState('')
  const [result, setResult] = useState(null)

  const data = options.data
  const rules = data?.rules
  const timezoneLabel = setting('appointment.appointment_timezone_label', rules?.timezone)
  const service = data?.services.find((s) => s.id === serviceId)
  const counsellors = useMemo(() => (data?.counsellors ?? []).filter((c) => !serviceId || c.service_ids.includes(serviceId)), [data, serviceId])
  const counsellor = data?.counsellors.find((c) => c.id === counsellorId)

  const form = useForm({
    defaultValues: { name: search.get('name') ?? '', email: search.get('email') ?? '', phone: '', mode: '', destination_id: '', study_level: '', message: '', agree: false, website: '' },
  })
  const { register, handleSubmit, setError, setValue, watch, formState: { errors, isSubmitting } } = form

  // Prefill from the homepage quick form (?service=&destination=&date=).
  useEffect(() => {
    if (!data) return
    const wanted = search.get('service')
    const s = data.services.find((x) => String(x.id) === wanted || x.slug === wanted)
    if (s) setServiceId(s.id)
    const dest = data.destinations.find((d) => d.slug === search.get('destination') || String(d.id) === search.get('destination'))
    if (dest) setValue('destination_id', String(dest.id))
    if (!watch('mode') && rules.modes.length) setValue('mode', rules.modes.includes('online') ? 'online' : rules.modes[0])
    setMonth((m) => m || (search.get('date') ?? rules.today).slice(0, 7))
  }, [data]) // eslint-disable-line react-hooks/exhaustive-deps

  const go = (n) => {
    setStep(n)
    document.getElementById('booking')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  const chooseCounsellor = (id) => {
    if (id !== counsellorId) {
      setCounsellorId(id)
      const wanted = search.get('date')
      setDate(wanted && wanted >= rules.today && wanted <= rules.last_date ? wanted : '')
      setTime('')
    }
  }

  const onSubmit = async (values) => {
    try {
      const res = await bookingService.book({
        ...values,
        destination_id: values.destination_id || null,
        study_level: values.study_level || null,
        service_id: serviceId,
        counsellor_id: counsellorId,
        date,
        time,
      })
      setResult(res.data)
      go(4)
    } catch (e) {
      // Slot taken meanwhile: send the visitor back to pick another time.
      if (e.isValidation && (e.errors.time || e.errors.date || e.errors.counsellor_id)) {
        setSlotError((e.errors.time ?? e.errors.date ?? e.errors.counsellor_id)[0])
        setTime('')
        queryClient.invalidateQueries({ queryKey: ['slots'] })
        queryClient.invalidateQueries({ queryKey: ['availability'] })
        go(2)
        return
      }
      applyServerErrors(e, setError)
    }
  }

  const back = (n) => <Button variant="outline" onClick={() => go(n)}><ArrowLeft className="size-4" /> Back</Button>
  const minMonth = rules?.today.slice(0, 7)
  const maxMonth = rules?.last_date.slice(0, 7)
  const summary = [
    ['Service', service?.title],
    ['Counsellor', counsellor?.name],
    ['Date', formatLongDate(date)],
    ['Time', time && `${formatTime(time)} (${timezoneLabel})`],
  ]

  const renderStep = () => {
    if (step === 0) {
      return (
        <StepCard title="Select a Service" subtitle="Choose the area you need help with." footer={<><span />
          <Button onClick={() => go(1)} disabled={!serviceId}>Next: Choose Counsellor <ArrowRight className="size-4" /></Button></>}>
          <div className="grid gap-3 sm:grid-cols-2">
            {data.services.map((s) => (
              <Choice key={s.id} selected={serviceId === s.id} onClick={() => { setServiceId(s.id); if (counsellor && !counsellor.service_ids.includes(s.id)) setCounsellorId(null) }}>
                <span className="mb-3 grid size-10 place-items-center rounded-lg bg-primary-50 text-primary"><AppIcon name={s.icon} fallback="graduation-cap" className="size-5" /></span>
                <span className="block pr-6 font-semibold text-ink">{s.title}</span>
                {s.short_description && <span className="mt-1 line-clamp-2 block text-xs text-slate-500">{s.short_description}</span>}
              </Choice>
            ))}
          </div>
        </StepCard>
      )
    }
    if (step === 1) {
      return (
        <StepCard title="Choose Your Counsellor" subtitle={service ? `Counsellors for ${service.title}` : undefined} footer={<>{back(0)}
          <Button onClick={() => go(2)} disabled={!counsellorId}>Next: Date & Time <ArrowRight className="size-4" /></Button></>}>
          {!counsellors.length ? (
            <p className="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">No counsellor is available for this service right now. Please choose another service or contact us.</p>
          ) : (
            <div className="grid gap-3 sm:grid-cols-2">
              {counsellors.map((c) => (
                <Choice key={c.id} selected={counsellorId === c.id} onClick={() => chooseCounsellor(c.id)} className="flex items-center gap-3">
                  {c.photo ? <img src={assetUrl(c.photo)} alt="" className="size-14 shrink-0 rounded-full object-cover" /> : <span className="grid size-14 shrink-0 place-items-center rounded-full bg-primary-50 text-primary"><User className="size-6" /></span>}
                  <span className="min-w-0 pr-5">
                    <span className="block font-semibold text-ink">{c.name}</span>
                    {c.designation && <span className="block text-xs text-primary">{c.designation}</span>}
                    <span className="mt-0.5 block text-xs text-slate-500">{c.duration} min{c.experience_years ? ` · ${c.experience_years}+ yrs` : ''}</span>
                  </span>
                </Choice>
              ))}
            </div>
          )}
        </StepCard>
      )
    }
    if (step === 2) {
      return (
        <StepCard title="Select Date & Time" subtitle={`Times are shown in ${timezoneLabel}.`} footer={<>{back(1)}
          <Button onClick={() => { setSlotError(''); go(3) }} disabled={!date || !time}>Next: Your Details <ArrowRight className="size-4" /></Button></>}>
          {slotError && <p className="mb-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{slotError}</p>}
          <div className="grid gap-6 md:grid-cols-2">
            <MonthCalendar counsellorId={counsellorId} month={month} onMonthChange={setMonth} minMonth={minMonth} maxMonth={maxMonth} value={date}
              onChange={(d) => { setDate(d); setTime('') }} />
            <SlotPicker counsellorId={counsellorId} date={date} value={time} onChange={setTime} timezoneLabel={timezoneLabel} />
          </div>
        </StepCard>
      )
    }
    if (step === 3) {
      const mode = watch('mode')
      return (
        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          <StepCard title="Your Details" subtitle="We'll send the confirmation to your email." footer={<>{back(2)}
            <Button type="submit" loading={isSubmitting}><CalendarCheck className="size-4" /> Confirm Booking</Button></>}>
            <Honeypot {...register('website')} />
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Full Name" required icon={User} error={errors.name?.message}>
                <TextInput icon invalid={!!errors.name} autoComplete="name" placeholder="Your full name" {...register('name', { required: 'Please enter your name.' })} />
              </Field>
              <Field label="Email Address" required icon={Mail} error={errors.email?.message}>
                <TextInput icon type="email" invalid={!!errors.email} autoComplete="email" placeholder="you@example.com"
                  {...register('email', { required: 'Please enter your email.', pattern: { value: /^\S+@\S+\.\S+$/, message: 'Enter a valid email.' } })} />
              </Field>
              <Field label="Phone / WhatsApp" required icon={Phone} error={errors.phone?.message}>
                <TextInput icon type="tel" invalid={!!errors.phone} autoComplete="tel" placeholder="Phone number with country code"
                  {...register('phone', { required: 'Please enter your phone number.', pattern: { value: /^[0-9+()\-\s]{6,30}$/, message: 'Enter a valid phone number.' } })} />
              </Field>
              <Field label="Preferred Destination" error={errors.destination_id?.message}>
                <SelectInput {...register('destination_id')}>
                  <option value="">Not sure yet</option>
                  {data.destinations.map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}
                </SelectInput>
              </Field>
              <Field label="Study Level" error={errors.study_level?.message}>
                <SelectInput {...register('study_level')}>
                  <option value="">Select level</option>
                  {STUDY_LEVELS.map((l) => <option key={l.value} value={l.value}>{l.label}</option>)}
                </SelectInput>
              </Field>
            </div>

            <fieldset className="mt-5">
              <legend className="mb-2 text-sm font-medium text-ink">Meeting Type <span className="text-rose-500">*</span></legend>
              <div className="grid gap-3 sm:grid-cols-3">
                {rules.modes.map((m) => {
                  const meta = MODES[m] ?? { label: m, icon: CalendarCheck }
                  return (
                    <label key={m} className={cn('flex cursor-pointer items-center gap-3 rounded-xl border-2 p-3 transition', mode === m ? 'border-primary bg-primary-50' : 'border-slate-100 hover:border-primary-200')}>
                      <input type="radio" value={m} className="sr-only" {...register('mode', { required: 'Please choose a meeting type.' })} />
                      <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-white text-primary shadow-sm"><meta.icon className="size-4" /></span>
                      <span><span className="block text-sm font-semibold text-ink">{meta.label}</span>{meta.hint && <span className="block text-xs text-slate-500">{meta.hint}</span>}</span>
                    </label>
                  )
                })}
              </div>
              {errors.mode && <p className="mt-1 text-xs font-medium text-rose-600">{errors.mode.message}</p>}
            </fieldset>

            <Field label="Message (optional)" className="mt-5" error={errors.message?.message}>
              <TextArea rows={3} placeholder="Tell us briefly about your goals or questions" {...register('message', { maxLength: { value: 2000, message: 'Please keep it under 2000 characters.' } })} />
            </Field>

            <div className="mt-5"><AppointmentSummary rows={summary} /></div>

            <div className="mt-5">
              <AgreeCheckbox error={errors.agree?.message} {...register('agree', { required: 'Please accept the terms to continue.' })}>
                I agree to be contacted about my appointment and accept the <Link to="/privacy-policy" className="text-primary hover:underline">privacy policy</Link>.
              </AgreeCheckbox>
            </div>
            {errors.root && <p className="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{errors.root.message}</p>}
          </StepCard>
        </form>
      )
    }
    return (
      <StepCard title="Booking Received">
        <div className="text-center">
          <span className="mx-auto grid size-16 place-items-center rounded-full bg-emerald-50 text-emerald-500"><Check className="size-8" /></span>
          <p className="mt-4 text-lg font-semibold text-ink">{result.status === 'confirmed' ? 'Your appointment is confirmed!' : 'Thank you! Your appointment request has been received.'}</p>
          <p className="mt-1 text-sm text-slate-500">A confirmation email has been sent to {result.email}. Your reference is <strong className="text-ink">{result.reference}</strong>.</p>
        </div>
        <div className="mt-6">
          <AppointmentSummary rows={[
            ['Reference', result.reference],
            ['Status', result.status_label],
            ['Service', result.service?.title],
            ['Counsellor', result.counsellor?.name],
            ['Date', formatLongDate(result.date)],
            ['Time', `${formatTime(result.start_time)} – ${formatTime(result.end_time)} (${timezoneLabel})`],
            ['Meeting', MODES[result.mode]?.label ?? result.mode],
          ]} />
        </div>
        {result.cancellation_policy && <p className="mt-4 text-xs text-slate-500">{result.cancellation_policy}</p>}
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <Button variant="outline" onClick={() => downloadIcs(result, setting('general.site_name', ''))}><CalendarPlus className="size-4" /> Add to Calendar</Button>
          <Button variant="secondary" to={`/appointment/${result.manage_token}`}>Manage Appointment</Button>
          <Button to="/">Back to Home</Button>
        </div>
      </StepCard>
    )
  }

  return (
    <ListingLayout slug="book-appointment">
      {({ sidebarBlocks }) => (
        <div id="booking" className="grid scroll-mt-28 gap-8 lg:grid-cols-[1fr_340px]">
          <div className="min-w-0">
            <Stepper step={step} onJump={go} />
            {options.isPending ? <LoadingSkeleton count={4} /> : options.isError ? <ErrorState error={options.error} onRetry={options.refetch} /> : renderStep()}
          </div>
          <aside className="space-y-6">
            <AdvisorCard counsellor={counsellor ?? counsellors[0]} />
            <SidebarBlocks blocks={sidebarBlocks} />
          </aside>
        </div>
      )}
    </ListingLayout>
  )
}
