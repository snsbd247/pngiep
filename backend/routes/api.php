<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\DirectoryController;
use App\Http\Controllers\Api\V1\EnquiryController;
use App\Http\Controllers\Api\V1\HomepageController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Public website endpoints live at /api/v1/*.
| Admin endpoints live at /api/v1/admin/* and require a Sanctum session plus
| a per-route permission (Gate ability defined in config/permissions.php).
*/

Route::prefix('v1')->middleware('throttle:api')->group(function () {

    // ---------------------------------------------------------------- Public
    Route::get('site', [SiteController::class, 'show']);
    Route::get('settings', [SiteController::class, 'settings']);
    Route::get('menus', [SiteController::class, 'menus']);
    Route::get('homepage', HomepageController::class);
    Route::get('pages/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::get('faqs', [PageController::class, 'faqs']);
    Route::post('newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:public-forms');
    Route::post('enquiries', [EnquiryController::class, 'store'])->middleware('throttle:public-forms');
    Route::get('search', SearchController::class);

    $slug = '[a-z0-9-]+';
    Route::get('destinations', [DirectoryController::class, 'destinations']);
    Route::get('destinations/{slug}', [DirectoryController::class, 'destination'])->where('slug', $slug);
    Route::get('universities', [DirectoryController::class, 'universities']);
    Route::get('universities/{slug}', [DirectoryController::class, 'university'])->where('slug', $slug);
    Route::get('courses', [DirectoryController::class, 'courses']);
    Route::get('courses/{slug}', [DirectoryController::class, 'course'])->where('slug', $slug);
    Route::get('services', [DirectoryController::class, 'services']);
    Route::get('services/{slug}', [DirectoryController::class, 'service'])->where('slug', $slug);

    Route::get('success-stories', [ContentController::class, 'stories']);
    Route::get('success-stories/{slug}', [ContentController::class, 'story'])->where('slug', $slug);
    Route::post('success-stories', [ContentController::class, 'submitStory'])->middleware('throttle:public-forms');
    Route::get('gallery/photos', [ContentController::class, 'photos']);
    Route::get('gallery/albums/{slug}', [ContentController::class, 'album'])->where('slug', $slug);
    Route::get('gallery/videos', [ContentController::class, 'videos']);
    Route::get('blogs', [ContentController::class, 'blogs']);
    Route::get('blogs/{slug}', [ContentController::class, 'blog'])->where('slug', $slug);

    // Appointments, contact, applications (Phase 5)
    Route::get('appointments/options', [AppointmentController::class, 'options']);
    Route::get('appointments/availability', [AppointmentController::class, 'availability']);
    Route::get('appointments/slots', [AppointmentController::class, 'slots']);
    Route::get('appointments/manage/{token}', [AppointmentController::class, 'show']);
    Route::middleware('throttle:public-forms')->group(function () {
        Route::post('appointments', [AppointmentController::class, 'store']);
        Route::post('appointments/manage/{token}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('appointments/manage/{token}/reschedule', [AppointmentController::class, 'reschedule']);
        Route::post('contact', [InquiryController::class, 'contact']);
        Route::post('applications', [InquiryController::class, 'apply']);
    });

    // ----------------------------------------------------------------- Admin
    Route::prefix('admin')->group(function () {
        Route::get('auth/session', [Admin\AuthController::class, 'session']);

        Route::middleware('throttle:login')->group(function () {
            Route::post('auth/login', [Admin\AuthController::class, 'login']);
            Route::post('auth/forgot-password', [Admin\AuthController::class, 'forgotPassword']);
            Route::post('auth/reset-password', [Admin\AuthController::class, 'resetPassword']);
        });

        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::post('auth/logout', [Admin\AuthController::class, 'logout']);
            Route::get('auth/me', [Admin\AuthController::class, 'me']);
            Route::put('profile', [Admin\ProfileController::class, 'update']);
            Route::get('profile/logins', [Admin\ProfileController::class, 'logins']);
            Route::get('meta', Admin\MetaController::class);
            Route::get('meta/sections', [Admin\MetaController::class, 'sections']);
            Route::get('lookups', Admin\LookupController::class);

            Route::get('dashboard', Admin\DashboardController::class)->middleware('can:dashboard.view');
            Route::get('dashboard/report', [Admin\DashboardController::class, 'report'])->middleware('can:dashboard.view');
            Route::get('search', Admin\SearchController::class)->middleware('throttle:60,1');
            Route::get('notifications/summary', [Admin\NotificationController::class, 'summary']);
            Route::get('notifications/preferences', [Admin\NotificationController::class, 'preferences']);
            Route::put('notifications/preferences', [Admin\NotificationController::class, 'updatePreferences']);
            Route::post('notifications/read-all', [Admin\NotificationController::class, 'markAllRead']);
            Route::get('notifications', [Admin\NotificationController::class, 'index']);
            Route::post('notifications/{id}/read', [Admin\NotificationController::class, 'markRead'])->whereUuid('id');
            Route::delete('notifications/{id}', [Admin\NotificationController::class, 'destroy'])->whereUuid('id');

            Route::middleware('can:settings.manage')->group(function () {
                Route::get('settings', [Admin\SettingController::class, 'index']);
                Route::put('settings', [Admin\SettingController::class, 'update']);
                Route::post('settings/test-mail', [Admin\SettingController::class, 'testMail'])->middleware('throttle:login');
            });

            Route::middleware('can:media.manage')->group(function () {
                Route::delete('media/bulk', [Admin\MediaController::class, 'bulkDestroy']);
                Route::apiResource('media', Admin\MediaController::class)->except('show');
            });

            Route::middleware('can:users.manage')->group(function () {
                Route::get('roles/options', [Admin\RoleController::class, 'options']);
                Route::apiResource('users', Admin\UserController::class);
            });

            Route::middleware('can:roles.manage')->group(function () {
                Route::get('roles', [Admin\RoleController::class, 'index']);
                Route::put('roles/{role}', [Admin\RoleController::class, 'update']);
            });

            // ------------------------------------------------ CMS (Phase 2)
            Route::middleware('can:homepage.manage')->group(function () {
                Route::crud('homepage-sections', Admin\Cms\HomepageSectionController::class);
                Route::crud('hero-slides', Admin\Cms\HeroSlideController::class);
                Route::crud('statistics', Admin\Cms\StatisticController::class);
                Route::crud('team-members', Admin\Cms\TeamMemberController::class);
                Route::crud('timeline-items', Admin\Cms\TimelineItemController::class);
            });
            Route::middleware('can:menus.manage')->group(function () {
                Route::get('menus', [Admin\Cms\MenuController::class, 'menus']);
                Route::put('menus/{menu}', [Admin\Cms\MenuController::class, 'updateMenu']);
                Route::crud('menu-items', Admin\Cms\MenuController::class);
            });
            Route::middleware('can:pages.manage')->group(function () {
                Route::crud('pages', Admin\Cms\PageController::class);
                Route::crud('page-categories', Admin\Terms\PageCategoryController::class);
            });
            Route::middleware('can:services.manage')->group(fn () => Route::crud('services', Admin\Cms\ServiceController::class));
            Route::middleware('can:partners.manage')->group(function () {
                Route::crud('partners', Admin\Cms\PartnerController::class);
                Route::crud('partner-categories', Admin\Terms\PartnerCategoryController::class);
            });
            Route::middleware('can:testimonials.manage')->group(fn () => Route::crud('testimonials', Admin\Cms\TestimonialController::class));
            Route::middleware('can:faqs.manage')->group(function () {
                Route::crud('faqs', Admin\Cms\FaqController::class);
                Route::crud('faq-categories', Admin\Terms\FaqCategoryController::class);
            });

            // ------------------------------------------------ Directory (Phase 3)
            Route::middleware('can:destinations.manage')->group(function () {
                Route::crud('destinations', Admin\Directory\DestinationController::class);
                Route::crud('destination-categories', Admin\Terms\DestinationCategoryController::class);
            });
            Route::middleware('can:universities.manage')->group(fn () => Route::crud('universities', Admin\Directory\UniversityController::class));
            Route::middleware('can:courses.manage')->group(function () {
                Route::crud('courses', Admin\Directory\CourseController::class);
                Route::crud('degree-types', Admin\Terms\DegreeTypeController::class);
                Route::crud('intakes', Admin\Terms\IntakeController::class);
            });

            // ------------------------------------------------ Content (Phase 4)
            Route::middleware('can:success_stories.manage')->group(function () {
                Route::crud('success-stories', Admin\Content\SuccessStoryController::class);
                Route::crud('story-categories', Admin\Terms\StoryCategoryController::class);
            });
            Route::middleware('can:gallery.manage')->group(function () {
                Route::crud('gallery-categories', Admin\Content\GalleryCategoryController::class);
                Route::crud('gallery-albums', Admin\Content\GalleryAlbumController::class);
                Route::crud('gallery-videos', Admin\Content\GalleryVideoController::class);
            });
            Route::middleware('can:blogs.manage')->group(function () {
                Route::crud('blog-categories', Admin\Content\BlogCategoryController::class);
                Route::crud('blog-tags', Admin\Content\BlogTagController::class);
                Route::crud('blogs', Admin\Content\BlogController::class);
            });

            // ------------------------------------------------ Business (Phase 5)
            Route::middleware('can:counsellors.manage')->group(function () {
                Route::get('counsellors/availability', [Admin\Crm\CounsellorController::class, 'availability']);
                Route::crud('counsellors', Admin\Crm\CounsellorController::class);
            });
            Route::middleware('can:appointments.view')->group(function () {
                Route::get('appointments', [Admin\Crm\AppointmentController::class, 'index']);
                Route::get('appointments/slots', [Admin\Crm\AppointmentController::class, 'slots']);
                Route::get('appointments/{id}', [Admin\Crm\AppointmentController::class, 'show'])->whereNumber('id');
                Route::put('appointments/{id}/status', [Admin\Crm\AppointmentController::class, 'updateStatus'])->whereNumber('id');
                Route::post('appointments/{id}/cancel', [Admin\Crm\AppointmentController::class, 'cancel'])->whereNumber('id');
                Route::post('appointments/{id}/reschedule', [Admin\Crm\AppointmentController::class, 'reschedule'])->whereNumber('id');
                Route::post('appointments/{id}/notes', [Admin\Crm\AppointmentController::class, 'addNote'])->whereNumber('id');
            });
            Route::middleware('can:leads.manage')->group(function () {
                Route::get('leads', [Admin\Crm\LeadController::class, 'index']);
                Route::get('leads/sources', [Admin\Crm\LeadController::class, 'sources']);
                Route::get('leads/{id}', [Admin\Crm\LeadController::class, 'show'])->whereNumber('id');
                Route::put('leads/{id}', [Admin\Crm\LeadController::class, 'update'])->whereNumber('id');
                Route::delete('leads/{id}', [Admin\Crm\LeadController::class, 'destroy'])->whereNumber('id');
                Route::post('leads/{id}/notes', [Admin\Crm\LeadController::class, 'addNote'])->whereNumber('id');
            });
            Route::middleware('can:applications.manage')->group(function () {
                Route::get('applications', [Admin\Crm\ApplicationController::class, 'index']);
                Route::get('applications/{id}', [Admin\Crm\ApplicationController::class, 'show'])->whereNumber('id');
                Route::put('applications/{id}', [Admin\Crm\ApplicationController::class, 'update'])->whereNumber('id');
                Route::delete('applications/{id}', [Admin\Crm\ApplicationController::class, 'destroy'])->whereNumber('id');
                Route::post('applications/{id}/notes', [Admin\Crm\ApplicationController::class, 'addNote'])->whereNumber('id');
                Route::get('applications/{id}/documents/{documentId}', [Admin\Crm\ApplicationController::class, 'download'])->whereNumber(['id', 'documentId']);
            });
            Route::middleware('can:contacts.manage')->group(function () {
                Route::get('messages', [Admin\Crm\ContactMessageController::class, 'index']);
                Route::get('messages/{id}', [Admin\Crm\ContactMessageController::class, 'show'])->whereNumber('id');
                Route::put('messages/{id}', [Admin\Crm\ContactMessageController::class, 'update'])->whereNumber('id');
                Route::delete('messages/{id}', [Admin\Crm\ContactMessageController::class, 'destroy'])->whereNumber('id');
                Route::post('messages/{id}/reply', [Admin\Crm\ContactMessageController::class, 'reply'])->whereNumber('id')->middleware('throttle:30,1');
            });
            Route::middleware('can:newsletter.manage')->group(function () {
                Route::get('newsletter', [Admin\Crm\NewsletterController::class, 'index']);
                Route::get('newsletter/export', [Admin\Crm\NewsletterController::class, 'export']);
                Route::delete('newsletter/{id}', [Admin\Crm\NewsletterController::class, 'destroy'])->whereNumber('id');
            });
            Route::middleware('can:settings.manage')->group(fn () => Route::crud('email-templates', Admin\Crm\EmailTemplateController::class));

            Route::get('activity-logs', [Admin\ActivityLogController::class, 'index'])->middleware('can:activity_logs.view');

            // System settings: info and maintenance actions; backup files are Super Admin only.
            Route::middleware('can:settings.manage')->group(function () {
                Route::get('system', [Admin\SystemController::class, 'info']);
                Route::post('system/cache-clear', [Admin\SystemController::class, 'clearCache'])->middleware('throttle:10,1');
                Route::post('system/backups', [Admin\SystemController::class, 'backup'])->middleware('throttle:5,1');
                Route::get('system/backups', [Admin\SystemController::class, 'backups']);
                Route::get('system/backups/{file}', [Admin\SystemController::class, 'download'])->where('file', '[A-Za-z0-9_.-]+');
            });
        });
    });
});
