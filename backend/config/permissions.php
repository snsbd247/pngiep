<?php

/*
|--------------------------------------------------------------------------
| Permissions & default roles
|--------------------------------------------------------------------------
| Every permission slug becomes a Gate ability (see AppServiceProvider), so routes
| can use ->middleware('can:destinations.manage'). The seeder syncs these into the
| permissions / role_permissions tables; role permissions can then be edited by a
| Super Admin from the admin panel. Super Admin bypasses all checks.
*/

return [
    'modules' => [
        'dashboard' => ['dashboard.view' => 'View dashboard'],
        'settings' => ['settings.manage' => 'Manage settings'],
        'users' => [
            'users.manage' => 'Manage users',
            'roles.manage' => 'Manage roles & permissions',
        ],
        'activity_logs' => ['activity_logs.view' => 'View activity log'],
        'media' => ['media.manage' => 'Manage media library'],
        'menus' => ['menus.manage' => 'Manage menus'],
        'pages' => ['pages.manage' => 'Manage pages'],
        'homepage' => ['homepage.manage' => 'Manage homepage & about content'],
        'destinations' => ['destinations.manage' => 'Manage destinations'],
        'universities' => ['universities.manage' => 'Manage universities'],
        'courses' => ['courses.manage' => 'Manage courses'],
        'services' => ['services.manage' => 'Manage services'],
        'success_stories' => ['success_stories.manage' => 'Manage success stories'],
        'testimonials' => ['testimonials.manage' => 'Manage testimonials'],
        'partners' => ['partners.manage' => 'Manage partners'],
        'faqs' => ['faqs.manage' => 'Manage FAQs'],
        'gallery' => ['gallery.manage' => 'Manage photo & video gallery'],
        'blogs' => ['blogs.manage' => 'Manage blog & notices'],
        'counsellors' => ['counsellors.manage' => 'Manage counsellors & availability'],
        'appointments' => [
            'appointments.view' => 'View appointments',
            'appointments.manage' => 'Manage all appointments',
        ],
        'leads' => ['leads.manage' => 'Manage leads'],
        'applications' => ['applications.manage' => 'Manage applications'],
        'contacts' => ['contacts.manage' => 'Manage contact messages'],
        'newsletter' => ['newsletter.manage' => 'Manage newsletter subscribers'],
    ],

    // Default role → permission mapping used by the seeder. "*" = every permission.
    'roles' => [
        'super-admin' => ['name' => 'Super Admin', 'description' => 'Full, unrestricted access.', 'permissions' => ['*']],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Manages all content and business data, except roles.',
            'permissions' => ['*', '!roles.manage'],
        ],
        'content-manager' => [
            'name' => 'Content Manager',
            'description' => 'Manages website content. No settings, users or business data.',
            'permissions' => [
                'dashboard.view', 'media.manage', 'menus.manage', 'pages.manage', 'homepage.manage',
                'destinations.manage', 'universities.manage', 'courses.manage', 'services.manage',
                'success_stories.manage', 'testimonials.manage', 'partners.manage', 'faqs.manage',
                'gallery.manage', 'blogs.manage',
            ],
        ],
        'counsellor' => [
            'name' => 'Counsellor',
            'description' => 'Sees and manages only their own appointments.',
            'permissions' => ['dashboard.view', 'appointments.view'],
        ],
    ],
];
