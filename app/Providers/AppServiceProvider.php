<?php

namespace App\Providers;

use App\Ai\Stores\ProjectConversationStore;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\ConversationStore;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(ConversationStore::class, ProjectConversationStore::class);
    }
}
