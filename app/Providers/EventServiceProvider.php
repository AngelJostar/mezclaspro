<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        $this->app->singleton(\App\Services\StockReorderMonitor::class);
        Event::listen(\Illuminate\Database\Events\QueryExecuted::class,
            fn ($query) => app(\App\Services\StockReorderMonitor::class)->record($query));
        $this->app->terminating(fn () => app(\App\Services\StockReorderMonitor::class)->flush());
        Event::listen(\Illuminate\Queue\Events\JobProcessed::class,
            fn () => app(\App\Services\StockReorderMonitor::class)->flush());
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
