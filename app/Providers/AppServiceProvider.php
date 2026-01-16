<?php

namespace App\Providers;

use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\Contracts\LlmProvider;
use App\Services\SkillsCoach\Providers\PrismProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CoachContext::class);

        $this->app->bind(LlmProvider::class, PrismProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
