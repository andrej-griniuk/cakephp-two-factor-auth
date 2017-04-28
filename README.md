[![Build Status](https://img.shields.io/travis/andrej-griniuk/cakephp-two-factor-auth/master.svg?style=flat-square)](https://travis-ci.org/andrej-griniuk/cakephp-two-factor-auth)
[![Coverage Status](https://img.shields.io/coveralls/andrej-griniuk/cakephp-two-factor-auth.svg?style=flat-square)](https://coveralls.io/r/andrej-griniuk/cakephp-two-factor-auth?branch=master)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

# TwoFactorAuth plugin for CakePHP

This plugin provides two factor authentication functionality using [RobThree/TwoFactorAuth](https://github.com/RobThree/TwoFactorAuth) library.
Basically, it works similar way CakePHP `FormAuthenticate` does. After submitting correct username/password, if the user has `secret` field set, he will be asked to enter a one-time code.
**Attention:** it only provides authenticate provider and component and does not take care of users signup, management etc.

## Requirements

- CakePHP 3.0+

## Installation

You can install this plugin into your CakePHP application using [Composer][composer].

```bash
composer require andrej-griniuk/cakephp-two-factor-auth
```

## Usage

First of all you need to add `secret` field to your users table (field name can be changed to `TwoFactorAuth.Form` authenticator configuration).
```sql
ALTER TABLE `users` ADD `secret` VARCHAR(255) NULL;
```

Second, you need to load the plugin in your bootstrap.php

```php
Plugin::load('TwoFactorAuth', ['bootstrap' => true, 'routes' => true]);
```

You can see the default config values [here](https://github.com/andrej-griniuk/cakephp-two-factor-auth/blob/master/config/two_factor_auth.php) and find out what do they mean [here](https://github.com/RobThree/TwoFactorAuth#usage). To overwrite them, create two_factor_auth.php file in your `config` directory.

Then you need to set up authentication in your controller as you would normally do, but using `TwoFactorAuth.Auth` component and `TwoFactorAuth.Form` authenticator, e.g.:

```php
class AppController extends Controller
{
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Security');
        $this->loadComponent('Csrf');
        $this->loadComponent('TwoFactorAuth.Auth', [
            'authenticate' => [
                'TwoFactorAuth.Form' => [
                    'fields' => [
                        'username' => 'username',
                        'password' => 'password',
                        'secret' => 'secret', // database field
                        'remember' => 'remember' // checkbox form field name for "Trust this device" feature
                    ],
                    'remember' => true, // enable "Trust this device" feature
                    'cookie' => [ // cookie settings for "Trust this device" feature
                        'name' => 'TwoFactorAuth',
                        'httpOnly' => true,
                        'expires' => '+30 days'
                    ],
                    'verifyAction' => [
                        'prefix' => false,
                        'controller' => 'TwoFactorAuth',
                        'action' => 'verify',
                        'plugin' => 'TwoFactorAuth'
                    ],
                ],
            ],
        ]);
    }
}
```

Basically, it works same way CakePHP `Form` authenticator does.
After entering correct username/password combination, if the user has `secret` field (can be overwritten via `TwoFactorAuth.Form` configuration) set he will be redirected to `verifyAction` (by default `['controller' => 'TwoFactorAuth', 'action' => 'verify', 'plugin' => 'TwoFactorAuth', 'prefix' => false]`) where he is asked to enter a one-time code.
There is no logic behind the action, it only renders the form that has to be submitted to the `loginAction` again with `code` field set.
You can override the view using standard CakePHP conventions to [override Plugin views](http://book.cakephp.org/3.0/en/plugins.html#overriding-plugin-templates-from-inside-your-application) or change the `verifyAction` in `TwoFactorAuth` configuration.

You can access the [RobThree\Auth\TwoFactorAuth](https://github.com/RobThree/TwoFactorAuth) instance from your controller via `$this->Auth->tfa`. For example, you can generate user's secret and get QR code data URI for it this way:
```php
$secret = $this->Auth->tfa->createSecret();
$secretDataUri = $this->Auth->tfa->getQRCodeImageAsDataUri('Andrej Griniuk', $secret);
```
Then display it in your view:
```php
<img src="<?= $secretDataUri ?>" />
```
See the library page for full documentation: https://github.com/RobThree/TwoFactorAuth

## Bugs & Feedback

https://github.com/andrej-griniuk/cakephp-two-factor-auth/issues

## Credits

https://github.com/RobThree/TwoFactorAuth

## License

Copyright (c) 2016, [Andrej Griniuk][andrej-griniuk] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[andrej-griniuk]:https://github.com/andrej-griniuk
