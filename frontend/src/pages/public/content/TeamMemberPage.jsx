import { useQuery } from '@tanstack/react-query'
import { CheckCircle2, Mail, MessageCircle, Phone, Quote } from 'lucide-react'
import { useParams } from 'react-router-dom'
import { TeamCard } from '@/components/cards'
import { ShareButtons } from '@/components/content/ContentWidgets'
import Breadcrumb from '@/components/layout/Breadcrumb'
import SectionHeader from '@/components/sections/SectionHeader'
import Seo from '@/components/Seo'
import Image from '@/components/ui/Image'
import SocialIcon from '@/components/ui/SocialIcon'
import { whatsappHref } from '@/lib/utils'
import { contentService } from '@/services/publicService'
import { DetailQuery, ListingCta } from '../directory/shared'

const initials = (name) => name.split(' ').map((w) => w[0]).slice(0, 2).join('')

/** Contact and social links for a team member (only the ones that are filled in). */
function ContactLinks({ member }) {
  const links = member.social_links || {}
  const items = [
    member.email && { href: `mailto:${member.email}`, label: member.email, icon: <Mail className="size-4" /> },
    member.phone && { href: `tel:${member.phone.replace(/\s+/g, '')}`, label: member.phone, icon: <Phone className="size-4" /> },
    links.whatsapp && { href: whatsappHref(links.whatsapp), label: 'WhatsApp', icon: <MessageCircle className="size-4" />, external: true },
    links.linkedin && { href: links.linkedin, label: 'LinkedIn', icon: <SocialIcon name="linkedin" className="size-4" />, external: true },
    links.facebook && { href: links.facebook, label: 'Facebook', icon: <SocialIcon name="facebook" className="size-4" />, external: true },
  ].filter(Boolean)
  if (!items.length) return null
  return (
    <ul className="mt-6 flex flex-wrap gap-2">
      {items.map((i) => (
        <li key={i.href}>
          <a
            href={i.href}
            {...(i.external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
            className="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-ink shadow-card transition hover:text-primary"
          >
            <span className="text-primary">{i.icon}</span> {i.label}
          </a>
        </li>
      ))}
    </ul>
  )
}

/** /team/:slug — a director's profile: details, message (speech) and the rest of the team. */
export default function TeamMemberPage() {
  const { slug } = useParams()
  const query = useQuery({ queryKey: ['team-member', slug], queryFn: () => contentService.teamMember(slug) })

  return (
    <DetailQuery query={query}>
      {(m) => (
        <>
          <Seo title={m.designation ? `${m.name} – ${m.designation}` : m.name} description={m.bio} image={m.photo} seo={m.seo} />

          <section className="bg-lavender">
            <div className="container-page py-10 md:py-14">
              <Breadcrumb className="mb-6" items={[{ label: 'About Us', to: '/about' }, { label: m.name }]} />
              <div className="grid items-center gap-8 md:grid-cols-[280px_1fr] lg:gap-12">
                {m.photo ? (
                  <Image src={m.photo} alt={m.name} eager className="aspect-[4/5] w-full max-w-[280px] rounded-2xl object-top shadow-card" />
                ) : (
                  <span className="grid aspect-[4/5] w-full max-w-[280px] place-items-center rounded-2xl bg-primary-50 text-6xl font-bold text-primary" aria-hidden="true">
                    {initials(m.name)}
                  </span>
                )}
                <div className="min-w-0">
                  {m.designation && <p className="text-sm font-semibold tracking-wide text-primary uppercase">{m.designation}</p>}
                  <h1 className="mt-2 text-3xl leading-tight sm:text-4xl lg:text-5xl">{m.name}</h1>
                  {m.bio && <p className="mt-4 max-w-2xl text-[15px] leading-relaxed text-slate-600">{m.bio}</p>}
                  {m.highlights?.length > 0 && (
                    <ul className="mt-5 grid gap-2 sm:grid-cols-2">
                      {m.highlights.map((h) => (
                        <li key={h} className="flex items-start gap-2 text-sm text-slate-700">
                          <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-primary" /> {h}
                        </li>
                      ))}
                    </ul>
                  )}
                  <ContactLinks member={m} />
                </div>
              </div>
            </div>
          </section>

          <div className="container-page max-w-4xl space-y-12 py-12">
            {m.message && (
              <section aria-labelledby="director-message">
                <div className="relative rounded-2xl border border-primary-100 bg-white p-6 shadow-card sm:p-10">
                  <Quote className="absolute top-6 right-6 size-10 text-primary-100 sm:size-14" aria-hidden="true" />
                  <h2 id="director-message" className="text-2xl sm:text-3xl">{m.message_title || 'Message from the Director'}</h2>
                  <div className="prose-content mt-5 text-[15px]" dangerouslySetInnerHTML={{ __html: m.message }} />
                  <p className="mt-6 border-t border-slate-100 pt-4">
                    <span className="block font-semibold text-ink">{m.name}</span>
                    {m.designation && <span className="text-sm text-slate-500">{m.designation}</span>}
                  </p>
                </div>
              </section>
            )}

            {m.profile && (
              <section aria-labelledby="director-profile">
                <h2 id="director-profile" className="text-2xl sm:text-3xl">About {m.name.split(' ')[0]}</h2>
                <div className="prose-content mt-5 text-[15px]" dangerouslySetInnerHTML={{ __html: m.profile }} />
              </section>
            )}

            <div className="flex justify-end">
              <ShareButtons title={m.name} />
            </div>
          </div>

          {m.others?.length > 0 && (
            <section className="pb-12">
              <div className="container-page">
                <SectionHeader title="Meet the *Team*" />
                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                  {m.others.map((o) => <TeamCard key={o.id} member={o} />)}
                </div>
              </div>
            </section>
          )}
          <ListingCta slug="about" />
        </>
      )}
    </DetailQuery>
  )
}
