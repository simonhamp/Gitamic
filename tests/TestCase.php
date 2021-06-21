<?php

namespace SimonHamp\Gitamic\Tests;

use SimonHamp\Gitamic\ServiceProvider;
use Statamic\Providers\StatamicServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            StatamicServiceProvider::class,
        ];
    }
}
