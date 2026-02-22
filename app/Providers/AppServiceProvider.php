<?php

namespace App\Providers;

use App\Services\DataService;
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
        $this->app->singleton(DataService::class, function ($app) {
            return new DataService();
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
