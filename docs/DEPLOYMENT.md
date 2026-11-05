# Deployment guide (cPanel / shared hosting)

The production server needs **PHP + MySQL only**. Node.js is used only on your own computer to
build the website, and the build is included in the release zip.

---

## 1. Requirements

| Item | Requirement |
|------|-------------|
| PHP | 8.2 or newer (cPanel › *MultiPHP Manager*) |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` (with WebP), `zip`, `zlib`, `curl`, `tokenizer`, `xml`, `ctype`, `bcmath` (cPanel › *Select PHP Version* › Extensions) |
| PHP settings | `upload_max_filesize ≥ 10M`, `post_max_size ≥ 60M`, `memory_limit ≥ 256M`, `max_execution_time ≥ 60` |
| Database | MySQL 5.7+ or MariaDB 10.4+, `utf8mb4` |
| Access | cPanel **Terminal** (or SSH) to run `php artisan` commands, and **Cron Jobs** |
| SSL | AutoSSL / Let's Encrypt (free in cPanel) |

> In this guide, `USER` is your cPanel username and `example.com` is your domain.

---

## 2. Build the release (on your computer)

```powershell
# Windows, from the project folder
powershell -ExecutionPolicy Bypass -File scripts\build-release.ps1
```

This creates `release\riths-YYYY-MM-DD_HHMM.zip` (≈ 11 MB). It contains:

- the Laravel app with the compiled website in `public/spa`
- production PHP packages (`vendor`)
- nothing secret: no `.env`, no uploads, no logs, no backups

---

## 3. Create the database

cPanel › **MySQL® Databases**:

1. Create a database, e.g. `USER_riths`.
2. Create a user with a strong password.
3. Add the user to the database with **ALL PRIVILEGES**.

---

## 4. Upload the files: choose a layout

### Option A (recommended): point the domain at the `public` folder

Use this when you can choose the domain's **Document Root**, which is always possible for
addon domains and subdomains, and on most current cPanel versions also for the main domain.

1. cPanel › **File Manager** › home folder (`/home/USER`) › create `riths` › upload the zip ›
   **Extract** it there. You now have `/home/USER/riths/artisan`, `/home/USER/riths/public`, …
   Files zipped on Windows carry no Linux permissions, so fix them once in Terminal:
   `chmod -R u+rwX,go+rX,go-w ~/riths`
2. cPanel › **Domains** › your domain › **Manage** › set *Document Root* to `riths/public`.

### Option B: the main domain must use `public_html`

1. Upload and extract the zip into `/home/USER/riths` as above.
2. Move **the contents** of `/home/USER/riths/public` (including the hidden `.htaccess`) into
   `/home/USER/public_html`.
3. Edit `public_html/index.php` and change one line:
   ```php
   $appPath = __DIR__.'/../riths';
   ```
4. In step 6 below, create the storage link by hand instead of with `storage:link`:
   ```bash
   ln -s /home/USER/riths/storage/app/public /home/USER/public_html/storage
   ```

> **Never** put the whole application inside `public_html`. The `.env`, `vendor` and
> `storage` folders must stay outside the web root.

---

## 5. Configure `.env`

In `/home/USER/riths`, copy `.env.example` to `.env` (File Manager › *Copy*), then edit it:

```dotenv
APP_NAME="RITHS Global"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
FRONTEND_URL=https://example.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_riths
DB_USERNAME=USER_rithsuser
DB_PASSWORD="the-database-password"

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true      # HTTPS only
SESSION_DOMAIN=null
SANCTUM_STATEFUL_DOMAINS=example.com,www.example.com
CORS_ALLOWED_ORIGINS=https://example.com,https://www.example.com

QUEUE_CONNECTION=database
CACHE_STORE=file

# First Super Admin, used only by the first `migrate --seed`
SEED_ADMIN_NAME="Super Admin"
SEED_ADMIN_EMAIL=you@example.com
SEED_ADMIN_PASSWORD="a-long-unique-password"
```

Notes:

- SMTP for email is configured later in the admin panel (Settings › Email), so the `MAIL_*`
  lines can stay as they are.
- If the site lives in a sub-folder (e.g. `https://example.com/riths`), put that full URL in
  `APP_URL` / `FRONTEND_URL`. The app detects its base path automatically.
- After the first deployment, remove `SEED_ADMIN_PASSWORD` from `.env`.

---

## 6. Install (cPanel › Terminal)

```bash
cd ~/riths
php -v                                  # must be 8.2+; on some hosts use e.g. /opt/cpanel/ea-php82/root/usr/bin/php
php artisan key:generate --force
php artisan migrate --seed --force      # creates tables, roles, settings, pages, menus, email templates, first admin
php artisan storage:link                # Option A only (see 4B for Option B)
php artisan optimize                    # caches config, routes, views
chmod -R 775 storage bootstrap/cache
```

Open `https://example.com/admin`, sign in with the `SEED_ADMIN_*` account, and change the
password under **My Profile**.

> The first install includes the real company content (profile, services, partner universities,
> scholarships) **and demo content** so the site looks complete: courses with sample fees,
> success stories, testimonials, gallery and blog posts. Replace it with real content in the
> admin panel. When you are ready, remove all remaining demo records at once:
>
> ```bash
> php artisan app:demo-content          # what demo content is left
> php artisan app:demo-content remove   # delete it (real content is never touched)
> ```

---

## 7. Cron job (required)

Emails, backups and clean-up run from a single cron entry.
cPanel › **Cron Jobs** › *Once Per Minute* (`* * * * *`):

```bash
/usr/local/bin/php /home/USER/riths/artisan schedule:run >> /dev/null 2>&1
```

Run `which php` in Terminal if `/usr/local/bin/php` is not 8.2.

| Task | When |
|------|------|
| Send queued emails (`queue:work --stop-when-empty`) | every minute |
| Database backup | daily 02:30 (keeps 14) |
| Database + uploaded files backup | Fridays 03:00 (keeps 4) |
| Prune failed jobs, expired tokens, password resets | daily / every 15 min |

---

## 8. Email (SMTP)

Admin › **Settings › Email**: enter the SMTP host, port (587 TLS or 465 SSL), username, password
and *From* address. Use an email account created in cPanel › *Email Accounts* (pngiep.com uses `no_replay@pngiep.com`).
You can also put the SMTP details in `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST=mail.example.com`, `MAIL_PORT=465`,
`MAIL_SCHEME=smtps`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). Emails to students carry a *Reply-To* of
the contact email, and office notifications a *Reply-To* of the student, so replies never land in the no-reply box. Press **Send
test**. Messages are queued, so they arrive within about a minute once the cron job is running.

On the same page, set **Admin notification email**: new leads, appointments and applications
are sent to this address. Then use **Settings › Appointment** to set the booking timezone,
working days, minimum notice and cancellation notice.

---

## 9. HTTPS

1. cPanel › **SSL/TLS Status** › *Run AutoSSL*.
2. Redirect HTTP to HTTPS: add these lines at the top of the web root `.htaccess`, just after
   `RewriteEngine On`:
   ```apache
   RewriteCond %{HTTPS} !=on
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```
3. `SESSION_SECURE_COOKIE=true` in `.env`, then `php artisan config:cache`.

When the site runs over HTTPS, the app also sends HSTS and `upgrade-insecure-requests` headers.

---

## 10. After going live

- Admin › **Settings › SEO**: default title and description, Google Search Console
  verification code, analytics snippet.
- Submit `https://example.com/sitemap.xml` in Google Search Console. The sitemap updates
  automatically when content changes.
- Create staff accounts in **Users** with the right role. Link counsellor accounts to their
  counsellor profile (Business › Counsellors › *Login account*).

---

## 11. Updating to a new version

```bash
# on your computer
powershell -ExecutionPolicy Bypass -File scripts\build-release.ps1
```

On the server:

```bash
cd ~/riths
php artisan down --secret="pick-a-secret"     # visitors see a maintenance page; you can open https://example.com/pick-a-secret
php artisan app:backup                        # safety backup first
```

Upload the new zip and extract it **over** `/home/USER/riths`, letting it replace files. Your
`.env` and `storage/` uploads are not in the zip and are kept. For Option B, copy the new
`public/spa` folder and `public/index.php` into `public_html` again, then re-apply the
`$appPath` edit.

```bash
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

---

## 12. Backups and restore

- Location: `/home/USER/riths/storage/app/backups`. This is outside the web root and also
  protected by `.htaccess`.
  - `db_*.sql.gz`: full database
  - `files_*.zip`: uploaded media (`public/`) and application documents (`documents/`)
- Manual backup: `php artisan app:backup` (add `--files` to include uploads).
- **Download backups regularly** (File Manager › Download) or use cPanel's own backup feature.
  A backup that only exists on the same server does not protect against losing the server.

**Restore the database:** cPanel › phpMyAdmin › select the database › *Import* › choose the
`.sql.gz` file. The dump drops and recreates every table.
**Restore files:** extract `files_*.zip`. `public/*` goes to `storage/app/public/`, and
`documents/*` goes to `storage/app/private/documents/`.

---

## 13. Troubleshooting

| Symptom | Fix |
|---------|-----|
| "500 Server Error" | Read `storage/logs/laravel-YYYY-MM-DD.log`. Check folder permissions (`storage`, `bootstrap/cache`). |
| Changes to `.env` have no effect | `php artisan config:cache` |
| Login says "session expired" (419), or you are logged out at once | `APP_URL` must match the address in the browser (https / www). Add the domain to `SANCTUM_STATEFUL_DOMAINS`, then `php artisan config:cache`. |
| Uploaded images do not show | The storage link is missing: `php artisan storage:link` (Option B: the `ln -s` command in 4B). |
| Emails are not sent | Check that the cron job is running, test SMTP in Settings › Email, and look at the `failed_jobs` table in phpMyAdmin. |
| Site shows the old design after an update | `php artisan optimize:clear`, then hard-refresh the browser. |
| Uploads fail for large files | Raise `upload_max_filesize` / `post_max_size` in *MultiPHP INI Editor*. |

---

## 14. Security checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] HTTPS forced, `SESSION_SECURE_COOKIE=true`
- [ ] Only `public/` is web-accessible (Option A or B as above)
- [ ] `https://example.com/.env` returns **403/404** (the `.htaccess` blocks dotfiles)
- [ ] Seed admin password changed and removed from `.env`
- [ ] Every staff member has their own account with the smallest suitable role
- [ ] Cron job running; at least one backup downloaded off the server
