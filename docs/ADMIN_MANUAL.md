# Admin manual

A guide for office staff who run the website and handle student enquiries.
Sign in at **`/admin`** with your own email and password. Never share accounts. Each person's
actions are recorded in the Activity Log.

### Finding your way around

The left menu has 26 sections, in this order: Dashboard, Homepage, Pages, Destinations, Universities, Courses,
Services, Success Stories, Gallery, Blog / Notice, Partners, Testimonials, FAQs, Appointments,
Counsellors, Leads, Applications, Contact Messages, Media Library, Menus, Users & Roles, Settings,
Notifications, Activity Log, Profile and Logout.

- Click a section to open its sub-menu. Sub-menus are shortcuts to filtered lists, for example
  *Appointments › Pending* or *Blog / Notice › Drafts*. Clicking **Homepage** also opens the homepage editor.
- Each section manages its own categories. Examples: *Pages › Page Categories*, *Courses › Degree Types / Intakes*
  and *Partners › Partner Categories*. Renaming a category updates every record that uses it.
- You only see the menu items your role is allowed to use.
- **Notifications** (the bell, top right) lists new enquiries, bookings, applications, messages and
  story submissions. Under *Notifications › Notification Settings* you choose which types you receive.
- **Settings › System Settings** (Admins) shows the site's health and lets you clear caches and make backups.
  Only a Super Admin can download a backup.

---

## 1. Roles: who can do what

| Role | Can use |
|------|---------|
| **Super Admin** | Everything, including Roles & Permissions |
| **Admin** | Everything except editing roles |
| **Content Manager** | Website content only: pages, homepage, directory, blog, gallery, media. No settings, users or student data. |
| **Counsellor** | Dashboard and **their own** appointments only |

Admins create accounts under **System › Users**. Permissions per role can be adjusted under
**Roles & Permissions** (Super Admin only). If you open a page you are not allowed to use,
you will see "You do not have permission to view this page".

---

## 2. Daily routine (Business)

### Appointments
**Business › Appointments** lists every consultation booked on the website.

- Switch between **Today / Upcoming / Past / All**, and filter by status, counsellor or date.
- Open a booking to:
  - **Confirm** a pending booking. The student gets a confirmation email.
  - **Reschedule**: pick a new date, and only free times are shown. The old booking is marked
    *Rescheduled* and the student is emailed the new time.
  - **Cancel**, with an optional reason that is included in the email. The time becomes free again.
  - Mark **Completed** or **No-show** after the meeting.
  - Add **internal notes**. Students never see these.
- Students can also reschedule or cancel themselves from the link in their email, up to
  the *cancellation notice* set in Settings › Appointment (default 24 hours before).

New bookings are **Pending** until someone confirms them. To confirm automatically, switch on
Settings › Appointment › *Auto-confirm new bookings*.

### Leads
Every enquiry from the website becomes a **lead**: contact form, booking, Apply Now,
university, course or destination enquiry forms, and newsletter sign-ups. If the same email
writes again while their lead is still open, the new message is added to the same lead
under "Earlier enquiries".

- Set the **Status** (New → Contacted → Interested → Follow up → Converted / Closed) and
  **Assigned to** on the right.
- The lead page also shows that person's appointments, applications and messages.

### Applications
**Business › Applications** holds Apply Now submissions.

- Open one to see the program they chose and their **documents**. *Download* saves the
  file to your computer. Documents are private: they cannot be opened with a public link,
  and every download is recorded in the Activity Log.
- Update the **Status** as the case progresses and assign a staff member.

### Messages
**Business › Messages** is the contact form inbox. Unread messages have a dot.

- Opening a message marks it *Read*.
- Type a reply and press **Send reply**. It is emailed to the sender and the message
  becomes *Replied*.
- **Archive** hides a message from the inbox. Use the *Archived* tab to find it again.

### Newsletter
**Business › Newsletter** lists subscribers. **Export CSV** downloads the list for your
email marketing tool.

---

## 3. Counsellors and opening hours

**Business › Counsellors** › open a counsellor:

- **Services offered**: students only see this counsellor for the ticked services.
- **Weekly availability**: one row per time window, for example *Sunday 10:00–13:00* and
  *Sunday 14:00–16:00* for a lunch break. Times use the booking timezone in Settings ›
  Appointment.
- **Days off / blocked times**: a date with no times blocks the whole day. Add times to
  block only part of a day.
- **Login account**: link the counsellor's user account (role *Counsellor*) so they see
  their own appointments when they sign in.
- **Appointment length** overrides the default meeting length.

A counsellor with upcoming bookings cannot be deleted. Set them to *Draft* to hide them
from the booking page instead.

---

## 4. Website content

| Menu | What it controls |
|------|------------------|
| **Website › Homepage** | Order, visibility and text of homepage sections |
| **Hero Slides / Statistics** | Homepage slider; number counters ("10,000+ students") |
| **Pages** | About, Contact, Book Appointment and other pages. Each page has *Sections*, blocks you can add, reorder and edit. Built-in list pages (Universities, Blog …) cannot be deleted, but their banner and sections can be edited. |
| **Menus** | Header, footer and quick-link menus (drag to reorder) |
| **Media Library** | All uploaded images. Large photos are resized and converted to WebP automatically. |
| **Directory** | Destinations (`/study-in-…` pages), Universities, Courses |
| **Content** | Blog & notices, success stories, photo albums, videos, services, testimonials, partners, team, timeline, FAQs |

Tips:

- **Status**: *Draft* hides an item from the website; *Published* shows it.
- In titles, wrap words in `*asterisks*` to show them in the brand colour, e.g. `Get in *Touch*`.
- Each item has an **SEO** panel (search title, description, share image). Leave it empty to
  use sensible defaults.
- Blog posts can be **scheduled** by setting a future publish date.
- Visitors' "Share your story" submissions arrive as *Draft* success stories. Review them
  before publishing.
- Videos only need the YouTube link.

---

## 5. Settings (Admins)

| Group | Contents |
|-------|----------|
| General | Site name, tagline, logo, favicon |
| Header / Footer | Top bar message, header button, footer text |
| Contact | Phones, WhatsApp, email, address, office hours, contact-form inquiry types (one per line), map |
| Social | Social media links |
| Appearance | Brand colours |
| SEO | Default title/description/share image, Google verification, analytics code, robots.txt |
| Appointment | Timezone and label, length, booking window, minimum notice, cancellation notice and policy, working days, auto-confirm, meeting modes |
| Email | SMTP server, sender, **admin notification email**, *Send test* |
| Forms | Texts on the enquiry, story and apply forms |

**Email templates** (Business › Email Templates) contain the wording of every automatic
email. Keep the `{{placeholders}}` listed on the right, because they are replaced with the
student's details. Switch a template off to stop sending that email.

---

## 6. Demo content

At launch the website shows **demo** courses (with sample fees), success stories, testimonials,
photo albums, videos and blog posts, so no page looks empty. Edit them, delete them one by one,
or ask your developer to remove all remaining demo items at once
(`php artisan app:demo-content remove`). Anything you created yourself is never removed.

## 7. Good practice

- Reply to new leads and messages the same working day. The Dashboard shows today's numbers.
- Do not download student documents to shared or public computers.
- Remove accounts of staff who leave (Users › delete, or set them inactive).
- The website backs itself up every night. Ask your developer or hosting provider to download
  a copy regularly (see DEPLOYMENT.md § 12).
