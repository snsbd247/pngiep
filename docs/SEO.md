# SEO গাইড: pngiep.com

> শেষ আপডেট: 2026-11-19। কেউই "সবসময় টপ র‍্যাংকিং" নিশ্চিত করতে পারে না: র‍্যাংকিং ঠিক হয় Google-এর অ্যালগরিদম, প্রতিযোগী, কনটেন্টের মান আর অন্য সাইটের লিংক দিয়ে।
> কোডের দিক (টেকনিক্যাল SEO) এখন পুরোপুরি সাজানো; নিচের অংশ ২ নিয়মিত করলে র‍্যাংকিং ধীরে ধীরে বাড়বে।

## ১. টেকনিক্যাল SEO: যা সাইটে করা আছে

| বিষয় | কী আছে |
|---|---|
| প্রতিটা পেজের meta | আলাদা `<title>`, description, canonical, Open Graph, Twitter card; Admin-এ প্রতিটা আইটেমের SEO প্যানেল দিয়ে বদলানো যায় |
| Title-এর দৈর্ঘ্য | ৬০ অক্ষরের বেশি হলে "PNG International Education Portal"-এর বদলে ছোট নাম "PNGIEP" (Settings › SEO › Short site name); তাতেও লম্বা হলে শুধু পেজের নাম |
| Structured data (JSON-LD) | হোম: EducationalOrganization + **LocalBusiness** (ঠিকানা, ফোন, ইমেইল, লোগো, সোশ্যাল), WebSite + সার্চ বক্স; বিশ্ববিদ্যালয়: CollegeOrUniversity; কোর্স: Course; সার্ভিস: Service; ব্লগ: BlogPosting/NewsArticle; নিউজ: NewsArticle; ডিরেক্টর: Person; **সব পেজে BreadcrumbList** |
| JavaScript-এর পরে | **ঠিক করা (২০২৬-১১-১৯):** আগে React চালু হয়ে canonical আর সব structured data মুছে দিত, তাই Google (যে JS চালিয়ে index করে) এগুলো দেখত না। এখন এগুলো থাকে; একটাই `<title>` |
| Sitemap | `/sitemap.xml` (সব প্রকাশিত পেজ, lastmod সহ; noindex বাদ), robots.txt-এ লিংক; cookie ছাড়া, ১ ঘণ্টা ক্যাশ |
| robots.txt | `/admin`, `/api/v1/admin/`, ব্যক্তিগত অ্যাপয়েন্টমেন্ট লিংক বন্ধ; **পাবলিক API (`/api/v1/`) খোলা**: Google পেজ রেন্ডার করতে এটা লাগে (আগে বন্ধ থাকায় Search Console "indexing issues" দেখাত, ২০২৬-১১-১৯ ঠিক করা)। Settings › SEO-র Custom robots.txt খালি রাখুন |
| ইনডেক্স না করা | `/search` (noindex, follow), অ্যাডমিন, অ্যাপয়েন্টমেন্ট লিংক, ড্রাফট; ভুল ঠিকানায় আসল 404 (`/wp-login.php`-এর মতো ফাইল-ঠিকানাসহ) |
| URL | পরিষ্কার: `/study-in-india`, `/universities/parul-university`, `/news/…`; HTTP→HTTPS আর www→মূল ডোমেইন 301 |
| গতি | ছবি WebP-তে রূপান্তর ও ছোট করা, lazy loading, কোড-স্প্লিটিং (admin কোড ভিজিটর ডাউনলোড করে না), `/spa/assets` এক বছর ক্যাশ, gzip |
| নিরাপত্তা | HTTPS + HSTS, CSP: Google নিরাপদ সাইটকে অগ্রাধিকার দেয় |

## ২. কোম্পানির করণীয় (সবচেয়ে বেশি প্রভাব এগুলোর)

### এখনই (একবার)
1. **Google Search Console** (ইতিমধ্যে যুক্ত): Sitemaps-এ `https://pngiep.com/sitemap.xml` আবার Submit করুন। সার্ভার ১৯ নভেম্বর নতুন IP-তে গেছে; "Couldn't fetch" সাধারণত DNS আপডেটের পরে নিজেই ঠিক হয়। কয়েক দিন পরেও থাকলে URL Inspection › Test live URL দিয়ে sitemap পরীক্ষা করুন।
2. **Google Business Profile** (business.google.com): "PNG International Education Portal", ক্যাটাগরি *Educational consultant*, ঠিক সেই ঠিকানা/ফোন যা সাইটে আছে, অফিস সময়, ছবি, ওয়েবসাইট লিংক। "study abroad Port Moresby"-এর মতো স্থানীয় সার্চে এটাই সবচেয়ে বড় প্রভাব ফেলে।
3. **Bing Webmaster Tools**: Search Console থেকে ইমপোর্ট করুন (Bing আর DuckDuckGo-র জন্য)।
4. **PageSpeed Insights** (pagespeed.web.dev): হোম, একটা দেশের পেজ আর একটা কোর্স পেজ মাপুন; মোবাইলে ৭০-এর নিচে হলে আমাকে ফলাফল দিন।
5. Settings › Social-এ শুধু আসল প্রোফাইলের লিংক রাখুন (Facebook, LinkedIn ইত্যাদি); এগুলো Organization schema-য় যায়।

### নিয়মিত (প্রতি সপ্তাহে / মাসে)
1. **কনটেন্ট**: সপ্তাহে অন্তত ১টা Blog বা News: ভিসার নিয়ম, স্কলারশিপের শেষ তারিখ, ভর্তির ধাপ, "PNG থেকে ভারতে পড়ার খরচ", ছাত্রের অভিজ্ঞতা। মানুষ যা খোঁজে সেই প্রশ্নের উত্তর দিন।
2. **প্রতিটা পেজের নিজস্ব লেখা**: দেশ, বিশ্ববিদ্যালয়, কোর্স আর সার্ভিসের পেজে অন্তত ৩০০ শব্দের নিজস্ব বর্ণনা; অন্য সাইট থেকে কপি করবেন না।
3. **ডিরেক্টর প্রোফাইল ও Success Story**: আসল ছবি, আসল গল্প, YouTube ভিডিও; বিশ্বাসযোগ্যতা (E-E-A-T) বাড়ায়।
4. **ছবির alt টেক্সট**: Media Library-তে ছবির বর্ণনা লিখুন।
5. **SEO প্যানেল**: গুরুত্বপূর্ণ পেজে (দেশ, জনপ্রিয় কোর্স) নিজের ভাষায় description (১৫০–১৬০ অক্ষর) লিখুন।
6. **রিভিউ**: সন্তুষ্ট ছাত্রদের Google Business Profile-এ রিভিউ দিতে বলুন।

### অন্য সাইট থেকে লিংক (backlinks)
- অংশীদার বিশ্ববিদ্যালয়ের "authorised representative/agent" তালিকায় আপনার সাইটের লিংক চান।
- PNG-এর ব্যবসা ডিরেক্টরি, চেম্বার অব কমার্স, স্থানীয় সংবাদমাধ্যম, স্কুল/কলেজের ক্যারিয়ার পেজ।
- Facebook পেজে প্রতিটা নিউজ/ব্লগ শেয়ার হয় (Facebook auto-post চালু করলে নিজেই হবে)।
- কেনা লিংক বা "SEO প্যাকেজ" থেকে দূরে থাকুন: Google শাস্তি দিতে পারে।

## ৩. মাপার উপায়
- Search Console › Performance: কোন সার্চে সাইট দেখায়, ক্লিক, গড় অবস্থান (প্রতি মাসে দেখুন)।
- Search Console › Pages: কোন পেজ index হয়নি ও কেন।
- Search Console › Enhancements: Breadcrumbs, Organization ইত্যাদি structured data-র ত্রুটি।
- Rich Results Test (search.google.com/test/rich-results): যেকোনো পেজের structured data পরীক্ষা।
