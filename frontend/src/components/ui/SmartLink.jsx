import { forwardRef } from 'react'
import { Link } from 'react-router-dom'
import { withBase } from '@/lib/base'
import { isExternalUrl } from '@/lib/utils'

/** Renders a router <Link> for internal paths and a safe <a> for external URLs. */
const SmartLink = forwardRef(function SmartLink({ to = '#', target, children, ...props }, ref) {
  // External links, new-tab links and server files (e.g. /sitemap.xml) bypass the SPA router.
  if (isExternalUrl(to) || target === '_blank' || /\.[a-z0-9]{2,5}$/i.test(to)) {
    const newTab = target === '_blank'
    return (
      <a ref={ref} href={withBase(to)} target={target} rel={newTab ? 'noopener noreferrer' : undefined} {...props}>
        {children}
      </a>
    )
  }
  if (to === '#') {
    return (
      <span ref={ref} {...props}>
        {children}
      </span>
    )
  }
  return (
    <Link ref={ref} to={to} {...props}>
      {children}
    </Link>
  )
})

export default SmartLink
