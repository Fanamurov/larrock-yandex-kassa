<?php

namespace Larrock\YandexKassa;

use Illuminate\Support\ServiceProvider;

class YandexKassaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/../views', 'larrock');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'larrock');

        $this->publishes([
            __DIR__.'/../views' => base_path('resources/views/vendor/larrock'),
        ], 'views');
        $this->publishes([
            __DIR__.'/../lang' => resource_path('lang/vendor/larrock'),
        ], 'lang');
        $this->publishes([
            __DIR__.'/../config/larrock-yandex-kassa.php' => config_path('larrock-yandex-kassa.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larrock-yandex-kassa.php', 'larrock-yandex-kassa');

        $this->app->singleton('yandexkassa', function () {
            $class = config('larrock.components.yandexkassa', YandexKassaComponent::class);

            return new $class;
        });
    }
}
