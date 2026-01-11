<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CmsBlockRepository;
use App\Services\CmsService;
use App\Services\IssService;
use App\Services\JwstService;
use App\Support\JwstHelper;

/**
 * Application Service Provider
 * Регистрирует все зависимости для Dependency Injection
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository регистрация
        $this->app->singleton(CmsBlockRepository::class, function ($app) {
            return new CmsBlockRepository();
        });

        // Service регистрация
        $this->app->singleton(CmsService::class, function ($app) {
            return new CmsService(
                $app->make(CmsBlockRepository::class)
            );
        });

        $this->app->singleton(IssService::class, function ($app) {
            $rustBase = getenv('RUST_BASE') ?: 'http://rust_iss:3000';
            return new IssService($rustBase);
        });

        // JWST Helper и Service
        $this->app->bind(JwstHelper::class, function ($app) {
            return new JwstHelper();
        });

        $this->app->singleton(JwstService::class, function ($app) {
            return new JwstService(
                $app->make(JwstHelper::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

