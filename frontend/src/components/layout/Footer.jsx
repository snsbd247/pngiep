import { ArrowUp, ChevronRight, Clock, Mail, Mailbox, MapPin, Phone } from 'lucide-react'
import SmartLink from '@/components/ui/SmartLink'
import { useSite } from '@/context/SiteContext'
import { interpolate, telHref } from '@/lib/utils'
import SiteLogo from './SiteLogo'
import { phoneNumbers } from '@/lib/contact'
import SocialLinks from './SocialLinks'

function FooterMenu({ location }) {
  const { menu, menuName } = useSite()
  const items = menu(location)
  if (!items.length) return null

  return (
    <div>
      <h3 className="mb-5 text-base font-semibold text-white">{menuName(location)}</h3>
      <ul className="space-y-2.5">
        {items.map((item) => (
          <li key={item.id}>
            <SmartLink to={item.url} target={item.target} className="group flex items-center gap-1.5 text-sm text-white/70 transition-colors hover:text-white">
              <ChevronRight className="size-3.5 text-white/40 transition-transform group-hover:translate-x-0.5" />
              {item.label}
            </SmartLink>
          </li>
        ))}
      </ul>
    </div>
  )
}

export default function Footer() {
  const { setting, menu } = useSite()
  const phones = phoneNumbers(setting)
  const postal = setting('contact.contact_postal_address')
  const email = setting('contact.contact_email')
  const address = setting('contact.contact_address')
  const hours = setting('contact.office_hours')
  const legal = menu('footer_legal')

  return (
    <footer className="relative overflow-hidden bg-secondary text-white">
      <div className="pointer-events-none absolute -top-40 -right-40 size-96 rounded-full bg-primary/30 blur-3xl" aria-hidden="true" />
      <div className="container-page relative grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-12 lg:py-16">
        <div className="lg:col-span-4">
          <SiteLogo light imgClassName="md:h-12" />
          {setting('footer.footer_description') && (
            <p className="mt-4 max-w-xs text-sm leading-relaxed text-white/70">{setting('footer.footer_description')}</p>
          )}
          <SocialLinks className="mt-6" itemClassName="size-10 bg-white/10 text-white hover:bg-primary" />
        </div>

        <div className="lg:col-span-2">
          <FooterMenu location="footer_quick" />
        </div>
        <div className="lg:col-span-3">
          <FooterMenu location="footer_services" />
        </div>

        <div className="lg:col-span-3">
          <h3 className="mb-5 text-base font-semibold text-white">Contact Information</h3>
          <ul className="space-y-4 text-sm text-white/75">
            {address && (
              <li className="flex gap-3">
                <MapPin className="mt-0.5 size-4 shrink-0 text-white" />
                <span className="whitespace-pre-line">{address}</span>
              </li>
            )}
            {postal && (
              <li className="flex gap-3">
                <Mailbox className="mt-0.5 size-4 shrink-0 text-white" />
                <span className="whitespace-pre-line">{postal}</span>
              </li>
            )}
            {phones.length > 0 && (
              <li className="flex gap-3">
                <Phone className="mt-0.5 size-4 shrink-0 text-white" />
                <span className="flex flex-col">
                  {phones.map((p) => (
                    <a key={p} href={telHref(p)} className="hover:text-white">{p}</a>
                  ))}
                </span>
              </li>
            )}
            {email && (
              <li className="flex gap-3">
                <Mail className="mt-0.5 size-4 shrink-0 text-white" />
                <a href={`mailto:${email}`} className="break-all hover:text-white">{email}</a>
              </li>
            )}
            {hours && (
              <li className="flex gap-3">
                <Clock className="mt-0.5 size-4 shrink-0 text-white" />
                <span className="whitespace-pre-line">{hours}</span>
              </li>
            )}
          </ul>
        </div>
      </div>

      <div className="relative bg-black/60">
        <div className="container-page flex flex-col items-center justify-between gap-3 py-5 text-center text-sm text-white/70 md:flex-row md:text-left">
          <p>{interpolate(setting('footer.footer_copyright', ''))}</p>
          <div className="flex items-center gap-5">
            {legal.length > 0 && (
              <nav className="flex flex-wrap justify-center divide-x divide-white/25" aria-label="Legal">
                {legal.map((item) => (
                  <SmartLink key={item.id} to={item.url} target={item.target} className="px-3 hover:text-white">
                    {item.label}
                  </SmartLink>
                ))}
              </nav>
            )}
            <button
              type="button"
              onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
              className="grid size-9 shrink-0 place-items-center rounded-full bg-white text-secondary shadow-lg transition-transform hover:-translate-y-0.5"
              aria-label="Back to top"
            >
              <ArrowUp className="size-4" />
            </button>
          </div>
        </div>
      </div>
    </footer>
  )
}
