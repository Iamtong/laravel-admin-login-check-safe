<?php

namespace Encore\LoginCheckSafe;

use Encore\Admin\Extension;

class LoginCheckSafe extends Extension
{
    public $name = 'login-check-safe';

    public $views = __DIR__.'/../resources/views';

    public $migrations = __DIR__.'/../database/migrations';

    public $menu = [
        'title' => 'Loginchecksafe',
        'path'  => 'login-check-safe',
        'icon'  => 'fa-gears',
    ];
}
