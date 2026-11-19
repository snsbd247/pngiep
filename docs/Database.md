# Database: আর্কিটেকচার

> সর্বশেষ আপডেট: 2026-11-20 · উৎস: `backend/database/migrations` (২৩টা ফাইল) · ইঞ্জিন: MySQL 8 / MariaDB 10.4+ (টেস্টে in-memory SQLite)

## ১. ডিজাইনের নিয়ম

| নিয়ম | কারণ |
|---|---|
| **Status আর type `string` কলামে** রাখা হয়, PHP Enum (`app/Enums`) দিয়ে যাচাই হয় | MariaDB-তে ENUM বদলাতে ALTER লাগে; string বাড়ানো সহজ |
| **Polymorphic টেবিল**: `seo_meta`, `notes`, `faqs`, `activity_logs`, `notifications` | একটা ইমপ্লিমেন্টেশন সব মডিউলে কাজে লাগে |
| **Morph map** (`user`, `page`, `destination`, `university`, `course`, `service`, `success_story`, `blog`, `lead`, `appointment`, `application`, `contact_message`, `setting`, `media`, `role`) | DB-তে PHP ক্লাসের নামের বদলে ছোট স্থির নাম থাকে |
| **`*_sections` টেবিল** (page, destination, university, service) | অর্ডার করা, চালু/বন্ধযোগ্য ব্লক; `data` JSON, তাই নতুন ব্লকে schema বদলাতে হয় না |
| **Soft delete**: users, pages, destinations, universities, courses, services, success_stories, blogs, counsellors, leads, appointments, contact_messages, applications | ভুল করে মুছলে ফেরত আনা যায় |
| **`sort_order` + `is_active`/`status`** প্রায় সব কনটেন্ট টেবিলে | ড্র্যাগ করে সাজানো আর প্রকাশ নিয়ন্ত্রণ |
| **Typed settings** (`type`, `is_public`, `is_encrypted`) | ফর্ম আর validation টাইপ থেকে তৈরি হয়; গোপন মান এনক্রিপ্টেড থাকে, কখনো ফেরত পাঠানো হয় না |
| **ডাবল বুকিং ঠেকানো**: `UNIQUE(counsellor_id, appointment_date, start_time, slot_lock)` | বাতিল হলে `slot_lock = NULL` হয়, তাই স্লট আবার খালি হয় |

## ২. টেবিলের গ্রুপ (মোট ~৬০টা)

### ২.১ Auth ও সিস্টেম
| টেবিল | মূল কলাম | নোট |
|---|---|---|
| `roles` | name, slug (U), description, is_system | super-admin, admin, content-manager, counsellor |
| `permissions` | name, slug (U), module | `config/permissions.php` থেকে seed হয় |
| `role_permissions` | role_id, permission_id | PK দুটো মিলিয়ে, cascade |
| `users` | role_id (FK, null on delete), name, email (U), phone, avatar, password, is_active, last_login_at/ip, notification_preferences (json), **mailbox (U), mailbox_linked_at, mailbox_created** (cPanel মেইলবক্স, ২০২৬-১১-১৯) | soft delete |
| `password_reset_tokens`, `sessions`, `personal_access_tokens` | Laravel স্ট্যান্ডার্ড | session ড্রাইভার: database |
| `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Laravel স্ট্যান্ডার্ড | cron-চালিত queue |
| `settings` | group, key (U), value, type, label, help, options (json), is_public, is_encrypted, sort_order | |
| `media` | uuid (U), disk, folder, path, filename, original_name, mime_type, extension, size, width, height, alt, title, variants (json: thumb), uploaded_by | |
| `seo_meta` | seoable (morph, U), title, description, keywords, canonical_url, og_*, robots, schema (json) | |
| `activity_logs` | user_id, action, module, subject (morph), description, properties (json), ip, user_agent, created_at | অডিট লগ |
| `notes` | notable (morph), user_id, body | lead/appointment/application-এর নোট |
| `email_templates` | key (U), name, subject, body, variables (json), is_active | `{{placeholder}}` |
| `notifications` | uuid, type, notifiable (morph), data, read_at | admin নোটিফিকেশন |
| `terms` | taxonomy, name, slug, description, sort_order, is_active; UNIQUE(taxonomy, slug) | page/destination/story/partner/faq category, degree_type, intake |

### ২.২ CMS
| টেবিল | মূল কলাম |
|---|---|
| `menus` | name, location (U), is_active |
| `menu_items` | menu_id, parent_id (নিজের সাথে), label, link_type, url, page_id, target, icon, sort_order |
| `pages` | title, slug (U), template, page_category_id → terms, eyebrow, subtitle, excerpt, banner_image, content, status, published_at, created_by |
| `page_sections` | page_id, key, type, eyebrow, title, subtitle, content, image, data (json), sort_order, is_active |
| `hero_slides` | badge, title, highlight, subtitle, image, mobile_image, ২টা বাটন, features (json), video_url, video_label |
| `homepage_sections` | key (U), name, eyebrow, title, subtitle, image, button, items_limit, data (json) |
| `homepage_statistics` | group, label, value, suffix, icon, description |
| `team_members` | name, **slug (U)**, designation, photo, bio, **highlights (json), profile (html), message_title, message (html)**, email, phone, social_links (json), sort_order, is_active; SEO `seo_meta`-তে। প্রোফাইল পেজ `/team/{slug}` |
| `timeline_items` | সাল-ভিত্তিক মাইলস্টোন |
| `partners`, `testimonials` | featured, status, category / rating, country_code. বিশ্ববিদ্যালয় এখানে রাখা হয় না: logo strip সরাসরি `universities` পড়ে (২০২৬-১১-২০) |
| `faqs` | question, answer, category, page_key, faqable (nullable morph) |
| `newsletter_subscribers` | email (U), name, status, ip_address |

### ২.৩ Directory
| টেবিল | মূল কলাম |
|---|---|
| `destinations` | name, slug (U), country_code, destination_category_id, capital, language, flag/thumbnail/hero, tagline, overview, ফি ও খরচের রেঞ্জ, intakes/quick_facts (json), post_study_work, video_url |
| `universities` | destination_id (FK), name, slug (U), logo/thumbnail/cover, type, campus_type, city, address, website, established_year, world/national ranking, student counts, ফি, application_fee, intakes, about, gallery, video, highlights (json) |
| `courses` | university_id, destination_id (university থেকে নেয়), name, slug (U), subject, degree_type, study_level, duration, duration_months, tuition_fee, fee_period, intakes, ielts_requirement, entry_requirements, description, application_deadline, highlights; INDEX(destination_id, study_level, status) |
| `services` | title, slug (U), icon, image, description, features (json), cta, is_bookable |
| `destination_sections`, `university_sections`, `service_sections` | `page_sections`-এর মতো একই গঠন |

### ২.৪ Content
| টেবিল | মূল কলাম |
|---|---|
| `success_stories` | student_name, slug (U), photo, cover, destination/university/course (FK), story_category_id, *_name টেক্সট, study_level, intake, year, outcome, excerpt, story, journey/gallery (json), quote, video, submitter_email; INDEX(status, destination_id) |
| `gallery_categories` | name, slug, type (photo/video), icon; UNIQUE(type, slug) |
| `gallery_albums` → `gallery_images` | অ্যালবাম, cover, event_date → ছবি, width/height |
| `gallery_videos` | youtube_url, youtube_id, thumbnail, duration |
| `blog_categories`, `blog_tags`, `blog_post_tags` | ক্যাটাগরি (type, icon), ট্যাগ, pivot |
| `blogs` | category, destination, author, type (blog/notice), title, slug (U), featured_image, content, published_at, status, views_count, **facebook_post, facebook_post_id, facebook_posted_at, facebook_error**; INDEX(status, published_at) |
| `news` | author, author_name, title, slug (U), featured_image, excerpt, content, source_name, source_url, published_at, status, is_featured, views_count, facebook_* (blogs-এর মতো); soft delete; INDEX(status, published_at) |

### ২.৫ Business (CRM)
| টেবিল | মূল কলাম |
|---|---|
| `counsellors` | user_id (U, nullable: লগইন অ্যাকাউন্টের সাথে লিংক), name, slug, photo, designation, bio, languages (json), slot_duration, status |
| `counsellor_service` | pivot (counsellor ↔ service) |
| `counsellor_availabilities` | counsellor_id, day_of_week, start_time, end_time, is_active |
| `counsellor_time_offs` | counsellor_id, date, start/end time (null = পুরো দিন), reason |
| `leads` | name, email, phone, source, status, destination/university/course, study_level, message, meta (json: history), assigned_to, last_contacted_at; INDEX(email, status) |
| `appointments` | reference (U), service/counsellor/lead/destination, appointment_date, start/end time, duration, mode, ছাত্রের তথ্য, status, **slot_lock**, rescheduled_from_id (নিজের সাথে), cancellation_reason, confirmed/cancelled/completed_at, **manage_token (U)** |
| `contact_messages` | lead_id, name, email, phone, inquiry_type, subject, message, status, read_at, reply, replied_at |
| `applications` | reference (U, `APP-XXXXXXXX`), lead/destination/university/course, ছাত্রের তথ্য, study_level, intake, status, assigned_to |
| `application_documents` | application_id, type (DocumentType enum), original_name, path (প্রাইভেট `documents` ডিস্ক), mime_type, size |

## ৩. সম্পর্কের সারাংশ

```
roles 1─* users 1─0..1 counsellors *─* services
destinations 1─* universities 1─* courses
destinations/universities/courses ─* leads, applications, success_stories
leads 1─* appointments, applications, contact_messages
appointments 1─0..1 appointments (rescheduled_from_id)
applications 1─* application_documents
pages/destinations/universities/services 1─* *_sections
যেকোনো model ─ seo_meta / notes / faqs / activity_logs (polymorphic)
terms ─ pages.page_category_id, destinations.destination_category_id, success_stories.story_category_id
```

## ৪. মাইগ্রেশনের ক্রম

| ফাইল | কী করে |
|---|---|
| `0001_01_01_000000…000002` | roles/permissions/users/sessions, cache, jobs |
| `2026_01_01_000001…000005` | system, CMS, directory, content, business: সব টেবিল Phase 1-এ একসাথে তৈরি |
| `2026_01_02…2026_01_05` | Phase 2–5-এর অতিরিক্ত কলাম ও ইনডেক্স |
| `2026_01_06_000001` | `terms` টেবিল + pages/destinations/success_stories-এ category FK |
| `2026_01_06_000002` | `users.notification_preferences` |
| `2026_10_07_…` | `personal_access_tokens` (Sanctum) |
| `2026_11_19_000001` | `news` টেবিল, `news.manage` পারমিশন, `/news` listing পেজ |
| `2026_11_19_000002` | `users.mailbox*`, পারমিশন `webmail.access` ও `mailboxes.manage` |
| `2026_11_19_000003` | blogs/news-এ `facebook_*` কলাম (পুরনো এন্ট্রিতে `facebook_post = false`), Settings › Facebook |
| `2026_11_19_000004` | `team_members`-এ slug (পুরনো সদস্যদের জন্য তৈরি), highlights, profile, message_title, message |

## ৫. Seeder

`DatabaseSeeder` → RolePermission, AdminUser (`SEED_ADMIN_*`), Setting, Branding, Menu, Page, Term, EmailTemplate, Content, PartnerDirectory, DemoDirectory, DemoCrm।
ডেমো কনটেন্ট একটা manifest-এ ট্র্যাক করা হয়; `php artisan app:demo-content remove` দিয়ে মোছা যায়।

## ৬. ব্যাকআপ

`php artisan app:backup [--files] [--keep=14]` → `storage/app/backups/db_*.sql.gz` (pure-PHP dump) এবং `files_*.zip`।
শিডিউল: ডাটাবেজ প্রতিদিন 02:30, ফাইল প্রতি শুক্রবার 03:00। ডাউনলোড শুধু Super Admin করতে পারে, এবং প্রতিটি ডাউনলোড লগ হয়।

## ৭. ডাটাবেজ-সংক্রান্ত জানা সমস্যা ও পরিকল্পনা

- [x] **ডকুমেন্ট retention** (২০২৬-১১-১৯): `Application` এখন `Prunable`। admin যে আবেদন মুছেছেন, মোছার ৯০ দিন পর `model:prune` (প্রতিদিন) সেটা আর তার ফাইল স্থায়ীভাবে মুছে দেয়। সক্রিয় আবেদনে হাত দেয় না।
- [x] **Backup dump** (২০২৬-১১-১৯): প্রতিটা টেবিল primary key অনুযায়ী `ORDER BY` করে পড়া হয়।
- [x] **Lead merge** (২০২৬-১১-১৯): খোলা lead-এর শুধু খালি ঘর পূরণ হয়; ভিন্ন নাম/ফোন `meta.history[].submitted`-এ থাকে, admin-এ দেখায়।
- [ ] চাইলে `sessions` টেবিল backup থেকে বাদ রাখা যায় (রিস্টোর করলে পুরোনো সেশন ফিরে আসে)।
