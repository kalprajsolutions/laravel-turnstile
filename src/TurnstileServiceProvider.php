<?php

namespace KalprajSolutions\LaravelTurnstile;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use KalprajSolutions\LaravelTurnstile\Rules\ValidCloudflareTurnstile;
use KalprajSolutions\LaravelTurnstile\Turnstile;

class TurnstileServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-turnstile');

        // Register the Blade component
        Blade::component('laravel-turnstile::components.turnstile', 'turnstile');

        // Alternative: Auto-register all components in the directory
        // $this->registerComponents();

        $this->publishes([
            __DIR__ . '/../resources/views/components' => resource_path('views/vendor/laravel-turnstile/components'),
        ], 'views');

        \Illuminate\Support\Facades\Validator::extend('turnstile', function ($attribute, $value, $parameters, $validator) {
            // Instantiate your class and manually call it
            $rule = new ValidCloudflareTurnstile();

            // ValidationRule classes use a different signature than extend(), 
            // so we check if it fails or not.
            $passes = true;
            $rule->validate($attribute, $value, function ($message) use (&$passes) {
                $passes = false;
            });

            return $passes;
        }, 'Invalid CAPTCHA. You need to prove you are human.');
    }

    public function register(): void
    {
        $this->app->singleton('turnstile', function () {
            return new Turnstile();
        });
    }

    /**
     * Optional: Auto-register all components from the components directory
     */
    protected function registerComponents(): void
    {
        $componentsPath = __DIR__ . '/../resources/views/components';

        if (!is_dir($componentsPath)) {
            return;
        }

        foreach (glob($componentsPath . '/*.blade.php') as $file) {
            $name = basename($file, '.blade.php');
            Blade::component('laravel-turnstile::components.' . $name, 'turnstile-' . $name);
        }
    }
}
