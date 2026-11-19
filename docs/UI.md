# UI: ডিজাইন প্ল্যান

> সর্বশেষ আপডেট: 2026-11-20 · কোড: `frontend/src`

## ১. ডিজাইন নীতি

- **Admin-চালিত ব্র্যান্ড**: ওয়েবসাইটের রং, লোগো, ফেভিকন, সোশ্যাল লিংক, মেনু সব Settings থেকে আসে। কোডে কোম্পানির কোনো তথ্য নেই।
- **দুটো আলাদা থিম**: পাবলিক সাইট ব্র্যান্ডের রং ব্যবহার করে; admin panel-এর নিজস্ব বেগুনি থিম আছে (`body.admin-theme`), যা ব্র্যান্ডের রঙে বদলায় না।
- **মোবাইল-ফার্স্ট**: 320px থেকে 1440px পর্যন্ত প্রতিটি sitemap URL রেস্পন্সিভ QA করা হয়েছে (Phase 6)।
- **প্রতিটি অবস্থার UI আছে**: loading (Skeleton), empty, error (`components/ui/States.jsx`)।
- **রেফারেন্স ডিজাইন**: Phase 2-এ top bar, navbar, footer, CTA আর inner-page hero "RITHS Global" স্ক্রিনশটের সাথে মিলিয়ে বানানো।

## ২. ডিজাইন টোকেন (`frontend/src/index.css`)

| টোকেন | ডিফল্ট | উৎস |
|---|---|---|
| `--brand-primary` | `#1a3fa6` (নীল) | Settings › Appearance (runtime-এ `SiteProvider` বসায়) |
| `--brand-secondary` | `#0b1d52` (গাঢ় নেভি) | Settings › Appearance |
| `--brand-accent` | `#1e8a3e` (সবুজ) | Settings › Appearance |
| Admin primary / secondary / accent | `#4a2bd4` / `#261a7d` / `#7b5cf0` | স্থির |
| `--color-ink` | `#0f0d1d` | হেডিংয়ের রং |
| `lavender` | primary 5% + `#f7f8fc` | সেকশনের হালকা ব্যাকগ্রাউন্ড |

- **রঙের স্কেল**: `primary-50 … primary-900` শুধু একটা admin রং থেকে `color-mix(in oklab, …)` দিয়ে তৈরি হয়।
- **ফন্ট**: Poppins (400–800, Google Fonts); বডির লেখা 15px, `text-slate-600`।
- **ছায়া**: `shadow-card`, `shadow-card-hover`, `shadow-glow` (primary রঙের আভা)।
- **কোণা**: `--radius-card: 1rem`।
- **ব্রেকপয়েন্ট**: Tailwind-এর ডিফল্টের পাশাপাশি `wide` = 1400px (admin dashboard-এর পুরো ডেস্কটপ গ্রিড)।
- **আইকন**: lucide-react; admin যে আইকন নাম বাছাই করে তা `AppIcon` (`DynamicIcon`) দিয়ে রেন্ডার হয়।
- **পতাকা**: `flag-icons`; **ক্যারোসেল**: Swiper।

## ৩. কম্পোনেন্ট লাইব্রেরি

| ফোল্ডার | কম্পোনেন্ট |
|---|---|
| `ui/` | Button, Badge, Modal, ConfirmDialog, Toast, Pagination, SearchBar, Image, AppIcon, Flag, Lightbox, HighlightText, SmartLink, SocialIcon, States |
| `forms/` | FormField, FormInput, FormTextarea, FormSelect, FormSwitch, FileUploader, ImageField |
| `layout/` | TopBar, Navbar (dropdown, সার্চ, মোবাইল drawer; মেনু Admin › Menus থেকে: Home · About ▾ · Destinations ▾ · Universities ▾ · Success Stories · Blog · News · Contact), Footer, Breadcrumb, SiteLogo, SocialLinks, PublicLayout |
| `sections/` | HeroBanner, SectionHeader, CTASection, Carousel, FAQAccordion, NewsletterBar, PartnerLogoSlider (ভার্সিটির লোগো Universities থেকে, ভার্সিটির পেজে লিংক), StatsBar |
| `blocks/` | PageBlocks (CMS ব্লক রেন্ডারার), shared (FeatureGrid, PhotoGrid, QuoteCarousel, SplitChecklist) |
| `cards/`, `directory/` | Team/Video কার্ড, University/Course কার্ড, FilterSidebar, ListingLayout, EnquiryForm, SidebarCards |
| `content/`, `public/` | ContentWidgets, StoryForm, BookingWidgets, FormControls |
| `admin/` | AdminLayout, Sidebar, Topbar, DataTable, Card, StatCard, PageHeader, MediaPickerModal, SortableList (dnd-kit), Avatar, dashboard উইজেট |
| `admin/fields/` | SchemaField (স্কিমা থেকে ফর্ম), RichTextEditor (TipTap), IconPicker ইত্যাদি |

## ৪. পাবলিক সাইটের পেজ

| URL | পেজ | বৈশিষ্ট্য |
|---|---|---|
| `/` | Home | CMS থেকে ১৪টা সেকশন, চালু/বন্ধ আর ড্র্যাগ করে সাজানো যায় |
| `/about`, `/{slug}` | CMS Page | ব্লক বিল্ডার; `SlugPage` `/study-in-{slug}`-ও সামলায় |
| `/destinations`, `/study-in-{slug}` | গন্তব্য দেশ | সেকশন বিল্ডার, quick facts, ম্যাপ, সম্পর্কিত বিশ্ববিদ্যালয় |
| `/universities`, `/universities/:slug` | বিশ্ববিদ্যালয় | ফিল্টার + গণনা, ফি রেঞ্জ, সর্টিং (URL-এ শেয়ারযোগ্য) |
| `/courses`, `/courses/:slug` | কোর্স | লেভেল ট্যাব, এন্ট্রি রিকোয়ারমেন্ট, সাইডবার এনকোয়ারি ফর্ম |
| `/services`, `/services/:slug` | সার্ভিস | সেকশন বিল্ডার, বুক করার CTA |
| `/success-stories`, `/:slug` | সাকসেস স্টোরি | দেশের ট্যাব, ভিডিও টেস্টিমোনিয়াল, "আপনার গল্প শেয়ার করুন" ফর্ম |
| `/gallery`, `/gallery/photos`, `/gallery/videos` | গ্যালারি | ক্যাটাগরি, অ্যালবাম, lightbox, YouTube privacy embed |
| `/blog`, `/blog/:slug` | ব্লগ ও নোটিশ | শেয়ার বাটন, সম্পর্কিত পোস্ট, জনপ্রিয় পোস্ট |
| `/news`, `/news/:slug` | নিউজ (২০২৬-১১-১৯) | সার্চ, sort, জনপ্রিয় নিউজ, উৎসের লিংক, "More News"; Blog-এর কার্ড ডিজাইন (`BlogCard basePath="/news"`) |
| `/team/:slug` | ডিরেক্টর প্রোফাইল (২০২৬-১১-১৯) | ছবি (৪:৫), পদবি, brief, key details চেকলিস্ট, যোগাযোগ, "Message from the Director" কার্ড, পূর্ণ প্রোফাইল, অন্য সদস্যদের কার্ড; About-এর `TeamCard` থেকে "View Profile" |
| `/search` | গ্লোবাল সার্চ | |
| `/book-appointment` | বুকিং উইজার্ড | সার্ভিস → কাউন্সেলর → তারিখ → সময় → তথ্য → নিশ্চিতকরণ, `.ics` ডাউনলোড |
| `/appointment/:token` | অ্যাপয়েন্টমেন্ট ম্যানেজ | দেখা, রিশিডিউল, বাতিল (noindex) |
| `/contact` | যোগাযোগ | ইনফো কার্ড, অফিস + ম্যাপ, FAQ |
| `/apply` | আবেদন | ডকুমেন্ট আপলোড (PDF/JPG/PNG/Word, ≤5 MB) |
| `*` | 404 | |

### হোমপেজ সেকশন (`config/sections.php › homepage`)
hero · statistics · destinations · services · universities · pathways · why_choose · success_stories · testimonials · gallery · latest_news · partners · cta · newsletter

### CMS ব্লক টাইপ (`config/sections.php › page_types`)
listing · rich_text · text_images · list · table · gallery · related_universities · related_courses · intro · statistics · feature_cards · features_with_image · checklist · timeline · team · partners · testimonials · faq · cta · story_videos · offices · sidebar_card

প্রতিটি ব্লকের ফিল্ড-টাইপ: `text, textarea, html, number, toggle, select, icon, url, image, images, repeater`। একই স্কিমা থেকে admin ফর্ম (`SchemaField`), সার্ভার validation আর HTML purification তৈরি হয়।

## ৫. Admin Panel

- **লেআউট**: বাম পাশে sidebar (পারমিশন অনুযায়ী ফিল্টার করা, `config/adminNav.js`), উপরে topbar (সার্চ, নোটিফিকেশন, প্রোফাইল), মাঝে কনটেন্ট।
- **Sidebar গ্রুপ**: Dashboard · Homepage · Pages · Destinations · Universities · Courses · Services · Success Stories · Gallery · Blog/Notice · Partners · Testimonials · FAQs · Appointments · Counsellors · Leads · Applications · Contact Messages · Newsletter · Media Library · Menus · Users & Roles · Settings · Notifications · Activity Log · Profile
- **জেনেরিক পেজ**: `content/:resource` → `ResourceListPage` (DataTable, সার্চ, ফিল্টার, ড্র্যাগ করে সাজানো), `content/:resource/:id` → `ResourceFormPage` (স্কিমা-চালিত ফর্ম, SEO ফিল্ড, সেকশন বিল্ডার)।
- **বিশেষ পেজ**: Dashboard (স্ট্যাট কার্ড, রিপোর্ট), Settings (গ্রুপ ট্যাব), System, Media Library (গ্রিড, ফোল্ডার, বাল্ক ডিলিট), Menus (nested drag), Homepage Sections, CRM পেজগুলো (তালিকা + ডিটেইল + নোট)।
- **Auth পেজ**: login, forgot-password, reset-password (`GuestOnly` গার্ড)।
- **২০২৬-১১-১৯-এর সংযোজন**:
  - Topbar-এ **Webmail** বাটন (লিংক করা মেইলবক্স থাকলে; নতুন ট্যাবে এককালীন cPanel সেশন POST করে)।
  - Sidebar-এ **News** মেনু আর Settings › **Facebook Auto-post**।
  - Users ফর্মে ইমেইলের নিচে লাইভ মেইলবক্স-স্ট্যাটাস, "Staff mailbox & Webmail" সুইচ, নতুন মেইলবক্সের পাসওয়ার্ড একবার দেখানোর মোডাল, তালিকায় Mailbox কলাম।
  - News/Blog তালিকায় **Facebook** কলাম (Posted / Waiting / On publish / Failed + Retry / Post now)।
  - রিচ-টেক্সট এডিটরে **YouTube** বাটন (`SquarePlay` আইকন; embed `.prose-content`-এ ১৬:৯, রেসপনসিভ)।
  - Success story কার্ডে "▶ Video" ব্যাজ; ভিজিটরের স্টোরি ফর্মে ঐচ্ছিক YouTube লিংক।
  - Super Admin ছাড়া অন্যদের জন্য Settings-এর analytics code ঘর read-only।

## ৬. UX নিয়ম

- ফর্ম: React Hook Form + Zod; সার্ভারের 422 এরর ফিল্ডের নিচে দেখায় (`ApiError.errors`)।
- সব public ফর্মে honeypot (`website`) ফিল্ড আর "agree" চেকবক্স আছে।
- ডেটা: TanStack Query (cache, refetch); API envelope `{success, message, data, meta}`।
- SEO: সার্ভার থেকে আসা meta-তে `data-seo` থাকে, React Helmet চালু হলে সেগুলো সরিয়ে দেয়।
- অ্যাক্সেসিবিলিটি: FAQ accordion-এ `id`/aria, ছবিতে alt (Media-তে alt ফিল্ড আছে)।

## ৭. UI-সংক্রান্ত উন্নতির পরিকল্পনা

- [ ] `AppIcon` বান্ডেল ছোট করা (৩৫৮ KB-এর icon map → শুধু ব্যবহৃত আইকন)।
- [ ] হোমপেজে `forms` (zod) চাঙ্কের preload বাদ দেওয়া।
- [ ] `set-state-in-effect` lint warning থাকা কম্পোনেন্টগুলো রিফ্যাক্টর করা (SearchBar, FilterSidebar, SettingsPage, MenusPage, HomepageSectionsPage, AdminLayout, ResourceListPage)।
- [ ] ডার্ক মোড নেই; দরকার হলে পরে যোগ করা যায় (টোকেনগুলো আগে থেকেই CSS variable-এ আছে)।
