# Architecture

## Key decisions

| Decision | Why |
|---|---|
| **One React app** for website + admin, routes lazy-loaded | One build and deploy; visitors never download admin code. |
| **Laravel serves `index.html`** (`SpaController`) with meta injected | SEO-safe SPA on shared hosting without Node/SSR. `SeoService::register()` lets each module add URL resolvers. |
| **Sanctum SPA cookies**, not tokens in localStorage | HttpOnly session cookie + CSRF; nothing sensitive reachable from JS. |
| **Permissions in `config/permissions.php`** → Gates | Routes use `can:<permission>`; role assignments are editable in the admin. Super Admin bypasses permission checks but **not** policies (e.g. cannot delete self). |
| **Statuses as strings + PHP enums** (`app/Enums`) | Easy to extend without ALTERing MariaDB enums; `/admin/meta` exposes options to forms. |
| **`*_sections` tables** (page, destination, university, service) | Detail pages are composed of ordered, toggleable sections with `data` JSON. No schema change for new section types. |
| **Polymorphic `seo_meta`, `notes`, `faqs`** | One implementation reused by every module. |
| **Appointment `slot_lock` + unique index** | `(counsellor_id, date, start_time, slot_lock)` unique, and `slot_lock` is NULL once cancelled. Double booking is impossible at DB level; the app adds row locking. |
| **Typed settings table** | The admin form and server validation are generated from each setting's `type`. Secrets are encrypted and never returned. |
| **Database queue + cron worker** | Shared hosting has no supervisor. `schedule:run` drains the queue each minute. |
| **Images → WebP, max 2000px, thumbnail** | Fast pages without manual optimisation. SVG uploads rejected (XSS). |

## Backend structure

```
app/
  Enums/                  ContentStatus, AppointmentStatus, LeadStatus, LeadSource, ApplicationStatus, …
  Http/Controllers/
    Api/V1/               public endpoints (SiteController …)
    Api/V1/Admin/         admin endpoints (Auth, Dashboard, Settings, Media, Users, Roles, ActivityLog, Meta)
    SpaController.php     SPA shell + robots.txt + favicon
  Http/Middleware/        EnsureUserIsActive, SecurityHeaders
  Http/Requests/Admin/    Form Requests
  Http/Resources/         API resources
  Models/ + Models/Concerns/  HasSlug, HasSeo, HasPublishing, HasActiveFlag, HasSections, HasNotes, LogsActivity
  Policies/               UserPolicy
  Services/               SettingService, MenuService, MediaService, SeoService, ActivityLogger
  Support/                ApiResponse, Permissions
config/permissions.php    permission catalogue + default roles
```

## Frontend structure

```
src/
  lib/          api.js (Axios + CSRF + ApiError), queryClient, utils, validation
  services/     siteService, authService, admin/* (createCrudService factory)
  context/      SiteContext (settings + menus + brand colours), AuthContext (admin only)
  components/
    ui/         Button, Badge, Modal, ConfirmDialog, Toast, Pagination, SearchBar, Image, AppIcon, States (Skeleton/Empty/Error)
    forms/      FormField, FormInput, FormTextarea, FormSelect, FormSwitch, FileUploader, ImageField
    layout/     TopBar, Navbar, Footer, Breadcrumb, SiteLogo, SocialLinks, PublicLayout
    sections/   HeroBanner, SectionHeader, CTASection
    admin/      AdminLayout, DataTable, Card, StatCard, PageHeader, MediaPickerModal
  config/adminNav.js   sidebar (permission-filtered)
  router/              routes + guards
  pages/public, pages/admin
```

## Database (all tables created in Phase 1)

- **Auth/system:** users, roles, permissions, role_permissions, sessions, password_reset_tokens, personal_access_tokens, settings, media, seo_meta, activity_logs, notes, email_templates, notifications, jobs, cache
- **CMS:** menus, menu_items, pages, page_sections, hero_slides, homepage_sections, homepage_statistics, team_members, timeline_items, partners, testimonials, faqs, newsletter_subscribers
- **Directory:** destinations, destination_sections, universities, university_sections, courses, services, service_sections
- **Content:** success_stories, gallery_categories, gallery_albums, gallery_images, gallery_videos, blog_categories, blogs, blog_tags, blog_post_tags
- **Business:** counsellors, counsellor_service, counsellor_availabilities, counsellor_time_offs, appointments, leads, contact_messages, applications, application_documents

## API (Phase 1)

Response envelope: `{ "success": bool, "message": string|null, "data": any, "meta": object|null }`,
plus `errors` on 422. Lists put pagination in `meta`.

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/site` | public: settings + menus |
| GET | `/api/v1/settings`, `/api/v1/menus` | public |
| GET | `/api/v1/admin/auth/session` | returns user or null |
| POST | `/api/v1/admin/auth/login` · `forgot-password` · `reset-password` | throttled |
| POST | `/api/v1/admin/auth/logout` · GET `auth/me` · PUT `profile` · GET `meta` | signed in |
| GET | `/api/v1/admin/dashboard` | `dashboard.view` |
| GET/PUT | `/api/v1/admin/settings` · POST `settings/test-mail` | `settings.manage` |
| GET/POST/PUT/DELETE | `/api/v1/admin/media` · DELETE `media/bulk` | `media.manage` |
| CRUD | `/api/v1/admin/users` · GET `roles/options` | `users.manage` |
| GET/PUT | `/api/v1/admin/roles` | `roles.manage` |
| GET | `/api/v1/admin/activity-logs` | `activity_logs.view` |

## API (Phase 2)

| Method | Path | Access |
|---|---|---|
| GET | `/api/v1/homepage` | public: enabled sections + their records (cached) |
| GET | `/api/v1/pages/{slug}` · `/api/v1/faqs?page_key=` | public |
| POST | `/api/v1/newsletter` | public, throttled |
| CRUD + POST `…/reorder` | `/api/v1/admin/{hero-slides, statistics, team-members, timeline-items, homepage-sections}` | `homepage.manage` |
| CRUD + reorder | `/api/v1/admin/pages` · `services` · `partners` · `testimonials` · `faqs` | matching `*.manage` |
| GET / PUT, CRUD | `/api/v1/admin/menus`, `/api/v1/admin/menu-items` | `menus.manage` |
| GET | `/api/v1/admin/meta/sections` · `/api/v1/admin/lookups?types=` | signed in |

Generic admin CRUD: `Admin\CrudController` + `Route::crud()` on the API, `config/resources.js` +
`ResourceListPage` / `ResourceFormPage` + `SchemaField` in the admin. A new simple module = model,
FormRequest, a 10-line controller and a config entry.

## Security checklist (Phase 1)

CSRF (Sanctum XSRF cookie) · HttpOnly encrypted session · bcrypt passwords · login and form
rate limiters · Form Request validation everywhere · Gates + policies · inactive users
blocked mid-session · upload MIME sniffing + size limits, no SVG · private `documents` disk ·
encrypted SMTP password · security headers (nosniff, frame, referrer, HSTS on HTTPS) ·
CORS restricted to configured origins · no secrets in the frontend bundle.
