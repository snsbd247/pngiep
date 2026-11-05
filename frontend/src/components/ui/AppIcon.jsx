import { DynamicIcon } from 'lucide-react/dynamic'

/**
 * Icon chosen by an admin (stored as a lucide name like "graduation-cap").
 * Each icon is lazy-loaded as its own small chunk.
 */
export default function AppIcon({ name, fallback = 'circle', className = 'size-5', ...props }) {
  const iconName = String(name || fallback)
    .trim()
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .toLowerCase()

  return <DynamicIcon name={iconName} fallback={() => <span className={className} />} className={className} {...props} />
}
