<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamically apply mail settings from database
        try {
            if (!app()->runningInConsole() || app()->runningUnitTests()) {
                \App\Services\MailConfigService::applyRuntimeConfig();
            }
        } catch (\Throwable $e) {}

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            static $sidebarCategories = null;
            
            if ($sidebarCategories === null) {
                $sidebarCategories = \Illuminate\Support\Facades\Cache::remember('sidebar_categories', 60 * 24, function () {
                    return \App\Models\Category::whereNull('parent_id')
                        ->where('is_active', true)
                        ->with(['children' => function ($query) {
                            $query->where('is_active', true)->orderBy('sort_order', 'asc');
                        }])
                        ->orderBy('sort_order', 'asc')
                        ->get();
                });
            }
            
            $view->with('sidebarCategories', $sidebarCategories);
        });
    }
}
