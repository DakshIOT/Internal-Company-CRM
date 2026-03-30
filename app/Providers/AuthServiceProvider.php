<?php

namespace App\Providers;

use App\Models\Package;
use App\Models\Service;
use App\Models\User;
use App\Models\Venue;
use App\Models\FunctionEntry;
use App\Policies\FunctionEntryPolicy;
use App\Policies\PackagePolicy;
use App\Policies\ServicePolicy;
use App\Policies\UserPolicy;
use App\Policies\VenuePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        FunctionEntry::class => FunctionEntryPolicy::class,
        Package::class => PackagePolicy::class,
        Service::class => ServicePolicy::class,
        User::class => UserPolicy::class,
        Venue::class => VenuePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
