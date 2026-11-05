import { Loader2 } from 'lucide-react'
import { forwardRef } from 'react'
import { cn } from '@/lib/utils'
import SmartLink from './SmartLink'

const VARIANTS = {
  primary: 'bg-primary text-white shadow-sm hover:bg-primary-700',
  gradient: 'bg-brand-gradient text-white shadow-glow hover:brightness-110',
  secondary: 'bg-primary-50 text-primary-700 hover:bg-primary-100',
  outline: 'border border-slate-300 bg-white text-slate-700 hover:border-primary hover:text-primary',
  'outline-light': 'border border-white/40 bg-white/10 text-white backdrop-blur hover:bg-white/20',
  white: 'bg-white text-primary shadow-card hover:bg-primary-50',
  'outline-primary': 'border border-primary bg-white text-primary hover:bg-primary hover:text-white',
  ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
  danger: 'bg-rose-600 text-white hover:bg-rose-700',
}

const SIZES = {
  sm: 'h-9 px-3.5 text-xs gap-1.5',
  md: 'h-11 px-5 text-sm gap-2',
  lg: 'h-12 px-6 text-sm gap-2 sm:text-[15px]',
  icon: 'size-10 justify-center',
}

/**
 * Button that renders as <a>/<Link> when `to` is set.
 * <Button to="/contact">…</Button>  <Button loading type="submit">…</Button>
 */
const Button = forwardRef(function Button(
  { variant = 'primary', size = 'md', loading = false, to, className, children, disabled, type = 'button', ...props },
  ref,
) {
  const classes = cn(
    'inline-flex shrink-0 items-center justify-center rounded-lg font-semibold whitespace-nowrap transition-all duration-200 disabled:pointer-events-none disabled:opacity-60',
    VARIANTS[variant],
    SIZES[size],
    className,
  )

  const content = (
    <>
      {loading && <Loader2 className="size-4 animate-spin" />}
      {children}
    </>
  )

  if (to) {
    return (
      <SmartLink ref={ref} to={to} className={classes} {...props}>
        {content}
      </SmartLink>
    )
  }

  return (
    <button ref={ref} type={type} className={classes} disabled={disabled || loading} {...props}>
      {content}
    </button>
  )
})

export default Button
