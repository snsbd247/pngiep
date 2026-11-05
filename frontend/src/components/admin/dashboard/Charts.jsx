import { useEffect, useMemo, useRef, useState } from 'react'

/** Width of an element, kept up to date (charts render at real pixel size, so text never stretches). */
function useWidth() {
  const ref = useRef(null)
  const [width, setWidth] = useState(0)
  useEffect(() => {
    if (!ref.current) return undefined
    const ro = new ResizeObserver(([entry]) => setWidth(Math.floor(entry.contentRect.width)))
    ro.observe(ref.current)
    return () => ro.disconnect()
  }, [])
  return [ref, width]
}

/** Smooth path through points (Catmull-Rom → cubic Bézier). */
function smooth(points) {
  if (points.length < 2) return points.length ? `M${points[0][0]},${points[0][1]}` : ''
  let d = `M${points[0][0]},${points[0][1]}`
  for (let i = 0; i < points.length - 1; i++) {
    const [p0, p1, p2, p3] = [points[i - 1] ?? points[i], points[i], points[i + 1], points[i + 2] ?? points[i + 1]]
    const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6]
    const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6]
    d += ` C${c1[0]},${c1[1]} ${c2[0]},${c2[1]} ${p2[0]},${p2[1]}`
  }
  return d
}

/** Rounded axis maximum with 5 even steps (0, 20, 40 … 100). */
function niceMax(value) {
  if (value <= 5) return 5
  const step = 10 ** Math.floor(Math.log10(value / 5))
  const nice = [1, 2, 2.5, 5, 10].map((m) => m * step).find((s) => s * 5 >= value) ?? step * 10
  return nice * 5
}

const fmtDay = (iso, unit) => {
  const d = new Date(`${iso}T00:00:00`)
  return unit === 'month' ? d.toLocaleDateString('en-GB', { month: 'short', year: '2-digit' }) : d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })
}

/**
 * Area/line chart. series: [{ key, label, color, area? }]; points: [{ date, [key]: number }].
 */
export function LineChart({ points = [], series, unit = 'day', height = 210 }) {
  const [ref, width] = useWidth()
  const [hover, setHover] = useState(null)
  const pad = { top: 10, right: 12, bottom: 26, left: 34 }
  const w = Math.max(width - pad.left - pad.right, 10)
  const h = height - pad.top - pad.bottom
  const max = useMemo(() => niceMax(Math.max(1, ...points.flatMap((p) => series.map((s) => p[s.key] ?? 0)))), [points, series])
  const x = (i) => pad.left + (points.length > 1 ? (i / (points.length - 1)) * w : w / 2)
  const y = (v) => pad.top + h - (v / max) * h
  const ticks = [0, 1, 2, 3, 4, 5].map((i) => (max / 5) * i)
  // Evenly spaced date labels, as many as fit (~1 per 70px); first and last always shown.
  const every = Math.max(1, Math.ceil(points.length / Math.max(2, Math.floor(w / 70))))
  const labelIdx = points.map((_, i) => i).filter((i) => i === 0 || i === points.length - 1 || (i % every === 0 && points.length - 1 - i >= every))

  const onMove = (e) => {
    const rect = e.currentTarget.getBoundingClientRect()
    const rel = (e.clientX - rect.left - pad.left) / w
    setHover(Math.max(0, Math.min(points.length - 1, Math.round(rel * (points.length - 1)))))
  }

  return (
    <div ref={ref} className="relative w-full" style={{ height }}>
      {width > 0 && (
        <svg width={width} height={height} onMouseMove={onMove} onMouseLeave={() => setHover(null)} role="img" aria-label={`Chart of ${series.map((s) => s.label).join(', ')}`}>
          <defs>
            {series.map((s) => (
              <linearGradient key={s.key} id={`g-${s.key}`} x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stopColor={s.color} stopOpacity={s.area ?? 0.12} />
                <stop offset="100%" stopColor={s.color} stopOpacity="0" />
              </linearGradient>
            ))}
          </defs>
          {ticks.map((t) => (
            <g key={t}>
              <line x1={pad.left} x2={pad.left + w} y1={y(t)} y2={y(t)} stroke="#eef0f5" />
              <text x={pad.left - 10} y={y(t) + 4} textAnchor="end" className="fill-slate-500 text-[11px]">{Math.round(t)}</text>
            </g>
          ))}
          {labelIdx.map((i) => (
            <text key={i} x={x(i)} y={height - 6} textAnchor={i === 0 ? 'start' : i === points.length - 1 ? 'end' : 'middle'} className="fill-slate-500 text-[11px]">
              {fmtDay(points[i].date, unit)}
            </text>
          ))}
          {series.map((s) => {
            const coords = points.map((p, i) => [x(i), y(p[s.key] ?? 0)])
            const line = smooth(coords)
            return (
              <g key={s.key}>
                {coords.length > 1 && <path d={`${line} L${x(points.length - 1)},${y(0)} L${x(0)},${y(0)} Z`} fill={`url(#g-${s.key})`} />}
                <path d={line} fill="none" stroke={s.color} strokeWidth="2" strokeLinecap="round" />
              </g>
            )
          })}
          {hover !== null && points[hover] && (
            <g>
              <line x1={x(hover)} x2={x(hover)} y1={pad.top} y2={pad.top + h} stroke="#c7cbe0" strokeDasharray="3 3" />
              {series.map((s) => <circle key={s.key} cx={x(hover)} cy={y(points[hover][s.key] ?? 0)} r="4" fill="#fff" stroke={s.color} strokeWidth="2" />)}
            </g>
          )}
        </svg>
      )}
      {hover !== null && points[hover] && (
        <div
          className="pointer-events-none absolute top-1 z-10 rounded-lg border border-slate-100 bg-white px-3 py-2 text-xs shadow-lg"
          style={{ left: Math.min(Math.max(x(hover) - 70, 0), Math.max(width - 150, 0)) }}
        >
          <p className="mb-1 font-semibold text-slate-800">{fmtDay(points[hover].date, unit)}</p>
          {series.map((s) => (
            <p key={s.key} className="flex items-center gap-2 text-slate-600">
              <span className="size-2 rounded-full" style={{ background: s.color }} /> {s.label}: <b className="text-slate-800">{points[hover][s.key] ?? 0}</b>
            </p>
          ))}
        </div>
      )}
    </div>
  )
}

/** Donut with small gaps between segments; total in the centre. items: [{ label, count, color }] */
export function Donut({ items = [], total = 0, caption, size = 160, stroke = 26 }) {
  const r = (size - stroke) / 2
  const c = 2 * Math.PI * r
  const gap = items.filter((i) => i.count > 0).length > 1 ? 3 : 0
  let offset = 0

  return (
    <div className="relative shrink-0" style={{ width: size, height: size }}>
      <svg width={size} height={size} className="-rotate-90" role="img" aria-label={`${caption}: ${total}`}>
        <circle cx={size / 2} cy={size / 2} r={r} fill="none" stroke="#eef0f5" strokeWidth={stroke} />
        {total > 0 && items.map((item) => {
          if (!item.count) return null
          const len = (item.count / total) * c
          const seg = (
            <circle
              key={item.label}
              cx={size / 2}
              cy={size / 2}
              r={r}
              fill="none"
              stroke={item.color}
              strokeWidth={stroke}
              strokeDasharray={`${Math.max(len - gap, 0.5)} ${c}`}
              strokeDashoffset={-offset}
            >
              <title>{`${item.label}: ${item.count}`}</title>
            </circle>
          )
          offset += len
          return seg
        })}
      </svg>
      <div className="absolute inset-0 grid place-content-center text-center">
        <span className="text-[24px] leading-none font-bold text-slate-900">{total}</span>
        {caption && <span className="mt-1 text-[11px] text-slate-500">{caption}</span>}
      </div>
    </div>
  )
}
