import { Mail, MapPin, Phone } from 'lucide-react'
import { useSite } from '@/context/SiteContext'
import { telHref } from '@/lib/utils'
import SocialLinks from './SocialLinks'

/** Slim utility bar above the navbar. Controlled from Settings > Header / Contact / Social. */
export default function TopBar() {
  const { setting } = useSite()
  if (!setting('header.topbar_enabled', false)) return null

  const phone = setting('contact.contact_phone')
  const email = setting('contact.contact_email')
  const location = setting('contact.contact_location')
  const message = setting('header.topbar_message')

  return (
    <div className="hidden bg-secondary text-white/85 md:block">
      <div className="mx-auto flex h-10 w-full max-w-[1440px] items-center justify-between gap-6 px-4 text-xs sm:px-6 lg:px-8">
        <div className="flex items-center gap-2">
          {setting('header.topbar_social_label') && <span className="font-medium text-white">{setting('header.topbar_social_label')}</span>}
          <SocialLinks itemClassName="size-6 hover:bg-white/10 hover:text-white" iconClassName="size-3.5" />
          {message && <span className="ml-4 hidden font-medium text-white xl:inline">{message}</span>}
        </div>
        <div className="flex items-center gap-5">
          {phone && (
            <a href={telHref(phone)} className="flex items-center gap-1.5 hover:text-white">
              <Phone className="size-3.5" /> {phone}
            </a>
          )}
          {email && (
            <a href={`mailto:${email}`} className="flex items-center gap-1.5 hover:text-white">
              <Mail className="size-3.5" /> {email}
            </a>
          )}
          {location && (
            <span className="hidden items-center gap-1.5 lg:flex">
              <MapPin className="size-3.5" /> {location}
            </span>
          )}
        </div>
      </div>
    </div>
  )
}
