<?php

namespace App\Providers;

use App\Services\ApiService;
use App\Services\MasterApiService;
use App\Services\MenuService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MasterApiService::class, function ($app) {
            return new MasterApiService($app->make(ApiService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // View::composer('*', function ($view) {
        //     $menuService = new MenuService();
        //     $menus = $menuService->getSidebarMenu();
        //     $view->with('menus', $menus);
        // });
    }
}
