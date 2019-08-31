<?php

namespace Encore\LoginCheckSafe;

use Illuminate\Support\ServiceProvider;

class LoginCheckSafeServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(LoginCheckSafe $extension)
    {
        if (! LoginCheckSafe::boot()) {
            return ;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'login-check-safe');
        }

        /*if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [
                    $assets => public_path('vendor/laravel-admin-ext/login-check-safe')
                ],
                'login-check-safe'
            );
        }*/

        $this->publishes(
            [
                //$extension->assets() => public_path('vendor/laravel-admin-ext/login-check-safe'),
                //$extension->migrations() => database_path('migrations'),
                //__DIR__.'/../resources/lang/' => resource_path('lang'),
                __DIR__.'/../database/migrations/2019_08_12_090524_table_enabled_into_admin_user.php' => database_path('migrations/2019_08_12_090524_table_enabled_into_admin_user.php'),
                __DIR__.'/../resources/lang/en/validation.php' => resource_path('lang/en/validation.php'),
                __DIR__.'/../resources/lang/en/admins.php' => resource_path('lang/en/admins.php'),
                __DIR__.'/../resources/lang/zh-CN/admins.php' => resource_path('lang/zh-CN/admins.php'),
                __DIR__.'/../resources/lang/zh-CN/validation.php' => resource_path('lang/zh-CN/validation.php'),
                __DIR__.'/../resources/lang/zh-CN/auth.php' => resource_path('lang/zh-CN/auth.php'),
            ],
            'login-check-safe'
        );

        $this->app->booted(function () {
            LoginCheckSafe::routes(__DIR__.'/../routes/web.php');
        });
    }
}
