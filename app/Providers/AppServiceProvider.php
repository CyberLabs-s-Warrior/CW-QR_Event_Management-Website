<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Logs\UsersLogService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('userLogsService', function ($app) {
            return new UsersLogService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure for Cloudflare tunnel
        if (request()->header('CF-Visitor')) {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }

        // Trust Cloudflare proxies
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        // super_admin
        Gate::define('isSuperAdmin', fn(User $user) => $user->role === 'super_admin');

        // super_admin pertama (system owner)
        Gate::define('isSystemOwner', function(User $user) {
            $selectedOption = request()->input('role');
            return $user->role === 'super_admin' && $user->id === 1 && $selectedOption === 'super_admin';
        });

        // admin
        Gate::define('isAdmin', fn(User $user) => $user->role === 'admin');

        // super_admin + admin
        Gate::define(
            'isSuperOrAdmin',
            fn(User $user) =>
            in_array($user->role, ['super_admin', 'admin'])
        );
    }
}
