<?php

namespace SimonHamp\Gitamic;

use Statamic\Facades\User;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;
use Gitonomy\Git\Repository as GitRepository;
use SimonHamp\Gitamic\Contracts\SiteRepository;

class ServiceProvider extends AddonServiceProvider
{
    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js'
    ];

    protected $stylesheets = [
        __DIR__.'/../resources/dist/css/cp.css'
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    public function register()
    {
        app()->singleton(SiteRepository::class, function () {
            return new Repository(app(GitRepository::class));
        });

        app()->singleton(GitRepository::class, function () {
            $user = User::current();

            $authenticated = config('gitamic.use_authenticated');

            $name = $authenticated ? $user->name() : config('gitamic.user.name');
            $email = $authenticated ? $user->email() : config('gitamic.user.email');

            return new GitRepository(base_path(), [
                'environment_variables' => [
                    'GIT_AUTHOR_NAME' => $name,
                    'GIT_AUTHOR_EMAIL' => $email,
                    'GIT_COMMITTER_NAME' => $name,
                    'GIT_COMMITTER_EMAIL' => $email,
                ],
            ]);
        });
    }

    public function boot()
    {
        parent::boot();

        Nav::extend(function ($nav) {
            $nav->tools('Gitamic')
                ->route('gitamic.status')
                ->icon('git');
        });
    }
}
