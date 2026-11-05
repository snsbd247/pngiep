import { useQuery } from '@tanstack/react-query'
import { Clock, Mail, Mailbox, MapPin, MessageCircle, Navigation, Phone, Send, User } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useSearchParams } from 'react-router-dom'
import { SidebarBlocks } from '@/components/content/ContentWidgets'
import ListingLayout from '@/components/directory/ListingLayout'
import { AgreeCheckbox, Field, Honeypot, SelectInput, SuccessPanel, TextArea, TextInput } from '@/components/public/FormControls'
import { applyServerErrors } from '@/lib/forms'
import Button from '@/components/ui/Button'
import { useSite } from '@/context/SiteContext'
import { phoneNumbers } from '@/lib/contact'
import { assetUrl, telHref, whatsappHref } from '@/lib/utils'
import { bookingService, inquiryService } from '@/services/publicService'

const lines = (text) => String(text ?? '').split(/\r?\n/).map((s) => s.trim()).filter(Boolean)

function InfoCards() {
  const { setting } = useSite()
  const phones = phoneNumbers(setting)
  const email = setting('contact.contact_email')
  const whatsapp = setting('contact.contact_whatsapp')
  const cards = [
    phones.length > 0 && { icon: Phone, title: 'Call Us', body: phones.map((p) => <a key={p} href={telHref(p)} className="block hover:text-primary">{p}</a>) },
    email && { icon: Mail, title: 'Email Us', body: <><a href={`mailto:${email}`} className="block break-all hover:text-primary">{email}</a>{setting('contact.contact_email_note') && <span className="text-xs text-slate-400">{setting('contact.contact_email_note')}</span>}</> },
    setting('contact.contact_address') && { icon: MapPin, title: 'Visit Us', body: <span className="whitespace-pre-line">{setting('contact.contact_address')}</span> },
    whatsapp && { icon: MessageCircle, title: setting('contact.contact_chat_title', 'WhatsApp'), body: <><a href={whatsappHref(whatsapp)} target="_blank" rel="noopener noreferrer" className="block font-semibold text-primary hover:underline">Chat on WhatsApp</a>{setting('contact.contact_chat_note') && <span className="text-xs text-slate-400">{setting('contact.contact_chat_note')}</span>}</> },
  ].filter(Boolean)
  if (!cards.length) return null

  return (
    <div className="container-page relative z-10 -mt-12">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {cards.map(({ icon: Icon, title, body }) => (
          <div key={title} className="flex gap-4 rounded-2xl border border-slate-100 bg-white p-5 shadow-card">
            <span className="grid size-12 shrink-0 place-items-center rounded-xl bg-primary text-white shadow-glow"><Icon className="size-5" /></span>
            <div className="min-w-0 text-sm text-slate-600">
              <h2 className="mb-1 text-base font-semibold">{title}</h2>
              {body}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

/** "Our Office" sidebar card: CMS title/subtitle/photo + contact details from Settings. */
function OfficeCard({ block }) {
  const { setting } = useSite()
  const address = setting('contact.contact_address')
  const directions = address && `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`
  const rows = [
    [MapPin, address],
    [Mailbox, setting('contact.contact_postal_address')],
    [Phone, phoneNumbers(setting).join('\n')],
    [Mail, setting('contact.contact_email')],
    [Clock, setting('contact.office_hours')],
  ].filter(([, v]) => v)

  return (
    <section className="overflow-hidden rounded-2xl border border-slate-100 bg-lavender">
      {block.data?.image && <img src={assetUrl(block.data.image)} alt="" loading="lazy" className="h-44 w-full object-cover" />}
      <div className="p-5">
        {block.title && <h3 className="text-lg">{block.title}</h3>}
        {block.subtitle && <p className="mt-1 text-sm text-slate-500">{block.subtitle}</p>}
        <ul className="mt-4 space-y-3 text-sm text-slate-600">
          {rows.map(([Icon, value]) => (
            <li key={value} className="flex gap-3"><Icon className="mt-0.5 size-4 shrink-0 text-primary" /> <span className="whitespace-pre-line">{value}</span></li>
          ))}
        </ul>
        {directions && (
          <Button to={directions} variant="primary" size="sm" className="mt-5 w-full"><Navigation className="size-4" /> Get Directions</Button>
        )}
      </div>
    </section>
  )
}

function ContactForm() {
  const { setting } = useSite()
  const [search] = useSearchParams()
  const [sent, setSent] = useState('')
  const types = lines(setting('contact.contact_inquiry_types'))
  const options = useQuery({ queryKey: ['booking-options'], queryFn: bookingService.options, staleTime: 60_000 })
  const destinations = options.data?.destinations ?? []
  const { register, handleSubmit, setError, reset, formState: { errors, isSubmitting } } = useForm({
    defaultValues: { name: '', email: '', phone: '', inquiry_type: search.get('type') ?? '', destination: search.get('destination') ?? '', subject: '', message: '', agree: false, website: '' },
  })

  const onSubmit = async (values) => {
    try {
      const res = await inquiryService.contact(values)
      setSent(res.message)
      reset()
    } catch (e) {
      applyServerErrors(e, setError)
    }
  }

  if (sent) {
    return (
      <SuccessPanel title="Message sent">
        <p>{sent}</p>
        <Button variant="outline" size="sm" className="mt-4" onClick={() => setSent('')}>Send another message</Button>
      </SuccessPanel>
    )
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} noValidate className="rounded-2xl border border-slate-100 bg-white p-5 shadow-card sm:p-7">
      <Honeypot {...register('website')} />
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Full Name" required icon={User} error={errors.name?.message}>
          <TextInput icon invalid={!!errors.name} autoComplete="name" placeholder="Enter your full name" {...register('name', { required: 'Please enter your name.' })} />
        </Field>
        <Field label="Email Address" required icon={Mail} error={errors.email?.message}>
          <TextInput icon type="email" invalid={!!errors.email} autoComplete="email" placeholder="Enter your email"
            {...register('email', { required: 'Please enter your email.', pattern: { value: /^\S+@\S+\.\S+$/, message: 'Enter a valid email.' } })} />
        </Field>
        <Field label="Phone Number" required icon={Phone} error={errors.phone?.message}>
          <TextInput icon type="tel" invalid={!!errors.phone} autoComplete="tel" placeholder="Enter your phone number"
            {...register('phone', { required: 'Please enter your phone number.', pattern: { value: /^[0-9+()\-\s]{6,30}$/, message: 'Enter a valid phone number.' } })} />
        </Field>
        <Field label="Inquiry Type" required error={errors.inquiry_type?.message}>
          {types.length ? (
            <SelectInput invalid={!!errors.inquiry_type} {...register('inquiry_type', { required: 'Please choose an inquiry type.' })}>
              <option value="">Select inquiry type</option>
              {types.map((t) => <option key={t} value={t}>{t}</option>)}
            </SelectInput>
          ) : (
            <TextInput invalid={!!errors.inquiry_type} placeholder="What is it about?" {...register('inquiry_type', { required: 'Please tell us what it is about.' })} />
          )}
        </Field>
        {destinations.length > 0 && (
          <Field label="Preferred Destination">
            <SelectInput {...register('destination')}>
              <option value="">Select destination (optional)</option>
              {destinations.map((d) => <option key={d.id} value={d.slug}>{d.name}</option>)}
            </SelectInput>
          </Field>
        )}
        <Field label="Subject" error={errors.subject?.message}>
          <TextInput placeholder="Subject (optional)" {...register('subject', { maxLength: { value: 190, message: 'Too long.' } })} />
        </Field>
      </div>
      <Field label="Message" required className="mt-4" error={errors.message?.message}>
        <TextArea rows={5} invalid={!!errors.message} placeholder="Write your message here…"
          {...register('message', { required: 'Please write your message.', minLength: { value: 10, message: 'Please write at least 10 characters.' } })} />
      </Field>
      <div className="mt-4">
        <AgreeCheckbox error={errors.agree?.message} {...register('agree', { required: 'Please accept the terms to continue.' })}>
          I agree to the <Link to="/privacy-policy" className="text-primary hover:underline">privacy policy</Link> and to be contacted about my enquiry.
        </AgreeCheckbox>
      </div>
      {errors.root && <p className="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{errors.root.message}</p>}
      <Button type="submit" size="lg" className="mt-5 w-full sm:w-auto" loading={isSubmitting}><Send className="size-4" /> Send Message</Button>
    </form>
  )
}

export default function ContactPage() {
  return (
    <ListingLayout slug="contact" belowHero={<InfoCards />}>
      {({ sidebarBlocks }) => {
        const [office, ...rest] = sidebarBlocks
        const hasOffice = office?.type === 'sidebar_card' && office.data?.kind !== 'newsletter' && office.data?.kind !== 'list'
        return (
          <div className="grid gap-8 lg:grid-cols-[1fr_360px]">
            <div className="min-w-0"><ContactForm /></div>
            <aside className="space-y-6">
              {hasOffice && <OfficeCard block={office} />}
              <SidebarBlocks blocks={hasOffice ? rest : sidebarBlocks} />
            </aside>
          </div>
        )
      }}
    </ListingLayout>
  )
}
