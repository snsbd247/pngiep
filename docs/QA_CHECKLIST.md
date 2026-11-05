# Final QA checklist

Run through this list before launch and after every major update. Items marked **(auto)** are
covered by the automated tests (`php artisan test`) or the browser test scripts. Check them
manually on the live server.

## 1. Before launch: content the company must supply

Already filled in from the PNGIEP Executive Summary: company profile, directors, statistics
(300 students admitted, 6 partner universities, 3 destinations, 2025 target), timeline,
the 6 authorised universities, scholarship requirements and services.

- [x] Logo and favicon are installed automatically from `backend/resources/branding` (replace any time in Settings › General)
- [ ] Office hours: Settings › Contact (demo value: Mon – Fri, 8:00 AM – 4:30 PM)
- [ ] Confirm the public email (website uses `pngiepstudiesabroad2526@gmail.com`; the summary lists `pngiepadmissions@studyabroad.com`)
- [ ] Directors' photos and consultation hours: Content › Team Members, Business › Counsellors (default Mon–Fri 9:00–12:00, 13:00–16:00)
- [ ] University details: city, logo, website, fees, courses, scholarships: Directory › Universities / Courses
- [ ] Partner logos: Content › Partners
- [ ] Replace the demo courses / fees, success stories, testimonials, gallery and blog posts, then run `php artisan app:demo-content remove`
- [x] Social share image (1200×630) generated from the logo: Settings › SEO
- [ ] Privacy policy and terms reviewed by the company: Website › Pages
- [ ] Social media profile links: Settings › Social (placeholder links are not used in search data)

## 2. Public website

| Check | How |
|-------|-----|
| Every page loads without errors on desktop, tablet and phone (320px+) | (auto) `qa.mjs` crawl of all sitemap URLs |
| No horizontal scrolling on phones | (auto) |
| Header menu, mobile drawer, footer links work | Click through |
| Homepage sections can be reordered and hidden in the admin | Homepage › drag / toggle, then reload the site |
| Search finds destinations, universities, courses, blog posts | `/search?q=…` |
| Universities and courses filters and pagination; URL keeps filters | (auto) Directory tests |
| `/study-in-{country}` pages; `/destinations/{slug}` redirects (301) | (auto) |
| Blog: categories, tags, scheduled posts hidden until their date | (auto) Content tests |
| Gallery lightbox, YouTube videos play | Manual |
| Unknown URL shows the 404 page with HTTP 404 | (auto) |

## 3. Forms and business flows

| Check | How |
|-------|-----|
| Booking: service → counsellor → date → time → details → confirmation | (auto) `p5.mjs` |
| Only free slots are offered; a taken slot is rejected (double booking) | (auto) AppointmentTest |
| Student can reschedule or cancel from the email link; blocked inside the notice period | (auto) |
| Contact form → message in inbox + lead + auto-reply email | (auto) CrmTest |
| Apply Now with documents → application + private documents | (auto) ApplicationTest |
| Wrong file types or files > 5 MB are rejected | (auto) |
| Enquiry forms on destination / university / course pages create leads | (auto) |
| Spam protection: honeypot, and rate limit of 10 submissions/minute and 60/hour per IP | (auto) |
| Real emails arrive (student and office) with the correct links | Manual after SMTP + cron setup |

## 4. Admin

| Check | How |
|-------|-----|
| Login, logout, forgot / reset password | Manual |
| Each role sees only its menus; direct URLs are blocked (403) | (auto) PermissionTest, CrmTest |
| Counsellor sees only their own appointments | (auto) |
| Create / edit / delete content in every module; images upload and appear | `admin2.mjs`, `p5admin.mjs` |
| Document download works for admins, and is logged in the Activity Log | (auto) |
| Settings changes appear on the website (name, colours, contact details) | Manual |
| Email templates edit and the test email | Manual |

## 5. SEO

- [ ] (auto) Every page has a server-rendered `<title>`, description, canonical and Open Graph tags (view source, no JavaScript)
- [ ] (auto) `sitemap.xml` lists published pages only; drafts and "noindex" pages are excluded
- [ ] (auto) `robots.txt` blocks `/admin`, `/api/`, `/appointment/` and points to the sitemap
- [ ] (auto) Structured data: Organization + WebSite (home), BreadcrumbList, CollegeOrUniversity, Course, Service, Article
- [ ] Validate a few pages with Google's Rich Results Test
- [ ] Submit the sitemap in Google Search Console

## 6. Security

- [ ] `APP_DEBUG=false`; a server error shows a generic message only
- [ ] `https://domain/.env`, `/.git/` and `/storage/…/*.php` return 403
- [ ] (auto) Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP (`object-src`, `base-uri`, `form-action`, `frame-ancestors`), HSTS on HTTPS
- [ ] (auto) Login throttling (5/min per email + IP); public form throttling
- [ ] (auto) Rich text is sanitised (HTML Purifier); visitor stories are stored as plain text
- [ ] (auto) Application documents: private disk, random file names, no public URL, permission-checked download
- [ ] `composer audit` and `npm audit` report no vulnerabilities
- [ ] Seed admin password changed; staff use individual accounts

## 7. Performance

- [ ] (auto) Public API answers from cache (0–4 database queries when warm); no N+1 queries (lazy loading is blocked outside production)
- [ ] Uploaded images are resized and stored as WebP
- [ ] SPA assets are cached for a year (`Cache-Control: immutable`) and gzip is on (if the host has `mod_deflate`)
- [ ] Homepage loads in under 3 s on a 4G phone (Chrome DevTools › Lighthouse)

## 8. Operations

- [ ] Cron job runs every minute (Admin emails arrive within about a minute)
- [ ] `storage/app/backups` receives a nightly `db_*.sql.gz`
- [ ] A backup was restored once into a test database (phpMyAdmin › Import)
- [ ] Someone is responsible for downloading backups off the server
