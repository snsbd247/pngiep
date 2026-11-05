/**
 * URL prefix the app is served under: '' at a domain root, '/riths' when installed in a
 * sub-folder (e.g. http://localhost/riths). Laravel injects it at runtime (SpaController),
 * so one build works anywhere. The Vite dev server has no prefix.
 */
export const APP_BASE = (typeof window !== 'undefined' && window.__APP_BASE__) || ''

/** Prefix a root-relative path ("/storage/x", "/sitemap.xml") with the app base. */
export const withBase = (path) => (path?.startsWith('/') && !path.startsWith('//') ? `${APP_BASE}${path}` : path)
