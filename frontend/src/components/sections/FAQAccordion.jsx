import { Minus, Plus } from 'lucide-react'
import { useId, useState } from 'react'
import { cn } from '@/lib/utils'

function FaqItem({ faq, open, onToggle }) {
  const id = useId()
  return (
    <div className={cn('rounded-xl border bg-white transition-colors', open ? 'border-primary-200 shadow-card' : 'border-slate-200')}>
      <h3>
        <button type="button" aria-expanded={open} aria-controls={id} onClick={onToggle} className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-semibold text-ink sm:text-[15px]">
          {faq.question}
          <span className="grid size-6 shrink-0 place-items-center text-primary">{open ? <Minus className="size-4" /> : <Plus className="size-4" />}</span>
        </button>
      </h3>
      {open && (
        // Answers are sanitised (HTML Purifier) on save in the API.
        <div id={id} className="prose-content px-5 pb-4 text-sm" dangerouslySetInnerHTML={{ __html: faq.answer }} />
      )}
    </div>
  )
}

/** Two-column FAQ accordion. faqs: [{ id, question, answer(html) }] */
export default function FAQAccordion({ faqs = [], className }) {
  const [openId, setOpenId] = useState(null)
  if (!faqs.length) return null

  const columns = [faqs.filter((_, i) => i % 2 === 0), faqs.filter((_, i) => i % 2 === 1)]

  return (
    <div className={cn('grid items-start gap-4 md:grid-cols-2', className)}>
      {columns.map((column, c) => (
        <div key={c} className="space-y-4">
          {column.map((faq) => (
            <FaqItem key={faq.id} faq={faq} open={openId === faq.id} onToggle={() => setOpenId(openId === faq.id ? null : faq.id)} />
          ))}
        </div>
      ))}
    </div>
  )
}
