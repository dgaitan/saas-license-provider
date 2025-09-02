<?php

namespace App\Providers;

use App\Repositories\ActivationRepository;
use App\Repositories\Interfaces\ActivationRepositoryInterface;
use App\Repositories\Interfaces\LicenseKeyRepositoryInterface;
use App\Repositories\Interfaces\LicenseRepositoryInterface;
use App\Repositories\LicenseKeyRepository;
use App\Repositories\LicenseRepository;
use App\Services\Api\V1\Product\Interfaces\LicenseStatusServiceInterface;
use App\Services\Api\V1\Product\LicenseStatusService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(LicenseKeyRepositoryInterface::class, LicenseKeyRepository::class);
        $this->app->bind(LicenseRepositoryInterface::class, LicenseRepository::class);
        $this->app->bind(ActivationRepositoryInterface::class, ActivationRepository::class);

        // Service bindings
        $this->app->bind(LicenseStatusServiceInterface::class, LicenseStatusService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
