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

        $this->app->bind(
            \App\Repositories\Favoris\FavoriRepositoryInterface::class,
            \App\Repositories\Favoris\FavoriRepository::class
        );

        $this->app->bind(
            \App\Repositories\Paniers\PanierRepositoryInterface::class,
            \App\Repositories\Paniers\PanierRepository::class
        );

        $this->app->bind(
            \App\Repositories\Configurations\ConfigurationRepositoryInterface::class,
            \App\Repositories\Configurations\ConfigurationRepository::class
        );

        $this->app->bind(
            \App\Repositories\Sales\DevisRepositoryInterface::class,
            \App\Repositories\Sales\DevisRepository::class
        );

        $this->app->bind(
            \App\Repositories\Sales\CommandeRepositoryInterface::class,
            \App\Repositories\Sales\CommandeRepository::class
        );

        $this->app->bind(
            \App\Repositories\Sales\FactureRepositoryInterface::class,
            \App\Repositories\Sales\FactureRepository::class
        );

        $this->app->bind(
            \App\Repositories\AvisClients\AvisClientRepositoryInterface::class,
            \App\Repositories\AvisClients\AvisClientRepository::class
        );

        $this->app->bind(
            \App\Repositories\Adresse\AdresseRepositoryInterface::class,
            \App\Repositories\Adresse\AdresseRepository::class
        );

        $this->app->bind(
            \App\Repositories\AdminNotification\AdminNotificationRepositoryInterface::class,
            \App\Repositories\AdminNotification\AdminNotificationRepository::class
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
