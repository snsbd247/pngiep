# Development: ডেভেলপমেন্ট হিস্টোরি

> সর্বশেষ আপডেট: 2026-11-19

## ১. উৎস ও সীমাবদ্ধতা

- Git-এ মাত্র **একটা কমিট** আছে: `68571d9 Initial commit: PNGIEP education portal (Laravel API + React SPA)` (লেখক: `snsbd247`)। পুরো কোডবেস একসাথে কমিট করা হয়েছে, তাই প্রতিটি ফিচার কবে যোগ হয়েছে তার git-ইতিহাস নেই।
- নিচের ফেজভিত্তিক ইতিহাস নেওয়া হয়েছে [docs/ROADMAP.md](docs/ROADMAP.md), মাইগ্রেশন ফাইল, `release/` ফোল্ডারের zip আর ফাইলের তারিখ থেকে। ফেজগুলোর সঠিক তারিখ জানা নেই।
- এখন থেকে প্রতিটি পরিবর্তন এই ফাইলের শেষে "লগ" অংশে তারিখসহ যোগ করা উচিত।

## ২. ফেজভিত্তিক ইতিহাস

### Phase 1: Foundation
- Laravel 12 API + React/Vite/Tailwind। **সব মডিউলের পুরো DB schema** একবারেই (`2026_01_01_00000{1..5}` মাইগ্রেশন)।
- `/api/v1` envelope, JSON এরর হ্যান্ডলিং, Sanctum SPA auth, পাসওয়ার্ড রিসেট, rate limiting।
- ৪টা রোল, পারমিশন অনুযায়ী Gate; Settings (৯টা গ্রুপ, এনক্রিপ্টেড secret); Media library (WebP, থাম্বনেইল)।
- Activity log, users, role editor, profile; পাবলিক লেআউট; ডিজাইন সিস্টেম; server-rendered SEO shell।

### Phase 2: CMS
- ১৪টা সেকশনের হোমপেজ, hero slider, statistics, team, timeline, partners, testimonials, FAQs।
- `config/sections.php` স্কিমা; ১২ ধরনের ব্লকসহ পেজ বিল্ডার; মেনু ম্যানেজার; TipTap এডিটর।
- RITHS Global স্ক্রিনশট অনুযায়ী top bar/navbar/footer রিডিজাইন; versioned public cache; newsletter।
- মাইগ্রেশন: `2026_01_02_000001_add_phase2_cms_columns`।

### Phase 3: Directory
- Destinations/universities/courses/services CRUD; সেকশন বিল্ডারে নতুন ব্লক।
- ফিল্টার + গণনা, ফি রেঞ্জ, সর্টিং; `/study-in-{slug}` URL, পুরোনো `/destinations/{slug}` থেকে 301।
- schema.org (BreadcrumbList, CollegeOrUniversity, Course, Service); সাব-ফোল্ডার থেকে চালানোর সাপোর্ট (XAMPP `/riths`)।
- মাইগ্রেশন: `2026_01_03_000001_add_phase3_directory_columns`।

### Phase 4: Content
- Success stories (ভিজিটরের সাবমিশন escape করে draft হিসেবে জমা হয়), photo/video gallery, blog ও notices।
- মাইগ্রেশন: `2026_01_04_000001_add_phase4_content_columns`।

### Phase 5: Business
- বুকিং উইজার্ড, availability/time-off, ডাবল বুকিং ঠেকানো, ছাত্রের manage link।
- Counsellor শুধু নিজের অ্যাপয়েন্টমেন্ট দেখে; Lead CRM; Contact inbox থেকে reply; প্রাইভেট ডকুমেন্টসহ Apply; email templates; newsletter CSV।
- টেস্ট: মোট ৭৪টা।
- মাইগ্রেশন: `2026_01_05_000001_add_phase5_business_columns`, পরে `2026_01_06_*` (terms, notification preferences)।

### Phase 6: Polish ও Deploy
- sitemap.xml, robots.txt, Organization/WebSite schema।
- CSP, COOP, HSTS, `.htaccess` হার্ডেনিং; XSS অডিট; `composer audit`/`npm audit` পরিষ্কার।
- কোয়েরি অডিট (N+1 নেই), availability ক্যালেন্ডারে কোয়েরি ২১ থেকে ৫-এ নামানো।
- `app:backup` কমান্ড; `scripts/build-release.ps1`; DEPLOYMENT/ADMIN_MANUAL/QA_CHECKLIST ডক।
- PNGIEP ব্র্যান্ডিং ও আসল কনটেন্ট (India / Dubai (UAE) / Australia, ৬টা অনুমোদিত বিশ্ববিদ্যালয়); ডেমো কনটেন্ট manifest।
- টেস্ট: মোট ৭৮টা।

### রিলিজ
| ফাইল | তারিখ |
|---|---|
| `release/riths-2026-10-08_0132.zip` | 2026-10-08 01:32 |
| `release/riths-2026-10-08_0216.zip` | 2026-10-08 02:16 |

(`release/` gitignore করা আছে, এই zip-গুলো শুধু লোকাল মেশিনে আছে।)

### Phase 6-এর পরে (তারিখ অজানা)
- ROADMAP-এ ৭৮টা টেস্টের কথা আছে, এখন আছে ৮৬টা। মানে Phase 6-এর পরে আরও কিছু কাজ হয়েছে (যেমন `AdminMenuFeaturesTest`, `DashboardTest`, `DemoContentTest`), কিন্তু ROADMAP আপডেট করা হয়নি।
- 2026-11-06: লোকাল রিপো GitHub `snsbd247/pngiep`-এর সাথে যুক্ত হয়েছে (`.git` ফোল্ডারের তারিখ)।

---

## ৩. কোড রিভিউ: 2026-11-19

**পরিধি:** পুরো রিপো (৪৪৯টা ফাইল)। Routes, middleware, auth, permissions/policy, CRUD base, media/document upload, appointment booking, SEO injection, settings, backup, CRM, frontend API/guards/build, deploy config।

### যাচাইয়ের ফলাফল
| চেক | ফলাফল |
|---|---|
| `php artisan test` (XAMPP PHP 8.2.12) | ✅ ৮৬টা পাস, ৬২২টা assertion, ২০.৬ সেকেন্ড |
| `npm run build` | ✅ সফল (স্ক্র্যাচ ফোল্ডারে বিল্ড করা হয়েছে, `backend/public/spa` বদলানো হয়নি) |
| `npm run lint` (oxlint) | ✅ ০টা error, প্রায় ৪০টা warning |

### যা ভালো আছে
- রিচ-টেক্সটের প্রতিটি ফিল্ড সার্ভারে `Purify` করা; ভিজিটরের স্টোরি পুরোপুরি escape করা; URL ফিল্ডে `SafeUrl` (`javascript:`/`data:` আটকায়)।
- SEO meta `e()` দিয়ে escape করা, JSON-LD-তে `JSON_HEX_TAG`।
- আপলোড: আসল MIME যাচাই, SVG নিষিদ্ধ, ছবি re-encode; ডকুমেন্টের নাম র‍্যান্ডম, প্রাইভেট ডিস্কে থাকে, ডাউনলোড অডিট লগ হয়।
- Super Admin-এর হায়ারার্কি (policy + `guardRoleAssignment`), নিজেকে লক-আউট করা যায় না, Super Admin রোল বদলানো যায় না।
- Counsellor-এর ডেটা সব জায়গায় স্কোপ করা (তালিকা, ড্যাশবোর্ড, সার্চ)।
- বুকিং: row lock, slot যাচাই, unique index; manage token ৬৪ অক্ষরের।
- CSV export-এ formula injection আটকানো; backup ডাউনলোডে regex + `basename` + শুধু Super Admin।

### সমস্যা (গুরুত্ব অনুযায়ী)

| # | গুরুত্ব | সমস্যা | লোকেশন |
|---|---|---|---|
| 1 | উচ্চ | `trustProxies(at: '*')`: `X-Forwarded-For` জাল করে login rate limit (৫/মিনিট প্রতি ইমেইল+IP, ২০/মিনিট প্রতি IP) আর public-form limit বাইপাস করা যায়। লগ ও lead-এ IP-ও ভুল আসে। | [backend/bootstrap/app.php:33](backend/bootstrap/app.php#L33) |
| 2 | উচ্চ | `analytics_head_code` কাঁচা HTML হিসেবে `/admin`-সহ সব পেজে বসে। Admin রোলের (`settings.manage` আছে) কেউ JS বসালে সেটা Super Admin-এর সেশনে চলবে এবং roles বদলাতে পারবে। ফলে "Admin roles ম্যানেজ করতে পারে না" সীমাটা অকার্যকর হয়ে যায়। | [backend/app/Services/SeoService.php:98](backend/app/Services/SeoService.php#L98) |
| 3 | মাঝারি | ছাত্রদের ডকুমেন্ট (পাসপোর্ট ইত্যাদি) কখনো মোছা হয় না: application soft-delete হয়, কিন্তু কোনো model `Prunable` নয়, তাই `model:prune` কিছুই করে না। | `Application` model, [backend/routes/console.php:21](backend/routes/console.php#L21) |
| 4 | মাঝারি | পাবলিক ফর্মে অন্য কারও ইমেইল দিলে তার খোলা lead-এর নাম/ফোন বদলে যায়, ফলে CRM ভুল নম্বরে কল করতে পারে। | [backend/app/Services/LeadService.php:36](backend/app/Services/LeadService.php#L36) |
| 5 | মাঝারি | পাসওয়ার্ড বদলালে (profile বা admin user edit) অন্য ডিভাইসের সেশন লগআউট হয় না। | [backend/app/Http/Controllers/Api/V1/Admin/ProfileController.php:33](backend/app/Http/Controllers/Api/V1/Admin/ProfileController.php#L33), `UserController::update` |
| 6 | নিম্ন | Backup dump-এ `LIMIT/OFFSET` আছে কিন্তু `ORDER BY` নেই, তাই row বাদ পড়তে বা ডুপ্লিকেট হতে পারে। | [backend/app/Console/Commands/BackupCommand.php:77](backend/app/Console/Commands/BackupCommand.php#L77) |
| 7 | নিম্ন (পারফ) | `AppIcon`-এর icon map চাঙ্ক ৩৫৮ KB / gzip ৬২ KB, পাবলিক পেজে লোড হয়; `forms` চাঙ্ক (১৩৬ KB) সব পেজে preload হয়। | `frontend/src/components/ui/AppIcon.jsx`, `vite.config.js` |
| 8 | নিম্ন | README-তে `scriptsuild-release.ps1` / `releaseiths-<date>.zip`: `\b` আর `\r` escape হয়ে backslash হারিয়েছে। | [README.md](README.md) |
| 9 | নিম্ন | ROADMAP-এ টেস্টের সংখ্যা (৭৮) পুরোনো, আসলে ৮৬টা। | [docs/ROADMAP.md](docs/ROADMAP.md) |

সমাধানের পরিকল্পনা: [Plan.md › পরবর্তী পরিকল্পনা](Plan.md)

---

## ৪. পরিবর্তনের লগ

| তারিখ | কী হয়েছে |
|---|---|
| 2026-11-19 | পুরো প্রজেক্ট রিভিউ; `Plan.md`, `UI.md`, `Database.md`, `development.md`, `chathistory.md` তৈরি। কোডে কোনো পরিবর্তন করা হয়নি। |
| 2026-11-19 | `8821bdd` **News মডিউল**: `/news`, admin CRUD, scheduling, SEO, sitemap, সার্চ। NewsTest (৪টা)। |
| 2026-11-19 | `aa8a9c9` **YouTube embed**: এডিটরে বাটন, `config/purify.php` (শুধু YouTube iframe), স্টোরি কার্ডে ব্যাজ, ভিজিটরের ফর্মে লিংক। YouTubeEmbedTest (২টা)। |
| 2026-11-19 | `164c044` **Staff mailbox + Webmail** (cPanel UAPI)। WebmailTest (৭টা)। |
| 2026-11-19 | `0e4a459` **Facebook auto-post** (News + Blog)। FacebookAutoPostTest (৭টা)। |
| 2026-11-19 | প্রথম প্রোডাকশন ডেপ্লয় (৩টা migration), cPanel API টোকেন `pngiep-app` তৈরি করে `.env`-এ বসানো। লোকালে C:\xampp সরিয়ে শুধু F:\Lovable\xampp (Windows সার্ভিস), `loon_interior` শিফট। |
| 2026-11-19 | `99aa3b4` **ডিরেক্টর প্রোফাইল** `/team/{slug}`; `HasSeo` soft-delete ছাড়া মডেলেও কাজ করে। TeamProfileTest (৩টা)। ডেপ্লয় হয়েছে। |
| 2026-11-19 | **রিভিউয়ের সমাধান** (#1–#6, #8, #9) + লগইন ছাড়া admin API-তে JSON 401। HardeningTest (৬টা)। মোট ১১৫টা টেস্ট পাস। |
| 2026-11-19 | `df916ff` **সিকিউরিটি অ্যানালাইসিস**: Webmail-এর ফাঁক ঠিক (বিদ্যমান মেইলবক্স যুক্ত/মোছা শুধু Super Admin), npm audit fix, সার্ভারে `.env` 600। রিপোর্ট `docs/SECURITY_REPORT.md` (লোকাল)। মোট ১১৬টা টেস্ট। |

### রিভিউয়ের সমস্যাগুলোর বর্তমান অবস্থা (৩ নম্বর অংশের টেবিল অনুযায়ী)

| # | অবস্থা | কীভাবে |
|---|---|---|
| 1 | ✅ | `TRUSTED_PROXIES` env; ডিফল্টে কোনো proxy trust করা হয় না (সার্ভারে PHP সরাসরি `HTTPS=on` দেখে, যাচাই করা) |
| 2 | ✅ | `/admin`-এ analytics বসে না; `SettingController` শুধু Super Admin-কে বদলাতে দেয়, অন্যদের ফর্মে read-only |
| 3 | ✅ | `Application` `Prunable`: মোছার ৯০ দিন পর ফাইলসহ force-delete |
| 4 | ✅ | `LeadService::capture` শুধু খালি ঘর পূরণ করে; ভিন্ন নাম/ফোন `meta.history[].submitted`-এ |
| 5 | ✅ | `App\Support\UserSessions::endOthers` (Profile ও Users → edit) |
| 6 | ✅ | primary key অনুযায়ী `ORDER BY` (লোকাল MariaDB-তে ৬০/৬০ টেবিল dump যাচাই) |
| 7 | ⏳ | পারফরম্যান্স: এখনো বাকি |
| 8 | ✅ | README-র release পাথ ঠিক করা |
| 9 | ✅ | ROADMAP-এ টেস্ট সংখ্যা ও "After launch" অংশ |
