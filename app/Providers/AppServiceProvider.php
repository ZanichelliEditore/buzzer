<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\ChannelController;
use App\Http\Repositories\ChannelRepository;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\SubscriberController;
use App\Http\Repositories\PublisherRepository;
use App\Http\Repositories\RepositoryInterface;
use App\Http\Repositories\SubscriberRepository;
use App\Http\Controllers\ChannelPublishController;
use App\Http\Repositories\ChannelPublishRepository;
use App\Http\Controllers\ChannelSubscribeController;
use App\Http\Repositories\ChannelSubscribeRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(ChannelController::class)
            ->needs(RepositoryInterface::class)
            ->give(ChannelRepository::class);
        $this->app->when(SubscriberController::class)
            ->needs(RepositoryInterface::class)
            ->give(SubscriberRepository::class);
        $this->app->when(PublisherController::class)
            ->needs(RepositoryInterface::class)
            ->give(PublisherRepository::class);
        $this->app->when(ChannelPublishController::class)
            ->needs(RepositoryInterface::class)
            ->give(ChannelPublishRepository::class);
        $this->app->when(ChannelSubscribeController::class)
            ->needs(RepositoryInterface::class)
            ->give(ChannelSubscribeRepository::class);
    }
}
