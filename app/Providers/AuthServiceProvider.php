<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        Passport::routes();
        Passport::personalAccessTokensExpireIn(Carbon::now()->addHours(24));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
        Passport::tokensCan([
            'add-new-sub-admin'=>'add-new-sub-admin',
            'add-new-categories'=>'add-new-categories',
            'add-new-subcategories'=>'add-new-subcategories',
            'add-new-products'=>'add-new-products',
            'user-block'=>'user-block',
            'edit-sub-admin'=>'edit-sub-admin',
            'edit-categories'=>'edit-categories',
            'edit-subcategories'=>'edit-subcategories',
            'edit-product'=>'edit-product',
            'edit-site'=>'edit-site',
            'remove-sub-admin'=>'remove-sub-admin',
            'remove-categories'=>'remove-categories',
            'remove-subcategories'=>'remove-subcategories',
            'remove-products'=>'remove-products',
            'basic'=>'user'
        ]);

        Passport::setDefaultScope([
            'basic',
        ]);
//
    }
}
