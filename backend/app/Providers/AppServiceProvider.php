<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Appointment;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Media;
use App\Models\Page;
use App\Models\Role;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SuccessStory;
use App\Models\University;
use App\Models\User;
use App\Services\SeoService;
use App\Services\SettingService;
use App\Support\Permissions;
use App\Support\SeoRoutes;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingService::class);
        $this->app->singleton(SeoService::class);
        $this->app->afterResolving(SeoService::class, fn (SeoService $seo) => SeoRoutes::register($seo));
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Short, stable morph names instead of PHP class names in the database.
        Relation::morphMap([
            'user' => User::class,
            'page' => Page::class,
            'destination' => Destination::class,
            'university' => University::class,
            'course' => Course::class,
            'service' => Service::class,
            'success_story' => SuccessStory::class,
            'blog' => Blog::class,
            'lead' => Lead::class,
            'appointment' => Appointment::class,
            'application' => Application::class,
            'contact_message' => ContactMessage::class,
            'setting' => Setting::class,
            'media' => Media::class,
            'role' => Role::class,
        ]);

        // Build public file URLs from the incoming host + base path, so uploads load whether the
        // site is opened via localhost, an IP address or a domain, at root or in a sub-folder.
        if (! $this->app->runningInConsole()) {
            config(['filesystems.disks.public.url' => url('storage')]);
        }

        $this->registerRouteMacros();
        $this->registerGates();
        $this->registerRateLimiters();
        $this->applyRuntimeSettings();

        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim(config('app.frontend_url'), '/')
            .'/admin/reset-password?token='.$token.'&email='.urlencode($user->email));
    }

    /**
     * Route::crud('partners', PartnerController::class) registers
     * index / store / show / update / destroy + POST {name}/reorder with {id} parameters.
     */
    private function registerRouteMacros(): void
    {
        Route::macro('crud', function (string $name, string $controller) {
            Route::post("{$name}/reorder", [$controller, 'reorder']);
            Route::get($name, [$controller, 'index']);
            Route::post($name, [$controller, 'store']);
            Route::get("{$name}/{id}", [$controller, 'show'])->whereNumber('id');
            Route::put("{$name}/{id}", [$controller, 'update'])->whereNumber('id');
            Route::delete("{$name}/{id}", [$controller, 'destroy'])->whereNumber('id');
        });
    }

    private function registerGates(): void
    {
        $permissions = array_keys(Permissions::all());

        // Super Admin passes every *permission* check, but model policies still run,
        // so rules like "you cannot delete your own account" apply to everyone.
        Gate::before(fn (User $user, string $ability) => $user->isSuperAdmin() && in_array($ability, $permissions, true) ? true : null);

        foreach ($permissions as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }

    private function registerRateLimiters(): void
    {
        // Generous for reads: public data is cached, and mobile carriers put many visitors behind one IP (CGNAT).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(600)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        // Public form submissions (contact, appointment, application, newsletter).
        RateLimiter::for('public-forms', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perHour(60)->by($request->ip()),
        ]);
    }

    /** Override mail config with SMTP details managed in Admin Settings > Email. */
    private function applyRuntimeSettings(): void
    {
        if (! SettingService::tableExists()) {
            return;
        }

        $settings = $this->app->make(SettingService::class);

        if ($host = $settings->get('mail_host')) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => (int) $settings->get('mail_port', 587),
                'mail.mailers.smtp.username' => $settings->get('mail_username'),
                'mail.mailers.smtp.password' => $settings->get('mail_password'),
                'mail.mailers.smtp.scheme' => $settings->get('mail_encryption') === 'ssl' ? 'smtps' : null,
            ]);
        }

        if ($from = $settings->get('mail_from_address')) {
            config(['mail.from.address' => $from]);
        }
        config(['mail.from.name' => $settings->get('mail_from_name', $settings->get('site_name', config('app.name')))]);
    }
}
