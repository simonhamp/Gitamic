# Gitamic
Are you running [Statamic](https://statamic.com) Free version and want git support, but can't justify going Pro?

Or maybe you're running Statamic Pro already, and you need more control over when, what and how changes get pushed back
to your repo?

**Gitamic** adds an intuitive git UI to Statamic so that you can have more control over your commits.

It's great for solo sites where you want more granular control of your git history, but really shines on more complex
sites that rely on live-publishing whilst pushing changes from your Statamic CP back to your git repository.

## Features
- View the 'working tree'
- Stage and unstage changes
- Discard unstaged changes
- Commit staged changes
- Push to & pull from the remote branch

And even more features are coming as Gitamic is under active development!

**Pro version COMING SOON** with...

- Diffs
- Commit history
- Stashing
- Restoring

## Requirements

- PHP 7.4+
- Statamic 3+ (running on Laravel 7+)
- git 2.30+

## Installation and Setup
### Statamic CP
Using the Statamic CP is the easiest way to install Gitamic. Under **Tools > Addons**, use the search to find 'Gitamic'
and install. All done.

### Composer
You can also install via Composer:

```bash
composer require simonhamp/gitamic
```

Once Composer has finished downloading the necessary dependencies, you'll need to update your application's
`config/statamic/editions.php` to indicate that you're using the `basic` edition of Gitamic:

```php
    'addons' => [
        'simonhamp/gitamic' => 'basic',
    ],
```

Once Gitamic is installed, just refresh your CP and you can use it immediately. Even if your site hasn't been
initialised with a git repository, you can use Gitamic to get started!

#### Setting the git committer

> You can edit all of these settings and more in **Gitamic > Settings**!

By default, Gitamic uses the following details for the git committer name and email address:

- name: `Gitamic`
- email: `gitamic@example.com`

You can easily override these values in your `.env` by adding the following keys, e.g.:

```dotenv
GITAMIC_GIT_USER_NAME="Simon Hamp"
GITAMIC_GIT_USER_EMAIL="simon.hamp@me.com"
```

> If you're using Statamic Pro's Git Integration and would prefer Gitamic to use the same set of user details,
> simply set these values to `null` in you `config/gitamic.php` or empty in your Gitamic Settings, and it
> will use `STATAMIC_GIT_USER_NAME` and `STATAMIC_GIT_USER_EMAIL` instead, if those are defined.

If you'd like to use the details of the logged-in Statamic user as the committer, you will need to enable the
`gitamic.use_authenticated` option in `config/gitamic.php`:

```php
return [
    'use_authenticated' => true,

    'user' => ...
];
```

You will need to publish this config file to your site in order to make that change (this should already be done as part
of the add-on's installation):

```bash
php artisan vendor:publish --provider=SimonHamp\\Gitamic\\ServiceProvider
```

#### Statamic Pro
Gitamic **does not** require Statamic Pro, nor [Statamic's Git Integration](https://statamic.dev/git-integration) to be
enabled.

If however you do have Statamic Pro and you have Statamic's Git Integration enabled, you may find it better to _disable_
its 'automatic commits' feature so that Statamic doesn't automatically commit every change. This will leave you to do
all your own commits via Gitamic, if that's what you're into.

To do this you can set the following key in your `.env` file:

```dotenv
STATAMIC_GIT_AUTOMATIC=false
```

#### Auto-deployment

If you're using Gitamic on your auto-deployment target, when you `push` it might trigger a redundant deployment back to
the environment where you've just committed those changes.

In your deployment script, you will need to write a statement that exits the deployment when it detects that Gitamic
initiated the commit.

For example, if you use [Laravel Forge](https://forge.laravel.com/) and leave the `GITAMIC_GIT_USER_NAME` as `Gitamic`,
you could add the following to the beginning of your deploy script, which inspects the author of the commit and stops
the process before it begins:

```bash
[[ "$FORGE_DEPLOY_AUTHOR" == "Gitamic" ]] && echo "Commit by $FORGE_DEPLOY_AUTHOR" && exit 0

# The rest of your deployment script...
```

However, the specific approach you should use will depend largely on your setup.

## Bugs and Feature Requests
If you experience any problems with Gitamic or would like to make a feature request, please
[raise an issue](https://github.com/simonhamp/Gitamic/issues) using the appropriate template.

You can also find me (@simonhamp) in the `#3rd-party` channel on the [Statamic Discord](https://statamic.com/discord).
(Note that I will likely still ask you to fill out a GitHub issue).

## Security
If you discover any security related issues, please **do not** raise an issue on GitHub. Email simon.hamp@me.com
instead.

Please note that I will not respond to feature requests or bug reports at this email address.

See [SECURITY](https://github.com/simonhamp/Gitamic/blob/main/SECURITY.md) for more details.

## License
Gitamic is a premium add-on, so a license is required for all versions. You may download and install it for free to
trial it, but you must purchase a license code in order to use this add-on in production.

You can purchase a license code from the [Statamic Marketplace](https://statamic.com/addons/simonhamp/gitamic).

See [LICENSE](https://github.com/simonhamp/Gitamic/blob/main/LICENSE.md).
