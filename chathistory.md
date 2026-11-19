# Chat History

> এই ফাইলে Claude Code-এর সাথে এই প্রজেক্ট নিয়ে কথোপকথনের রেকর্ড থাকে। নতুন সেশনের কথোপকথন নিচে তারিখসহ যোগ করুন।
> আগের সেশনগুলোর (যেখানে প্রজেক্টটা তৈরি হয়েছিল) চ্যাট এই সেশনে পাওয়া যায়নি, তাই সেগুলো এখানে নেই।

---

## সেশন: 2026-11-19

### 👤 ইউজার
> পুরো প্রজেক্ট রিভিউ করেন আগে
> https://github.com/snsbd247/pngiep.git

### 🤖 Claude: যা করা হয়েছে
1. লোকাল রিপো (`f:\Lovable\riths`) যাচাই করা হয়েছে: remote `origin` = `snsbd247/pngiep`, ব্রাঞ্চ `main`, একটা কমিট (`68571d9`), working tree পরিষ্কার।
2. README, `.gitignore` আর `docs/ARCHITECTURE.md` পড়া হয়েছে।
3. Backend রিভিউ: `routes/api.php`, `routes/web.php`, `routes/console.php`, `bootstrap/app.php`, middleware, `AppServiceProvider` (rate limiter, gates), `CrudController`, `AuthController`, `SystemController`, `BackupCommand`, `MediaService`/`MediaController`, `InquiryController`, admin `ApplicationController`, `TemplateMailer`, `UserController`/`UserPolicy`/`RoleController`, `User` model, `ProfileController`, `SettingService`/`SettingController`, public ও admin `AppointmentController`, `AppointmentBooking`, `SpaController`, `SeoService`, `config/permissions.php`, Purify-এর ব্যবহার, `SectionSync`/`SectionSchema`/`SafeUrl`, `LeadService`, newsletter export, `.env.example`, CORS/session config, `public/.htaccess`, release script।
4. Frontend রিভিউ: `lib/api.js`, `router/guards.jsx`, `dangerouslySetInnerHTML`-এর সব ব্যবহার, `AppIcon`, `vite.config.js`।
5. যাচাই:
   - `php artisan test` → **৮৬টা পাস (৬২২টা assertion)**। Git Bash-এর PATH-এ `php` নেই, তাই `F:\Lovable\xampp\php\php.exe` দিয়ে চালানো হয়েছে।
   - `npm run lint` → ০টা error, প্রায় ৪০টা warning।
   - `vite build` → সফল (আউটপুট স্ক্র্যাচ ফোল্ডারে)।
6. রিভিউয়ের ফলাফল: ৯টা সমস্যা পাওয়া গেছে (২টা উচ্চ, ৩টা মাঝারি, ৪টা নিম্ন)। বিস্তারিত [development.md › কোড রিভিউ](development.md)-তে।

### 👤 ইউজার (কাজ চলার মধ্যে)
> রিভিউ শেষ হলে 5 টা .md ফাইল তৈরি করবেন
> Plan.md (পুরো প্রজেক্ট এর প্ল্যান থাকবে),
> UI.md (ডিজাইন এর প্ল্যান থাকবে)
> Database.md (ডাটাবেজ এর আর্কিটেকচার থাকবে)
> development.md (পুরো ডেভেলপমেন্ট হিস্টোরি থাকবে)
> chathistory.md (পুরো চ্যাট হিস্টোরি থাকবে)

### 🤖 Claude: যা করা হয়েছে
1. অতিরিক্ত তথ্য সংগ্রহ: `docs/ROADMAP.md`, ১৫টা মাইগ্রেশন থেকে প্রতিটি টেবিলের সংক্ষিপ্ত তালিকা, `terms` taxonomy, `frontend/src/index.css` (ডিজাইন টোকেন), রাউটের তালিকা, `config/sections.php`-এর সেকশন/ব্লক টাইপ, admin sidebar।
2. রিপোর রুটে ৫টা ফাইল তৈরি:
   - [Plan.md](Plan.md): উদ্দেশ্য, স্ট্যাক, আর্কিটেকচারের সিদ্ধান্ত, মডিউল, ফেজ, বর্তমান অবস্থা, রিভিউ থেকে অগ্রাধিকারভিত্তিক কাজের তালিকা।
   - [UI.md](UI.md): ডিজাইন নীতি, টোকেন, কম্পোনেন্ট লাইব্রেরি, পাবলিক পেজ, হোমপেজ সেকশন ও ব্লক টাইপ, admin panel, UX নিয়ম, UI উন্নতির পরিকল্পনা।
   - [Database.md](Database.md): ডিজাইনের নিয়ম, গ্রুপ অনুযায়ী সব টেবিল, সম্পর্ক, মাইগ্রেশনের ক্রম, seeder, ব্যাকআপ, জানা সমস্যা।
   - [development.md](development.md): ফেজভিত্তিক ইতিহাস, রিলিজ, ২০২৬-১১-১৯-এর কোড রিভিউ, পরিবর্তনের লগ।
   - chathistory.md (এই ফাইল)।
3. কোডে কোনো পরিবর্তন করা হয়নি, কোনো commit বা push-ও করা হয়নি।
