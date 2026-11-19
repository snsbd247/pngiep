import { useEffect } from 'react'
import { Helmet } from 'react-helmet-async'
import { useLocation } from 'react-router-dom'
import { useSite } from '@/context/SiteContext'

/** Search results show ~60 characters: use the short site name when the full one does not fit. */
const TITLE_MAX = 60

// eslint-disable-next-line react-refresh/only-export-components
export function pageTitle(title, siteName, shortName) {
  if (!title) return siteName
  return fitTitle(`${title} | ${siteName}`, siteName, shortName)
}

/** Same rule as the server (SeoService::fitTitle), also for titles typed in an SEO panel. */
function fitTitle(full, siteName, shortName) {
  const suffix = ` | ${siteName}`
  if (!siteName || full.length <= TITLE_MAX || !full.endsWith(suffix)) return full
  const page = full.slice(0, -suffix.length)
  const shortened = shortName ? `${page} | ${shortName}` : page
  // Still too long (e.g. a long course name): the page name alone.
  return shortened.length <= TITLE_MAX ? shortened : page
}

/**
 * Client-side meta. The first page load keeps Laravel's server-rendered canonical and
 * JSON-LD (Google indexes the page after running JavaScript). After in-app navigation those
 * belong to the previous page, so they are removed and the canonical is set here.
 * Pass the `seo` object returned by the API for content pages.
 */
export default function Seo({ title, description, image, seo, noindex = false }) {
  const { setting } = useSite()
  const location = useLocation()
  const navigated = location.key !== 'default'
  const siteName = setting('general.site_name', '')
  const shortName = setting('seo.seo_short_name', '')
  const finalTitle = seo?.title ? fitTitle(seo.title, siteName, shortName) : title ? pageTitle(title, siteName, shortName) : setting('seo.seo_default_title', siteName)
  const finalDescription = seo?.description || description || setting('seo.seo_default_description', '')
  const finalImage = seo?.og_image || image || setting('seo.seo_default_og_image')
  const canonical = navigated ? window.location.origin + location.pathname.replace(/\/+$/, '') : null

  useEffect(() => {
    if (navigated) document.querySelectorAll('[data-seo]').forEach((el) => el.remove())
  }, [navigated, location.pathname])

  return (
    <Helmet>
      <title>{finalTitle}</title>
      {finalDescription && <meta name="description" content={finalDescription} />}
      <meta property="og:title" content={seo?.og_title || finalTitle} />
      {finalDescription && <meta property="og:description" content={seo?.og_description || finalDescription} />}
      {finalImage && <meta property="og:image" content={finalImage} />}
      {(noindex || seo?.robots) && <meta name="robots" content={noindex ? 'noindex, nofollow' : seo.robots} />}
      {canonical && <link rel="canonical" href={canonical || undefined} />}
    </Helmet>
  )
}
