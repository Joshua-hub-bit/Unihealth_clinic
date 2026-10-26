<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register the 'layouts' view namespace so starter-kit Livewire Volt
        // components that reference layouts::app resolve correctly.
        View::addNamespace('layouts', resource_path('views/components/layouts'));
    }
}
