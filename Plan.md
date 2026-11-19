# Plan: PNG International Education Portal (PNGIEP)

> সর্বশেষ আপডেট: 2026-11-19 · রিপো: `snsbd247/pngiep` · ব্রাঞ্চ: `main`

## ১. প্রজেক্টের উদ্দেশ্য

PNG International Education Portal (Port Moresby, Papua New Guinea · pngiep.com) একটি
study-abroad consultancy প্রতিষ্ঠান। এর জন্য একটি পাবলিক ওয়েবসাইট আর একটি admin CMS তৈরি করা হয়েছে।

- ছাত্ররা দেশ, বিশ্ববিদ্যালয় আর কোর্স খুঁজবে, কাউন্সেলরের সাথে অ্যাপয়েন্টমেন্ট বুক করবে, ডকুমেন্টসহ আবেদন করবে।
- অফিস স্টাফ admin panel থেকে সব কনটেন্ট, লিড (CRM), অ্যাপয়েন্টমেন্ট, আবেদন আর মেসেজ সামলাবে।
- **কোম্পানির কোনো তথ্য frontend-এ hard-coded নয়।** নাম, লোগো, রং, মেনু, যোগাযোগের তথ্য সব admin Settings থেকে আসে।

## ২. টেক স্ট্যাক

| স্তর | প্রযুক্তি |
|---|---|
| Backend | Laravel 12 · PHP 8.2+ · Sanctum (SPA cookie auth) · MySQL 8 / MariaDB 10.4+ |
| Frontend | React 19 · Vite 8 · React Router 7 · Tailwind CSS 4 · TanStack Query 5 · React Hook Form + Zod 4 · TipTap 3 |
| হোস্টিং | Shared cPanel (Apache), Node.js ছাড়া। React স্ট্যাটিক ফাইলে বিল্ড হয়ে `backend/public/spa`-তে যায়, Laravel সেটা সার্ভ করে |
| Queue/Cron | Database queue; প্রতি মিনিটে `schedule:run` চালানো একটা cron queue খালি করে |

## ৩. আর্কিটেকচারের মূল সিদ্ধান্ত

1. **একটাই React অ্যাপ** (ওয়েবসাইট + admin)। Admin রাউটগুলো lazy-loaded, তাই ভিজিটররা admin কোড ডাউনলোড করে না।
2. **Laravel `index.html` সার্ভ করে** (`SpaController`) এবং server-side-এ `<title>`, meta, OG, canonical, JSON-LD বসিয়ে দেয়। ফলে Node/SSR ছাড়াই SEO হয়।
3. **Sanctum SPA cookie** (HttpOnly session + CSRF)। localStorage-এ কোনো টোকেন রাখা হয় না।
4. **পারমিশন `config/permissions.php`-এ** থাকে, প্রতিটি পারমিশন একটা Gate। Super Admin সব পারমিশন পায়, তবে policy (যেমন নিজেকে ডিলিট করা যাবে না) তার ক্ষেত্রেও খাটে।
5. **জেনেরিক CRUD**: `Admin\CrudController` + `Route::crud()` + frontend `config/resources.jsx`। নতুন সাধারণ মডিউল যোগ করতে লাগে একটা model, একটা FormRequest, ১০ লাইনের controller আর একটা config entry।
6. **সেকশন/ব্লক বিল্ডার**: `config/sections.php`-এর একটা স্কিমা থেকেই API validation, HTML purification আর admin ফর্ম তৈরি হয়।
7. **ডাবল বুকিং ঠেকানো**: counsellor row lock, তারপর slot পুনরায় যাচাই, আর শেষে `appointments_slot_unique` DB index।
8. **ছবি**: WebP-তে রূপান্তর, সর্বোচ্চ 2000px, থাম্বনেইল তৈরি। SVG আপলোড নিষিদ্ধ।

বিস্তারিত: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) · ডাটাবেজ: [Database.md](Database.md) · ডিজাইন: [UI.md](UI.md)

## ৪. মডিউল তালিকা

| গ্রুপ | মডিউল |
|---|---|
| সিস্টেম | Auth (login/logout/reset), Users, Roles & Permissions, Settings (৯টা গ্রুপ), Media Library, Activity Log, Notifications, System (cache, backup) |
| CMS | Homepage (১৪টা সেকশন), Hero Slider, Statistics, Team, Timeline, Pages (block builder), Menus, Partners, Testimonials, FAQs, Newsletter |
| Directory | Destinations (`/study-in-{slug}`), Universities, Courses, Services, Terms (category, degree type, intake) |
| Content | Success Stories (ভিজিটরও সাবমিট করতে পারে), Photo/Video Gallery, Blog & Notices |
| Business | Appointment booking wizard, Counsellor availability/time-off, Leads CRM, Applications (প্রাইভেট ডকুমেন্টসহ), Contact inbox ও reply, Email Templates |

## ৫. ডেলিভারি ফেজ (সব ✅ সম্পন্ন)

| ফেজ | বিষয় | অবস্থা |
|---|---|---|
| 1 | Foundation, পুরো DB schema, admin auth, roles, settings, media, public layout, SEO foundation | ✅ |
| 2 | CMS: homepage, about, pages, menus, rich text, public cache | ✅ |
| 3 | Destinations, universities, courses, services, ফিল্টার/সার্চ, clean URL, schema.org | ✅ |
| 4 | Success stories, gallery, blog/notices | ✅ |
| 5 | Appointments, leads, applications, contact, email templates | ✅ |
| 6 | Security, SEO (sitemap), পারফরম্যান্স, backup, release script, ডকুমেন্টেশন, ব্র্যান্ডিং | ✅ |

প্রতিটি ফেজের চেকলিস্ট: [docs/ROADMAP.md](docs/ROADMAP.md)

## ৬. বর্তমান অবস্থা (2026-11-19, দিনের শেষে)

- Backend টেস্ট: **১১৫টি পাস, ৭৯৮টি assertion** (সকালের রিভিউতে ছিল ৮৬টি)
- Frontend: `npm run build` সফল; `npm run lint`-এ **০টি error** (warning শুধু আগের ফাইলগুলোতে)
- Git: `snsbd247/pngiep` › `main`, প্রতিটা ফিচার আলাদা কমিটে
- লাইভ: https://pngiep.com (cPanel সার্ভার 169.40.116.146-এ মাইগ্রেট হয়েছে); লোকাল: `F:\Lovable\xampp` (Apache/MySQL Windows সার্ভিস), `localhost/riths`
- নতুন ফিচার: News, YouTube embed, Webmail (cPanel টোকেন বসানো, চালু), Facebook auto-post (Meta App সেটআপ বাকি), ডিরেক্টর প্রোফাইল
- সিকিউরিটি অ্যানালাইসিস রিপোর্ট: `docs/SECURITY_REPORT.md` (শুধু লোকালে; খোলা দুর্বলতা থাকায় পাবলিক রিপোতে রাখা হয় না)

## ৭. পরবর্তী পরিকল্পনা: রিভিউ থেকে পাওয়া কাজ (অগ্রাধিকার অনুযায়ী)

বিস্তারিত কারণ আর ফাইলের লোকেশন [development.md](development.md)-এর "কোড রিভিউ" অংশে আছে।

### অগ্রাধিকার ১: নিরাপত্তা
- [x] **`trustProxies(at: '*')` ঠিক করা** ✅ ২০২৬-১১-১৯ ([backend/bootstrap/app.php:33](backend/bootstrap/app.php#L33))। এখন যে কেউ `X-Forwarded-For` হেডার পাঠিয়ে IP বদলাতে পারে, ফলে login brute-force limiter আর public form limiter বাইপাস হয়। সমাধান: `.env`-এ `TRUSTED_PROXIES` রেখে শুধু আসল proxy/Cloudflare-এর IP trust করা।
- [x] **`analytics_head_code` admin পেজে না বসানো** ✅ ২০২৬-১১-১৯ (দুটোই: `/admin`-এ বসে না + শুধু Super Admin বদলাতে পারে) ([backend/app/Services/SeoService.php:98](backend/app/Services/SeoService.php#L98))। `settings.manage` থাকা Admin এখানে JS বসালে সেটা Super Admin-এর ব্রাউজারেও চলবে, ফলে "Admin roles বদলাতে পারে না" সীমাটা আর কাজ করে না। সমাধান: `/admin` পাথে স্কিপ করা, এবং/অথবা এই সেটিং শুধু Super Admin-এর জন্য রাখা।
- [x] **পাসওয়ার্ড বদলালে অন্য সব সেশন লগআউট করা** ✅ ২০২৬-১১-১৯ (Profile এবং Users → edit)।

### অগ্রাধিকার ২: ডেটা ও প্রাইভেসি
- [x] **ছাত্রদের ডকুমেন্ট মোছার ব্যবস্থা।** ✅ ২০২৬-১১-১৯ (মোছা আবেদন ৯০ দিন পর ফাইলসহ স্থায়ীভাবে মুছে যায়) Application soft-delete হয় কিন্তু ফাইল কখনো মোছে না; `model:prune` শিডিউল করা থাকলেও কোনো model `Prunable` নয়। একটা retention policy ঠিক করে (যেমন ৯০ দিন পর) force-delete আর ফাইল মোছা দরকার।
- [x] **Lead overwrite আটকানো** ✅ ২০২৬-১১-১৯ ([backend/app/Services/LeadService.php:36](backend/app/Services/LeadService.php#L36))। কারও ইমেইল দিয়ে public form সাবমিট করলে তার খোলা lead-এর নাম/ফোন বদলে যায়। নতুন তথ্য শুধু `meta.history`-তে রাখা উচিত।
- [x] **Backup dump-এ `ORDER BY` যোগ করা** ✅ ২০২৬-১১-১৯ ([backend/app/Console/Commands/BackupCommand.php:77](backend/app/Console/Commands/BackupCommand.php#L77))। ORDER BY ছাড়া `LIMIT/OFFSET` হলে বড় টেবিলে row বাদ পড়তে বা ডুপ্লিকেট হতে পারে।

### অগ্রাধিকার ৩: পারফরম্যান্স ও কোয়ালিটি
- [ ] `AppIcon` (lucide `DynamicIcon`)-এর icon map চাঙ্ক ৩৫৮ KB (gzip ৬২ KB), হোমপেজেও লোড হয়। শুধু ব্যবহৃত আইকনগুলোর একটা ছোট map বানানো যায়।
- [ ] `forms` চাঙ্ক (zod + react-hook-form, ১৩৬ KB) সব পেজে preload হয়। ফুটারের newsletter ফর্মকে zod ছাড়া করা যায়।
- [ ] oxlint-এর warning কমানো (`set-state-in-effect`, `only-export-components`)।
- [x] ✅ README-তে `scripts\build-release.ps1` আর `release\riths-<date>.zip` পাথগুলোর backslash হারিয়ে গেছে, ঠিক করা দরকার।
- [x] নিয়মিত ছোট ছোট git commit করা ✅ (এখন প্রতিটা ফিচার আলাদা কমিটে)।

### ভবিষ্যতের সম্ভাব্য ফিচার (এখনো সিদ্ধান্ত হয়নি)
- Newsletter-এ double opt-in আর unsubscribe লিংক
- Admin-এ 2FA
- বাংলা/Tok Pisin বহুভাষা সাপোর্ট (এখন শুধু `en`)

## ৮. নতুন ফিচার: প্ল্যান (2026-11-19, অনুমোদনের অপেক্ষায়)

### ৮.১ News মডিউল ✅ কোড সম্পন্ন, কমিট `8821bdd` (২০২৬-১১-১৯ সার্ভারে ডেপ্লয় হয়েছে)
- পাবলিক `/news` (সার্চ, sort, pagination) আর `/news/{slug}` (উৎসের লিংক, জনপ্রিয় খবর, আরও খবর)।
- Admin → **News** মেনু, `news.manage` পারমিশন, scheduled publish, SEO (`NewsArticle`), sitemap, সাইট সার্চ ও admin সার্চ।
- Migration `2026_11_19_000001_create_news_table`: `news` টেবিল, পারমিশন সিঙ্ক আর `/news` listing পেজ তৈরি করে।
- বাকি: হেডার মেনুতে "News" লিংক (Admin → Menus), কমিট, সার্ভারে ডেপ্লয়।

### ৮.২ Webmail (cPanel মেইলবক্স) ✅ কোড সম্পন্ন, কমিট `164c044` (ডেপ্লয় হয়েছে; টোকেন `pngiep-app` বসানো, আসল cPanel-এ মেইলবক্স চেক যাচাই করা)
- cPanel UAPI + API Token (শুধু সার্ভারের `.env`-এ, DB-তে না)।
- Users ফর্মে ইমেইল লিখলে লাইভ চেক: 🟢 নতুন মেইলবক্স তৈরি হবে / 🔵 আগের মেইলবক্সের সাথে যুক্ত হবে / ⚪ বাইরের ইমেইল, মেইলবক্স হবে না।
- Save করলে: নতুন হলে `Email::add_pop`; আগে থাকলে শুধু ইউজারের সাথে যুক্ত (মেইলবক্সে হাত দেওয়া হবে না)।
- Admin হেডারে "Webmail" বাটন → `Session::create_webmail_session_for_mail_user` দিয়ে এককালীন লগইন, Roundcube নতুন ট্যাবে। মেইলবক্সের পাসওয়ার্ড কোথাও রাখা হবে না।
- নতুন পারমিশন: `webmail.access`, `mailboxes.manage`। সব ঘটনা Activity Log-এ। টেস্টে cPanel-এর fake।

### ৮.৩ YouTube ভিডিও ✅ খ, গ, ঘ সম্পন্ন, কমিট `aa8a9c9` (ক বাদ; ডেপ্লয় হয়েছে)
- এখন আছে: স্টোরি প্রতি একটা YouTube লিংক, ডিটেইল পেজে "Video Testimonial"।
- প্রস্তাব: (ক) একাধিক ভিডিও, (খ) রিচ-টেক্সট এডিটরে "YouTube" বাটন (শুধু youtube.com / youtube-nocookie.com iframe অনুমোদিত; Blog/News/Pages-এও কাজ করবে), (গ) কার্ডে ▶ চিহ্ন, (ঘ) ভিজিটরের স্টোরি ফর্মে ভিডিও লিংক।

### ৮.৪ Facebook অটো পোস্ট (News + Blog) ✅ কোড সম্পন্ন, কমিট `0e4a459` (ডেপ্লয় হয়েছে; Meta App সেটআপ বাকি)
- **কীভাবে:** Meta Graph API দিয়ে কোম্পানির Facebook **Page**-এ পোস্ট (ব্যক্তিগত প্রোফাইলে API দিয়ে পোস্ট করা যায় না)।
- **একবারের সেটআপ:** Meta Developer App → পারমিশন `pages_manage_posts`, `pages_read_engagement` → long-lived Page Access Token। Token আর Page ID থাকবে Admin → Settings → **Facebook**-এ encrypted অবস্থায়, সাথে "Test connection" বাটন।
- **কখন পোস্ট হবে:** News/Blog ফর্মে "Facebook-এ পোস্ট করুন" চেকবক্স (ডিফল্ট চালু)। এন্ট্রি **Published** হলে পোস্ট হবে। ভবিষ্যতের তারিখ দিলে প্রতি মিনিটের scheduler ঠিক সেই সময়ে পোস্ট করবে। Draft কখনো পোস্ট হবে না। Edit করলে আবার পোস্ট হবে না, তবে "আবার পোস্ট করুন" বাটন থাকবে।
- **পোস্টের ধরন:** ছবিসহ পোস্ট (`/{page-id}/photos`)। ক্যাপশনে শিরোনাম, সংক্ষিপ্ত বিবরণ আর ওয়েবসাইটের লিংক। ছবি না থাকলে লিংক পোস্ট (`/{page-id}/feed`), সেখানে Facebook নিজে OG ছবি দেখাবে। ক্যাপশনের টেমপ্লেট Settings থেকে বদলানো যাবে।
- **ছবি:** ছবিটা পাবলিক URL থেকে Facebook নিজে নেয়, তাই এটা লাইভ সার্ভারেই কাজ করবে, লোকালে না। ছবি WebP হলে নিরাপদ থাকতে পোস্টের জন্য একটা JPEG কপি বানানো হবে।
- **ভরসাযোগ্যতা:** queue job (৩ বার চেষ্টা)। DB-তে `facebook_post_id`, `facebook_posted_at`, `facebook_error` রাখা হবে, যাতে একই এন্ট্রি দুবার পোস্ট না হয়। admin লিস্টে স্ট্যাটাস দেখাবে (✅ পোস্ট হয়েছে / ⏳ অপেক্ষায় / ❌ ব্যর্থ + কারণ)। Token মেয়াদোত্তীর্ণ বা ব্যর্থ হলে admin notification।
- **সীমাবদ্ধতা:** Meta App-কে Live mode-এ রাখতে হবে। Meta-র নিয়ম অনুযায়ী Business Verification বা App Review লাগতে পারে। এটা Facebook-এর দিক থেকে, কোডের বাইরে।
- **টেস্ট:** Graph API-র `Http::fake`। আসল পেজে কিছু পোস্ট হবে না।

### ৮.৫ ডিরেক্টর প্রোফাইল ✅ সম্পন্ন, কমিট `99aa3b4` (ডেপ্লয় হয়েছে)
- `/team/{slug}`: ছবি, পদবি, brief, key details, যোগাযোগ, "Message from the Director", পূর্ণ প্রোফাইল, অন্য সদস্য।
- বাকি: Admin → Team Members-এ প্রত্যেক ডিরেক্টরের বাণী আর প্রোফাইল লেখা (কোম্পানির কাজ)।

### ৮.৬ রিভিউয়ের নিরাপত্তা ও ডেটা সমাধান ✅ (২০২৬-১১-১৯)
- অংশ ৭-এর অগ্রাধিকার ১ ও ২-এর সব কাজ, README, ROADMAP, আর লগইন ছাড়া admin API-তে JSON 401।
- এরপরের পূর্ণ সিকিউরিটি অ্যানালাইসিস: `docs/SECURITY_REPORT.md` (শুধু লোকালে)। সেখানে পাওয়া Webmail-এর ফাঁক (Admin অন্যের মেইলবক্স যুক্ত করতে পারত) ঠিক করা হয়েছে, কমিট `df916ff`।

### ৮.৭ CSP ও SEO ✅ (২০২৬-১১-১৯)
- Content-Security-Policy: প্রতি request-এ nonce + strict-dynamic (কমিট `3fd1f5c`)।
- টেকনিক্যাল SEO ঠিক করা ও কোম্পানির করণীয়: [docs/SEO.md](docs/SEO.md)।
