<?php

namespace App\Providers;

use App\Models\CmsContent;
use App\Models\CmsPage;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Repositories\Eloquent\CmsContentRepository;
use App\Repositories\Eloquent\CmsPageRepository;
use App\Repositories\Eloquent\SubscriptionPlanRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\CmsContentRepositoryInterface;
use App\Repositories\Interfaces\CmsPageRepositoryInterface;
use App\Repositories\Interfaces\SubscriptionPlanRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind CMS Page Repository
        $this->app->bind(
            CmsPageRepositoryInterface::class,
            function ($app) {
                return new CmsPageRepository(new CmsPage());
            }
        );

        // Bind CMS Content Repository
        $this->app->bind(
            CmsContentRepositoryInterface::class,
            function ($app) {
                return new CmsContentRepository(new CmsContent());
            }
        );

        // Bind User Repository
        $this->app->bind(
            UserRepositoryInterface::class,
            function ($app) {
                return new UserRepository(new User());
            }
        );

        // Bind Subscription Plan Repository
        $this->app->bind(
            SubscriptionPlanRepositoryInterface::class,
            function ($app) {
                return new SubscriptionPlanRepository(new SubscriptionPlan());
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
