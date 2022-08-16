<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        // \DB::listen(function ($query) {
        //     Log::info('sql : '.$query->sql);
        //     Log::info('time : '.$query->time);
        //     Log::info('bindings : ');
        //     Log::info($query->bindings);
        // });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
