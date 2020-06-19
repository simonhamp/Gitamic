<?php

namespace SimonHamp\Gitamic;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js'
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];
}
