<?php

namespace App\Providers;

use App\Repositories\Contracts\CursoRepository;
use App\Repositories\Eloquent\EloquentCursoRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CursoRepository::class, EloquentCursoRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
