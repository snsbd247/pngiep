import { Link } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'
import { cn } from '@/lib/utils'

/**
 * Uploaded logo from settings, optionally followed by the website name
 * (Settings › General › "Show the website name next to the logo"). Without a logo,
 * the name is rendered as a wordmark (first word bold, rest regular).
 * `light`: dark backgrounds (footer). A logo without a dedicated light version
 * sits on a white badge so its colours stay readable.
 */
export default function SiteLogo({ light = false, className, imgClassName, nameClassName }) {
  const { setting } = useSite()
  const name = setting('general.site_name', '')
  const lightLogo = light && setting('general.site_logo_light')
  const logo = lightLogo || setting('general.site_logo')
  const showName = !logo || setting('general.logo_show_name', true) !== false
  const [first, ...rest] = name.split(' ')

  return (
    <Link to="/" className={cn('flex min-w-0 shrink-0 items-center gap-2.5', className)} aria-label={name || 'Home'}>
      {logo && (
        <span className={cn('shrink-0', light && !lightLogo && 'rounded-xl bg-white p-1.5')}>
          <img src={logo} alt={showName ? '' : name} className={cn('h-10 w-auto max-w-[190px] object-contain md:h-12', imgClassName)} />
        </span>
      )}
      {showName && (
        <span
          className={cn(
            'min-w-0 tracking-tight',
            logo ? 'text-base leading-tight sm:text-lg' : 'text-xl sm:text-2xl',
            light ? 'whitespace-normal text-white' : 'text-primary',
            // Long names: wrap next to a logo, truncate as a plain wordmark in the header.
            !light && (logo ? 'line-clamp-2 max-w-[11rem] sm:max-w-[14rem]' : 'truncate'),
            nameClassName,
          )}
        >
          <span className="font-extrabold">{first}</span>
          {rest.length > 0 && <span className="font-normal"> {rest.join(' ')}</span>}
        </span>
      )}
    </Link>
  )
}
