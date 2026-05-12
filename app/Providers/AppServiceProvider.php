<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\AdminRepositoryInterface::class,
            \App\Repositories\AdminRepository::class
        );

        $this->app->bind(
            \App\Repositories\Category\CategoryRepositoryInterface::class,
            \App\Repositories\Category\CategoryRepository::class
        );

        $this->app->bind(
            \App\Repositories\Produit\ProduitRepositoryInterface::class,
            \App\Repositories\Produit\ProduitRepository::class
        );

        $this->app->bind(
            \App\Repositories\ImageProduit\ImageProduitRepositoryInterface::class,
            \App\Repositories\ImageProduit\ImageProduitRepository::class
        );

        $this->app->bind(
            \App\Repositories\AttributProduit\AttributProduitRepositoryInterface::class,
            \App\Repositories\AttributProduit\AttributProduitRepository::class
        );

        $this->app->bind(
            \App\Repositories\UtilisateurRepositoryInterface::class,
            \App\Repositories\UtilisateurRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
