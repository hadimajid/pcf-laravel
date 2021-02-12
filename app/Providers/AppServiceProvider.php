<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('array_size', function($attribute, $value, $parameters, $validator){
            $p= Arr::get($validator->getData(), $parameters[0]);
            return count($value)==count($p);
        },'All fields are required!');
    }
}
