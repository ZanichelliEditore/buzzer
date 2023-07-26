<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('board', function ($user, $permission) {
            if (!in_array($permission, $user->permissions)) {
                Log::error('403* ' . '{"content" :"User with id  ' . $user->id . ' is not authorized (permission required:' . $permission . ')"}');
                return false;
            }
            return true;
        });

        Passport::routes();
        Passport::tokensExpireIn(now()->addMinutes(2));
        Passport::refreshTokensExpireIn(now()->addDays(30));
    }
}
