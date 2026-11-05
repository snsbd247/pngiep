import { Helmet } from 'react-helmet-async'
import { useSite } from '@/context/SiteContext'

/**
 * Client-side meta for in-app navigation. Initial page loads already receive
 * server-rendered meta from Laravel (SpaController), so crawlers see it without JS.
 * Pass the `seo` object returned by the API for content pages.
 */
export default function Seo({ title, description, image, seo, noindex = false }) {
  const { setting } = useSite()
  const siteName = setting('general.site_name', '')
  const finalTitle = seo?.title || (title ? `${title} | ${siteName}` : setting('seo.seo_default_title', siteName))
  const finalDescription = seo?.description || description || setting('seo.seo_default_description', '')
  const finalImage = seo?.og_image || image || setting('seo.seo_default_og_image')

  return (
    <Helmet>
      <title>{finalTitle}</title>
      {finalDescription && <meta name="description" content={finalDescription} />}
      <meta property="og:title" content={seo?.og_title || finalTitle} />
      {finalDescription && <meta property="og:description" content={seo?.og_description || finalDescription} />}
      {finalImage && <meta property="og:image" content={finalImage} />}
      {(noindex || seo?.robots) && <meta name="robots" content={noindex ? 'noindex, nofollow' : seo.robots} />}
    </Helmet>
  )
}
