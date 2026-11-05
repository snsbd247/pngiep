import {
  Activity,
  Bell,
  BookOpenText,
  Building2,
  CalendarDays,
  ChartNoAxesCombined,
  CircleHelp,
  FileText,
  FolderOpen,
  House,
  Images,
  Landmark,
  LayoutList,
  LogOut,
  Mail,
  MessageCircle,
  Newspaper,
  Settings,
  ShieldCheck,
  ThumbsUp,
  UserCheck,
  UserCog,
  UserRound,
  Users,
} from 'lucide-react'

/**
 * Admin sidebar, in the order of the PNGIEP admin specification.
 *
 * Item:  { label, to, icon?, permission?, end? }
 * Group: { label, icon?, to?, children: [...] }; groups nest (Gallery › Photo Gallery › …).
 *        A group with `to` also opens that page when its label is clicked.
 * Action: { label, icon, action: 'logout' }
 *
 * Anything the user lacks permission for is hidden, and so is a group left without children.
 * Links may carry ?query (filtered views) or #anchor (a section inside a page).
 */
const section = (label, key) => ({ label, to: `/admin/homepage?section=${key}`, permission: 'homepage.manage' })
const status = (base, permission, statuses) => statuses.map(([label, value]) => ({ label, to: `${base}${value}`, permission }))

export const adminNav = [
  { label: 'Dashboard', to: '/admin', icon: House, permission: 'dashboard.view', end: true },
  {
    label: 'Homepage', icon: House, to: '/admin/homepage', children: [
      { label: 'Hero Slider', to: '/admin/content/hero-slides', permission: 'homepage.manage' },
      { label: 'Statistics', to: '/admin/content/statistics', permission: 'homepage.manage' },
      section('Destinations Section', 'destinations'),
      section('Universities Section', 'universities'),
      section('Services Section', 'services'),
      section('Study Pathways', 'pathways'),
      section('Why Choose Us', 'why_choose'),
      section('Success Stories Section', 'success_stories'),
      section('Photo Gallery Section', 'gallery'),
      { label: 'Video Gallery Section', to: '/admin/homepage?section=gallery&part=video', permission: 'homepage.manage' },
      section('Blog / Notice Section', 'latest_news'),
      section('Testimonials Section', 'testimonials'),
      section('Partners Section', 'partners'),
      section('CTA Sections', 'cta'),
      { label: 'Homepage SEO', to: '/admin/settings/seo', permission: 'settings.manage' },
    ],
  },
  {
    label: 'Pages', icon: FileText, children: [
      { label: 'All Pages', to: '/admin/content/pages', permission: 'pages.manage' },
      { label: 'Add New Page', to: '/admin/content/pages/new', permission: 'pages.manage' },
      { label: 'Page Categories', to: '/admin/content/page-categories', permission: 'pages.manage' },
      { label: 'Team Members', to: '/admin/content/team-members', permission: 'homepage.manage' },
      { label: 'Company Timeline', to: '/admin/content/timeline-items', permission: 'homepage.manage' },
    ],
  },
  {
    label: 'Destinations', icon: ShieldCheck, children: [
      { label: 'All Destinations', to: '/admin/content/destinations', permission: 'destinations.manage' },
      { label: 'Add Destination', to: '/admin/content/destinations/new', permission: 'destinations.manage' },
      { label: 'Destination Categories', to: '/admin/content/destination-categories', permission: 'destinations.manage' },
      { label: 'FAQs', to: '/admin/content/faqs?category=Destinations', permission: 'faqs.manage' },
    ],
  },
  {
    label: 'Universities', icon: Landmark, children: [
      { label: 'All Universities', to: '/admin/content/universities', permission: 'universities.manage' },
      { label: 'Add University', to: '/admin/content/universities/new', permission: 'universities.manage' },
      { label: 'Featured Universities', to: '/admin/content/universities?is_featured=1', permission: 'universities.manage' },
      { label: 'University FAQs', to: '/admin/content/faqs?category=Universities', permission: 'faqs.manage' },
    ],
  },
  {
    label: 'Courses', icon: Building2, children: [
      { label: 'All Courses', to: '/admin/content/courses', permission: 'courses.manage' },
      { label: 'Add Course', to: '/admin/content/courses/new', permission: 'courses.manage' },
      { label: 'Degree Types', to: '/admin/content/degree-types', permission: 'courses.manage' },
      { label: 'Study Levels', to: '/admin/study-levels', permission: 'courses.manage' },
      { label: 'Intakes', to: '/admin/content/intakes', permission: 'courses.manage' },
    ],
  },
  {
    label: 'Services', icon: Settings, children: [
      { label: 'All Services', to: '/admin/content/services', permission: 'services.manage' },
      { label: 'Add Service', to: '/admin/content/services/new', permission: 'services.manage' },
      { label: 'Service FAQs', to: '/admin/content/faqs?category=Services', permission: 'faqs.manage' },
    ],
  },
  {
    label: 'Success Stories', icon: ThumbsUp, children: [
      { label: 'All Stories', to: '/admin/content/success-stories', permission: 'success_stories.manage' },
      { label: 'Add Success Story', to: '/admin/content/success-stories/new', permission: 'success_stories.manage' },
      { label: 'Featured Stories', to: '/admin/content/success-stories?is_featured=1', permission: 'success_stories.manage' },
      { label: 'Categories', to: '/admin/content/story-categories', permission: 'success_stories.manage' },
    ],
  },
  {
    label: 'Gallery', icon: Images, children: [
      {
        label: 'Photo Gallery', children: [
          { label: 'All Photos', to: '/admin/content/gallery-albums', permission: 'gallery.manage' },
          { label: 'Add Photos', to: '/admin/content/gallery-albums/new', permission: 'gallery.manage' },
          { label: 'Photo Categories', to: '/admin/content/gallery-categories?type=photo', permission: 'gallery.manage' },
        ],
      },
      {
        label: 'Video Gallery', children: [
          { label: 'All Videos', to: '/admin/content/gallery-videos', permission: 'gallery.manage' },
          { label: 'Add YouTube Video', to: '/admin/content/gallery-videos/new', permission: 'gallery.manage' },
          { label: 'Video Categories', to: '/admin/content/gallery-categories?type=video', permission: 'gallery.manage' },
        ],
      },
    ],
  },
  {
    label: 'Blog / Notice', icon: Newspaper, children: [
      { label: 'All Posts', to: '/admin/content/blogs', permission: 'blogs.manage' },
      { label: 'Add New Post', to: '/admin/content/blogs/new', permission: 'blogs.manage' },
      { label: 'Categories', to: '/admin/content/blog-categories', permission: 'blogs.manage' },
      { label: 'Tags', to: '/admin/content/blog-tags', permission: 'blogs.manage' },
      ...status('/admin/content/blogs?state=', 'blogs.manage', [['Drafts', 'draft'], ['Scheduled', 'scheduled'], ['Published', 'published']]),
    ],
  },
  {
    label: 'Partners', icon: BookOpenText, children: [
      { label: 'All Partners', to: '/admin/content/partners', permission: 'partners.manage' },
      { label: 'Add Partner', to: '/admin/content/partners/new', permission: 'partners.manage' },
      { label: 'Partner Categories', to: '/admin/content/partner-categories', permission: 'partners.manage' },
      { label: 'Featured Partners', to: '/admin/content/partners?is_featured=1', permission: 'partners.manage' },
    ],
  },
  {
    label: 'Testimonials', icon: MessageCircle, children: [
      { label: 'All Testimonials', to: '/admin/content/testimonials', permission: 'testimonials.manage' },
      { label: 'Add Testimonial', to: '/admin/content/testimonials/new', permission: 'testimonials.manage' },
      { label: 'Featured Testimonials', to: '/admin/content/testimonials?is_featured=1', permission: 'testimonials.manage' },
      { label: 'Pending Testimonials', to: '/admin/content/testimonials?status=draft', permission: 'testimonials.manage' },
    ],
  },
  {
    label: 'FAQs', icon: CircleHelp, children: [
      { label: 'All FAQs', to: '/admin/content/faqs', permission: 'faqs.manage' },
      { label: 'Add FAQ', to: '/admin/content/faqs/new', permission: 'faqs.manage' },
      { label: 'FAQ Categories', to: '/admin/content/faq-categories', permission: 'faqs.manage' },
    ],
  },
  {
    label: 'Appointments', icon: CalendarDays, children: [
      { label: 'All Appointments', to: '/admin/appointments?view=all', permission: 'appointments.view' },
      ...status('/admin/appointments?view=all&status=', 'appointments.view', [
        ['Pending', 'pending'], ['Confirmed', 'confirmed'], ['Rescheduled', 'rescheduled'], ['Completed', 'completed'], ['Cancelled', 'cancelled'], ['No Show', 'no_show'],
      ]),
      { label: 'Appointment Settings', to: '/admin/settings/appointment', permission: 'settings.manage' },
    ],
  },
  {
    label: 'Counsellors', icon: Users, children: [
      { label: 'All Counsellors', to: '/admin/content/counsellors', permission: 'counsellors.manage' },
      { label: 'Add Counsellor', to: '/admin/content/counsellors/new', permission: 'counsellors.manage' },
      { label: 'Availability', to: '/admin/counsellors/availability', permission: 'counsellors.manage' },
      { label: 'Counsellor Settings', to: '/admin/settings/appointment#setting-appointment_duration', permission: 'settings.manage' },
    ],
  },
  {
    label: 'Leads', icon: ChartNoAxesCombined, children: [
      { label: 'All Leads', to: '/admin/leads', permission: 'leads.manage' },
      ...status('/admin/leads?status=', 'leads.manage', [
        ['New', 'new'], ['Contacted', 'contacted'], ['Interested', 'interested'], ['Follow Up', 'follow_up'], ['Converted', 'converted'], ['Closed', 'closed'],
      ]),
      { label: 'Lead Sources', to: '/admin/leads/sources', permission: 'leads.manage' },
    ],
  },
  {
    label: 'Applications', icon: UserCheck, children: [
      { label: 'All Applications', to: '/admin/applications', permission: 'applications.manage' },
      ...status('/admin/applications?status=', 'applications.manage', [
        ['New', 'new'], ['Contacted', 'contacted'], ['Documents Pending', 'documents_pending'], ['Under Review', 'under_review'],
        ['Submitted', 'submitted'], ['Processing', 'processing'], ['Completed', 'completed'], ['Rejected', 'rejected'],
      ]),
      { label: 'Application Settings', to: '/admin/settings/forms', permission: 'settings.manage' },
    ],
  },
  {
    label: 'Contact Messages', icon: Mail, children: [
      { label: 'All Messages', to: '/admin/messages', permission: 'contacts.manage' },
      ...status('/admin/messages?status=', 'contacts.manage', [['Unread', 'new'], ['Read', 'read'], ['Replied', 'replied'], ['Archived', 'archived']]),
      { label: 'Newsletter Subscribers', to: '/admin/newsletter', permission: 'newsletter.manage' },
    ],
  },
  {
    label: 'Media Library', icon: FolderOpen, children: [
      { label: 'All Media', to: '/admin/media', permission: 'media.manage' },
      { label: 'Images', to: '/admin/media?type=image', permission: 'media.manage' },
      { label: 'Documents', to: '/admin/media?type=document', permission: 'media.manage' },
      { label: 'Upload Media', to: '/admin/media#upload', permission: 'media.manage' },
    ],
  },
  {
    label: 'Menus', icon: LayoutList, children: [
      { label: 'Main Menu', to: '/admin/menus?location=header', permission: 'menus.manage' },
      { label: 'Footer Menu', to: '/admin/menus?location=footer_quick', permission: 'menus.manage' },
      { label: 'Menu Settings', to: '/admin/menus', permission: 'menus.manage' },
    ],
  },
  {
    label: 'Users & Roles', icon: UserRound, children: [
      { label: 'All Users', to: '/admin/users', permission: 'users.manage' },
      { label: 'Add User', to: '/admin/users?new=1', permission: 'users.manage' },
      { label: 'Roles', to: '/admin/roles', permission: 'roles.manage' },
      { label: 'Permissions', to: '/admin/roles#permissions', permission: 'roles.manage' },
      { label: 'User Activity', to: '/admin/activity-logs?group=users', permission: 'activity_logs.view' },
    ],
  },
  {
    label: 'Settings', icon: UserCog, children: [
      { label: 'General Settings', to: '/admin/settings/general', permission: 'settings.manage' },
      {
        label: 'Branding', children: [
          { label: 'Logo', to: '/admin/settings/general#setting-site_logo', permission: 'settings.manage' },
          { label: 'Favicon', to: '/admin/settings/general#setting-site_favicon', permission: 'settings.manage' },
          { label: 'Website Name', to: '/admin/settings/general#setting-site_name', permission: 'settings.manage' },
        ],
      },
      { label: 'Contact Information', to: '/admin/settings/contact', permission: 'settings.manage' },
      { label: 'Social Media', to: '/admin/settings/social', permission: 'settings.manage' },
      { label: 'Appearance', to: '/admin/settings/appearance', permission: 'settings.manage' },
      { label: 'SEO Settings', to: '/admin/settings/seo', permission: 'settings.manage' },
      { label: 'Appointment Settings', to: '/admin/settings/appointment', permission: 'settings.manage' },
      { label: 'Email / SMTP', to: '/admin/settings/email', permission: 'settings.manage' },
      { label: 'Email Templates', to: '/admin/content/email-templates', permission: 'settings.manage' },
      { label: 'Footer Settings', to: '/admin/settings/footer', permission: 'settings.manage' },
      { label: 'System Settings', to: '/admin/settings/system', permission: 'settings.manage' },
    ],
  },
  {
    label: 'Notifications', icon: Bell, children: [
      { label: 'All Notifications', to: '/admin/notifications' },
      { label: 'Unread', to: '/admin/notifications?filter=unread' },
      { label: 'Notification Settings', to: '/admin/notifications/settings' },
    ],
  },
  {
    label: 'Activity Log', icon: Activity, children: [
      { label: 'All Activities', to: '/admin/activity-logs', permission: 'activity_logs.view' },
      ...status('/admin/activity-logs?group=', 'activity_logs.view', [['Login Activities', 'login'], ['Content Activities', 'content'], ['User Activities', 'users'], ['System Activities', 'system']]),
    ],
  },
  {
    label: 'Profile', icon: UserRound, children: [
      { label: 'My Profile', to: '/admin/profile' },
      { label: 'Change Password', to: '/admin/profile#password' },
      { label: 'Login Activity', to: '/admin/profile#logins' },
    ],
  },
  { label: 'Logout', icon: LogOut, action: 'logout' },
]
