import SocialIcon, { SOCIAL_KEYS } from '@/components/ui/SocialIcon'
import { useSite } from '@/context/SiteContext'
import { cn, headline } from '@/lib/utils'

/** Renders whichever social profiles are filled in under Settings > Social. */
export default function SocialLinks({ className, itemClassName, iconClassName }) {
  const { setting } = useSite()
  const links = SOCIAL_KEYS.map((key) => ({ key, url: setting(`social.social_${key}`) })).filter((l) => l.url)

  if (!links.length) return null

  return (
    <div className={cn('flex items-center gap-2', className)}>
      {links.map(({ key, url }) => (
        <a
          key={key}
          href={url}
          target="_blank"
          rel="noopener noreferrer"
          aria-label={headline(key)}
          className={cn('grid size-8 place-items-center rounded-full transition-colors', itemClassName)}
        >
          <SocialIcon name={key} className={iconClassName} />
        </a>
      ))}
    </div>
  )
}
