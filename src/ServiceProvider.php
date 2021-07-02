<?php

namespace SimonHamp\Gitamic;

use Edalzell\Forma\Forma;
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
            $use_authenticated = config('gitamic.use_authenticated');
            $user = User::current();

            $config_user = config('gitamic.user.name') ?? config('statamic.git.user.name', 'Gitamic');
            $config_email = config('gitamic.user.email') ?? config('statamic.git.user.email', 'gitamic@example.com');

            $name = $use_authenticated ? $user->name() : $config_user;
            $email = $use_authenticated ? $user->email() : $config_email;

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

        Forma::add('simonhamp/gitamic');

        Nav::extend(function ($nav) {
            $nav->tools('Gitamic')
                ->route('gitamic.status')
                ->icon('git')
                ->children([
                    'Settings' => cp_route('gitamic.config.edit'),
                ]);
        });
    }
}
