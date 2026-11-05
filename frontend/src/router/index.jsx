import { lazy } from 'react'
import { createBrowserRouter } from 'react-router-dom'
import PublicLayout from '@/components/layout/PublicLayout'
import { AuthRoot } from '@/context/AuthContext'
import { APP_BASE } from '@/lib/base'
import ErrorPage from '@/pages/ErrorPage'
import { GuestOnly, RequireAuth, RequirePermission } from './guards'

// Every page is code-split; the admin panel never loads for website visitors.
const HomePage = lazy(() => import('@/pages/public/HomePage'))
const NotFoundPage = lazy(() => import('@/pages/public/NotFoundPage'))
const CmsPage = lazy(() => import('@/pages/public/CmsPage'))
const SlugPage = lazy(() => import('@/pages/public/SlugPage'))
const SearchPage = lazy(() => import('@/pages/public/SearchPage'))
const DestinationsPage = lazy(() => import('@/pages/public/directory/DestinationsPage'))
const UniversitiesPage = lazy(() => import('@/pages/public/directory/UniversitiesPage'))
const UniversityDetailPage = lazy(() => import('@/pages/public/directory/UniversityDetailPage'))
const CoursesPage = lazy(() => import('@/pages/public/directory/CoursesPage'))
const CourseDetailPage = lazy(() => import('@/pages/public/directory/CourseDetailPage'))
const ServicesPage = lazy(() => import('@/pages/public/directory/ServicesPage'))
const ServiceDetailPage = lazy(() => import('@/pages/public/directory/ServiceDetailPage'))
const SuccessStoriesPage = lazy(() => import('@/pages/public/content/SuccessStoriesPage'))
const SuccessStoryDetailPage = lazy(() => import('@/pages/public/content/SuccessStoryDetailPage'))
const GalleryPage = lazy(() => import('@/pages/public/content/GalleryPage'))
const VideoGalleryPage = lazy(() => import('@/pages/public/content/VideoGalleryPage'))
const BlogPage = lazy(() => import('@/pages/public/content/BlogPage'))
const BlogDetailPage = lazy(() => import('@/pages/public/content/BlogDetailPage'))
const BookAppointmentPage = lazy(() => import('@/pages/public/business/BookAppointmentPage'))
const ManageAppointmentPage = lazy(() => import('@/pages/public/business/ManageAppointmentPage'))
const ContactPage = lazy(() => import('@/pages/public/business/ContactPage'))
const ApplyPage = lazy(() => import('@/pages/public/business/ApplyPage'))

const AdminLayout = lazy(() => import('@/components/admin/AdminLayout'))
const LoginPage = lazy(() => import('@/pages/admin/auth/LoginPage'))
const ForgotPasswordPage = lazy(() => import('@/pages/admin/auth/ForgotPasswordPage'))
const ResetPasswordPage = lazy(() => import('@/pages/admin/auth/ResetPasswordPage'))
const DashboardPage = lazy(() => import('@/pages/admin/DashboardPage'))
const SettingsPage = lazy(() => import('@/pages/admin/SettingsPage'))
const MediaLibraryPage = lazy(() => import('@/pages/admin/MediaLibraryPage'))
const UsersPage = lazy(() => import('@/pages/admin/UsersPage'))
const RolesPage = lazy(() => import('@/pages/admin/RolesPage'))
const ActivityLogPage = lazy(() => import('@/pages/admin/ActivityLogPage'))
const ProfilePage = lazy(() => import('@/pages/admin/ProfilePage'))
const HomepageSectionsPage = lazy(() => import('@/pages/admin/content/HomepageSectionsPage'))
const MenusPage = lazy(() => import('@/pages/admin/content/MenusPage'))
const ResourceListPage = lazy(() => import('@/pages/admin/content/ResourceListPage'))
const ResourceFormPage = lazy(() => import('@/pages/admin/content/ResourceFormPage'))
const AppointmentsPage = lazy(() => import('@/pages/admin/crm/AppointmentsPage'))
const AppointmentDetailPage = lazy(() => import('@/pages/admin/crm/AppointmentDetailPage'))
const LeadsPage = lazy(() => import('@/pages/admin/crm/LeadsPage'))
const LeadDetailPage = lazy(() => import('@/pages/admin/crm/LeadDetailPage'))
const ApplicationsPage = lazy(() => import('@/pages/admin/crm/ApplicationsPage'))
const ApplicationDetailPage = lazy(() => import('@/pages/admin/crm/ApplicationDetailPage'))
const MessagesPage = lazy(() => import('@/pages/admin/crm/MessagesPage'))
const MessageDetailPage = lazy(() => import('@/pages/admin/crm/MessageDetailPage'))
const NewsletterPage = lazy(() => import('@/pages/admin/crm/NewsletterPage'))
const LeadSourcesPage = lazy(() => import('@/pages/admin/crm/LeadSourcesPage'))
const CounsellorAvailabilityPage = lazy(() => import('@/pages/admin/crm/CounsellorAvailabilityPage'))
const StudyLevelsPage = lazy(() => import('@/pages/admin/directory/StudyLevelsPage'))
const NotificationsPage = lazy(() => import('@/pages/admin/NotificationsPage'))
const NotificationSettingsPage = lazy(() => import('@/pages/admin/NotificationsPage').then((m) => ({ default: m.NotificationSettingsPage })))
const SystemSettingsPage = lazy(() => import('@/pages/admin/SystemSettingsPage'))

const guarded = (permission, element) => <RequirePermission permission={permission}>{element}</RequirePermission>

export const router = createBrowserRouter([
  {
    errorElement: <ErrorPage />,
    children: [
      {
        element: <PublicLayout />,
        children: [
          { index: true, element: <HomePage /> },
          { path: 'about', element: <CmsPage slug="about" /> },
          { path: 'destinations', element: <DestinationsPage /> },
          { path: 'universities', element: <UniversitiesPage /> },
          { path: 'universities/:slug', element: <UniversityDetailPage /> },
          { path: 'courses', element: <CoursesPage /> },
          { path: 'courses/:slug', element: <CourseDetailPage /> },
          { path: 'services', element: <ServicesPage /> },
          { path: 'services/:slug', element: <ServiceDetailPage /> },
          { path: 'success-stories', element: <SuccessStoriesPage /> },
          { path: 'success-stories/:slug', element: <SuccessStoryDetailPage /> },
          { path: 'gallery', element: <GalleryPage /> },
          { path: 'gallery/photos', element: <GalleryPage /> },
          { path: 'gallery/videos', element: <VideoGalleryPage /> },
          { path: 'blog', element: <BlogPage /> },
          { path: 'blog/:slug', element: <BlogDetailPage /> },
          { path: 'search', element: <SearchPage /> },
          { path: 'book-appointment', element: <BookAppointmentPage /> },
          { path: 'appointment/:token', element: <ManageAppointmentPage /> },
          { path: 'contact', element: <ContactPage /> },
          { path: 'apply', element: <ApplyPage /> },
          // /study-in-{destination} or an admin-created CMS page. Specific routes above win.
          { path: ':slug', element: <SlugPage /> },
          { path: '*', element: <NotFoundPage /> },
        ],
      },
      {
        path: 'admin',
        element: <AuthRoot />,
        children: [
          {
            element: <GuestOnly />,
            children: [
              { path: 'login', element: <LoginPage /> },
              { path: 'forgot-password', element: <ForgotPasswordPage /> },
              { path: 'reset-password', element: <ResetPasswordPage /> },
            ],
          },
          {
            element: <RequireAuth />,
            children: [
              {
                element: <AdminLayout />,
                children: [
                  { index: true, element: guarded('dashboard.view', <DashboardPage />) },
                  { path: 'settings/system', element: guarded('settings.manage', <SystemSettingsPage />) },
                  { path: 'settings/:group?', element: guarded('settings.manage', <SettingsPage />) },
                  { path: 'media', element: guarded('media.manage', <MediaLibraryPage />) },
                  { path: 'users', element: guarded('users.manage', <UsersPage />) },
                  { path: 'roles', element: guarded('roles.manage', <RolesPage />) },
                  { path: 'activity-logs', element: guarded('activity_logs.view', <ActivityLogPage />) },
                  { path: 'profile', element: <ProfilePage /> },
                  { path: 'notifications', element: <NotificationsPage /> },
                  { path: 'notifications/settings', element: <NotificationSettingsPage /> },
                  { path: 'counsellors/availability', element: guarded('counsellors.manage', <CounsellorAvailabilityPage />) },
                  { path: 'study-levels', element: guarded('courses.manage', <StudyLevelsPage />) },
                  { path: 'homepage', element: guarded('homepage.manage', <HomepageSectionsPage />) },
                  { path: 'menus', element: guarded('menus.manage', <MenusPage />) },
                  { path: 'appointments', element: guarded('appointments.view', <AppointmentsPage />) },
                  { path: 'appointments/:id', element: guarded('appointments.view', <AppointmentDetailPage />) },
                  { path: 'leads/sources', element: guarded('leads.manage', <LeadSourcesPage />) },
                  { path: 'leads', element: guarded('leads.manage', <LeadsPage />) },
                  { path: 'leads/:id', element: guarded('leads.manage', <LeadDetailPage />) },
                  { path: 'applications', element: guarded('applications.manage', <ApplicationsPage />) },
                  { path: 'applications/:id', element: guarded('applications.manage', <ApplicationDetailPage />) },
                  { path: 'messages', element: guarded('contacts.manage', <MessagesPage />) },
                  { path: 'messages/:id', element: guarded('contacts.manage', <MessageDetailPage />) },
                  { path: 'newsletter', element: guarded('newsletter.manage', <NewsletterPage />) },
                  // Generic content modules (config/resources.js); permission checked per resource.
                  { path: 'content/:resource', element: <ResourceListPage /> },
                  { path: 'content/:resource/:id', element: <ResourceFormPage /> },
                  { path: '*', element: <NotFoundPage /> },
                ],
              },
            ],
          },
        ],
      },
    ],
  },
], { basename: APP_BASE || '/' })
