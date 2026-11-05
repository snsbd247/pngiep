import { lazy } from 'react'
import { useParams } from 'react-router-dom'

const CmsPage = lazy(() => import('./CmsPage'))
const DestinationDetailPage = lazy(() => import('./directory/DestinationDetailPage'))

const DESTINATION_PREFIX = 'study-in-'

/**
 * Single-segment URLs: /study-in-{slug} is a destination (clean URL per spec),
 * anything else is a CMS page (about, privacy-policy, …).
 */
export default function SlugPage() {
  const { slug = '' } = useParams()
  if (slug.startsWith(DESTINATION_PREFIX) && slug.length > DESTINATION_PREFIX.length) {
    return <DestinationDetailPage key={slug} slug={slug.slice(DESTINATION_PREFIX.length)} />
  }
  return <CmsPage key={slug} />
}
