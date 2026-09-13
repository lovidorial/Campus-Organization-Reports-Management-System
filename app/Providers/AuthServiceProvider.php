<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\ActivityReport;
use App\Models\ActivityRequest;
use App\Models\Gpoa;
use App\Models\GpoaActivity;
use App\Models\OrganizationMember;
use App\Models\UserNotification;
use App\Policies\ActivityPolicy;
use App\Policies\ActivityReportPolicy;
use App\Policies\ActivityRequestPolicy;
use App\Policies\GpoaActivityPolicy;
use App\Policies\GpoaPolicy;
use App\Policies\OrganizationMemberPolicy;
use App\Policies\UserNotificationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Activity::class => ActivityPolicy::class,
        ActivityRequest::class => ActivityRequestPolicy::class,
        ActivityReport::class => ActivityReportPolicy::class,
        Gpoa::class => GpoaPolicy::class,
        GpoaActivity::class => GpoaActivityPolicy::class,
        OrganizationMember::class => OrganizationMemberPolicy::class,
        UserNotification::class => UserNotificationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
