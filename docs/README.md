# Education Consultancy CMS: PNG International Education Portal

Website + admin CMS for **PNG International Education Portal** (Port Moresby, Papua New Guinea · pngiep.com).

Study-abroad consultancy website + admin CMS: university and course directory, blog and news,
success stories (with YouTube videos), galleries, director profiles, appointment booking, lead CRM
and application management, staff mailboxes with one-click Webmail (cPanel) and Facebook Page
auto-posting.
Every piece of business content is managed from the admin panel. Nothing company-specific
is hard-coded in the frontend.

| Layer    | Stack |
|----------|-------|
| Backend  | Laravel 12 · PHP 8.2+ · Sanctum (SPA cookie auth) · MySQL / MariaDB |
| Frontend | React 19 · Vite · React Router · Tailwind CSS 4 · TanStack Query · React Hook Form + Zod |
| Hosting  | Shared cPanel (Apache). No Node.js in production: React is built to static files that Laravel serves. |

## Repository layout

```
backend/    Laravel API, admin API, SPA shell with server-rendered SEO tags
frontend/   React app (public website + admin panel, code-split)
docs/       All documentation (this README, plan, architecture, deployment, admin manual, SEO, …)
scripts/    build-release.ps1 (cPanel release zip)
```

## Local development

Requirements: PHP 8.2+ (gd, pdo_mysql, fileinfo, mbstring), Composer, Node 20+, MySQL 8 / MariaDB 10.4+.

```bash
# Backend
cd backend
cp .env.example .env            # set DB_*, SEED_ADMIN_* (and DB_PORT if not 3306)
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve               # http://127.0.0.1:8000

# Frontend (second terminal)
cd frontend
cp .env.example .env
npm install
npm run dev                     # http://localhost:5173  (proxies /api, /sanctum, /storage to :8000)
```

Admin panel: `http://localhost:5173/admin`. The seeder creates the Super Admin from
`SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD`. If no password is set it generates one and prints it once.

### Production-style run (no Vite)

```bash
cd frontend && npm run build    # writes to backend/public/spa
# then open http://127.0.0.1:8000. Laravel serves the SPA with SEO meta injected.
```

### XAMPP (Apache) at `http://localhost/riths`

The app runs from any sub-folder; Laravel tells the SPA its base path at runtime.

1. `F:\Lovable\xampp\apache\conf\extra\httpd-riths.conf` aliases `/riths` to `backend/public`
   (included at the end of `httpd.conf`). Restart Apache after changing it.
2. `.env`: `APP_URL=http://localhost/riths`, and add every host you browse from (e.g. a LAN/public IP)
   to `SANCTUM_STATEFUL_DOMAINS`.
3. `cd frontend && npm run build`
4. **`cd backend && php artisan config:cache`**, and run it again after every `.env` change.
   Several Laravel apps share one multi-threaded Apache process on Windows, so `.env` values leak
   between them unless each app's config is cached. Do the same for the other Laravel apps on this XAMPP.

## Tests

```bash
cd backend && php artisan test   # feature tests, in-memory SQLite
cd frontend && npm run lint && npm run build
```

## Release & deployment

```powershell
powershell -ExecutionPolicy Bypass -File scripts\build-release.ps1   # → release\riths-<date>.zip
```

First install: upload the zip to cPanel and follow [DEPLOYMENT.md](DEPLOYMENT.md).
Updates: copy the changed files, then `php artisan migrate --force` and rebuild the caches
(section 11 of DEPLOYMENT.md). Take a backup first (`php artisan app:backup`).
Documentation never goes to the server: everything is in `docs/` (outside `backend/`), and the release zip
and update packages also leave out any `*.md` file.

Live site: https://pngiep.com (cPanel, `~/riths` + `~/public_html`). Code: https://github.com/snsbd247/pngiep (`main`).

## Docs

- [ROADMAP.md](ROADMAP.md): six-phase delivery plan and status
- [ARCHITECTURE.md](ARCHITECTURE.md): design decisions, database, API, security
- [DEPLOYMENT.md](DEPLOYMENT.md): cPanel deployment, cron, SMTP, HTTPS, updates, backups, troubleshooting
- [ADMIN_MANUAL.md](ADMIN_MANUAL.md): guide for office staff
- [QA_CHECKLIST.md](QA_CHECKLIST.md): pre-launch and regression checklist
- [SEO.md](SEO.md): what the site does for SEO and what the company should do (Bangla)
- [Plan.md](Plan.md), [UI.md](UI.md), [Database.md](Database.md), [development.md](development.md),
  [chathistory.md](chathistory.md): project plan, design, database, development history and chat log (Bangla)
