# Delivery roadmap

Six phases, delivered strictly in order. Each phase ends with code complete, tests passing,
a successful production build and its acceptance checklist signed off.

## Phase 1: Foundation, database, admin auth ✅

- [x] Laravel 12 API + React/Vite/Tailwind frontend, MySQL
- [x] Full relational schema for **all** modules (no rewrites needed later)
- [x] Eloquent models with relationships, slugs, SEO, publishing scopes, audit logging
- [x] `/api/v1` with a consistent `{success, message, data, meta}` envelope and JSON errors
- [x] Sanctum SPA (HttpOnly cookie) auth, login, logout, password reset, rate limiting
- [x] Roles & permissions (Super Admin, Admin, Content Manager, Counsellor), Gate per permission
- [x] Settings module (9 groups, typed and validated, secrets encrypted, cached)
- [x] Media library (upload, WebP conversion and resize, thumbnails, MIME validation)
- [x] Activity log, user management, role permission editor, profile
- [x] Public layout: top bar, sticky navbar with dropdowns and mobile drawer, footer, all from settings and menus
- [x] Design system (brand colours driven by settings), reusable UI kit, form kit, loading/empty/error states
- [x] SEO foundation: Laravel serves the SPA shell with server-rendered title/meta/OG/canonical; robots.txt
- [x] cPanel-friendly scheduler (queue drained by cron), private documents disk

## Phase 2: CMS, homepage, about, global content ✅

- [x] Homepage built from 14 CMS sections (enable/disable, drag reorder, per-section content) per reference design
- [x] Hero slider, statistics (grouped), services, partners, testimonials, team, company timeline, FAQs
- [x] Section schemas in `config/sections.php`: one definition drives API validation, HTML purification and admin forms
- [x] CMS pages with a block builder (12 block types); About page and legal pages; server-side SEO for every page
- [x] Menu manager: drag reorder, dropdown nesting, internal / page / external links
- [x] Rich text editor (TipTap), icon picker, media picker, repeaters, SEO fields
- [x] Redesigned top bar, navbar (search), footer, CTA, inner-page hero to match the RITHS Global screenshots
- [x] Versioned public cache invalidated on every content change; newsletter signup
- [x] Sample destinations, universities, stories, gallery, blog (local only) so the homepage previews fully

## Phase 3: Destinations, universities, courses, services ✅

- [x] Admin CRUD for destinations, universities, courses (+ service detail sections); block deletes that would orphan records
- [x] Section builder for destination / university / service pages; new blocks: text + photos, list, table, gallery,
      related universities, related courses (level tabs), ranking stats, highlights bar; half-width pairing
- [x] Public lists with backend search, multi-select filters with counts, fee range, sorting, pagination (URL-shareable)
- [x] Detail pages per the reference designs with sidebar enquiry form (creates a Lead), quick info, map, related content
- [x] Clean URLs `/study-in-{slug}`, `/universities/{slug}`, `/courses/{slug}`, `/services/{slug}`; 301 from `/destinations/{slug}`
- [x] Server-side SEO + schema.org (BreadcrumbList, CollegeOrUniversity, Course, Service); 404 for unknown records
- [x] Global search page; editable list-page heroes/sections (template "listing")
- [x] Runs from a sub-folder (XAMPP `http://localhost/riths`, any host/IP)

## Phase 4: Success stories, gallery, blog/notices ✅

- [x] Success stories: admin CRUD, list (country tabs, search, stats, video testimonials), detail per reference design
- [x] "Share your success story" form → escaped draft for admin review (Visitor submissions filter)
- [x] Photo gallery: categories with icons/counts, albums with bulk photos (ordered), lightbox
- [x] Video gallery: YouTube URL only (watch / youtu.be / shorts parsed server-side), privacy-enhanced embed, custom thumbnail
- [x] Blog & notices: categories, tags, destination filter, scheduling, sanitised rich text, popular posts, view counts
- [x] Blog detail with share buttons, related posts; Article / NewsArticle schema and OG tags
- [x] Sidebar promo cards + newsletter card editable as CMS blocks; blog, stories in site search

## Phase 5: Appointments, leads, applications, contact ✅

- [x] Booking wizard (service → counsellor → date → time → details → confirmation), homepage prefill, .ics download
- [x] Weekly availability windows + time-off per counsellor; slot generation in the business timezone (Settings > Appointment)
- [x] Double-booking prevention: row lock + `appointments_slot_unique` DB index; min notice, booking window, working days
- [x] Student manage link `/appointment/{token}` (noindex): view, reschedule, cancel (cancellation notice enforced)
- [x] Admin appointment dashboard (today/upcoming/past, status tabs, filters), detail with confirm/complete/no-show/reschedule/cancel + notes
- [x] Counsellor role sees and manages only their own appointments (API-scoped)
- [x] Lead CRM: every form creates/merges a lead (open lead per email), status, assignment, notes, linked records
- [x] Contact page (info cards, inquiry types from Settings, office card, offices + map block, FAQ) → inbox with email reply
- [x] Apply Now with private document upload (type/size/MIME validated, random names, private disk) and audited admin download
- [x] Editable email templates with {{placeholders}}, queued via database queue (cron-driven, shared-hosting safe)
- [x] Newsletter subscribers list + CSV export (formula-injection safe)
- [x] Tests: AppointmentTest, CrmTest, ApplicationTest (74 tests total)

## Phase 6: Polish, security, SEO, performance, deployment ✅

- [x] `sitemap.xml` (published + indexable content, cached, refreshed on any change); base-path aware `robots.txt`
- [x] Organization + WebSite (SearchAction) structured data on the homepage, alongside the existing per-page schema
- [x] Security: CSP (`object-src`, `base-uri`, `form-action`, `frame-ancestors`), COOP, HSTS + `upgrade-insecure-requests` on HTTPS;
      `.htaccess` blocks dotfiles / dumps; no PHP execution in uploads; XSS audit (all rich text purified); `composer audit` / `npm audit` clean
- [x] Performance: query audit of every public endpoint (no N+1; 0–4 queries when cached); availability calendar 21 → 5 queries;
      immutable caching of hashed SPA assets, gzip, image resize + WebP (Phase 1)
- [x] "Today" in the dashboard and counsellor lists uses the booking timezone
- [x] Backups: `php artisan app:backup [--files]` (pure-PHP SQL dump, rotation), scheduled nightly / weekly; restore verified
- [x] Release script `scripts/build-release.ps1` (SPA build + production vendor zip); `public/index.php` supports the `public_html` layout;
      production deploy simulated end to end (migrate, seed, optimize, login, scheduler)
- [x] Docs: DEPLOYMENT.md (cPanel), ADMIN_MANUAL.md, QA_CHECKLIST.md
- [x] Branding for PNG International Education Portal: logo + favicon, contact details, Port Moresby timezone, Mon–Fri
- [x] Real content from the PNGIEP Executive Summary: About, directors (team + counsellors), statistics, timeline, 5 services,
      India / Dubai (UAE) / Australia with the 6 authorised universities, scholarship requirements page, FAQs.
      Demo courses (sample fees), stories, testimonials, gallery and blog in every environment, tracked in a manifest and
      removable with `php artisan app:demo-content remove`; social share image generated from the logo
- [x] Full responsive QA crawl of every sitemap URL (320 – 1440 px); SeoTest added (78 tests total)
