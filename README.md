[![Build Status](https://img.shields.io/travis/andrej-griniuk/cakephp-two-factor-auth/master.svg?style=flat-square)](https://travis-ci.org/andrej-griniuk/cakephp-two-factor-auth)
[![codecov](https://codecov.io/gh/andrej-griniuk/cakephp-two-factor-auth/branch/master/graph/badge.svg)](https://codecov.io/gh/andrej-griniuk/cakephp-two-factor-auth)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

# TwoFactorAuth plugin for CakePHP

This plugin provides two factor authentication functionality using [RobThree/TwoFactorAuth](https://github.com/RobThree/TwoFactorAuth) library.
Basically, it works similar way CakePHP `FormAuthenticate` does. After submitting correct username/password, if the user has `secret` field set, he will be asked to enter a one-time code.
**Attention:** it only provides authenticate provider and component and does not take care of users signup, management etc.

## Requirements

- CakePHP 4.0+ (use ***^1.3*** version for CakePHP <3.7, ***^2.0*** version for CakePHP <4.0)

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

Second, you need to load the plugin in your Application.php

```php
$this->addPlugin('TwoFactorAuth');
```

Alternatively, execute the following line:

```bash
bin/cake plugin load TwoFactorAuth
```

You can see the default config values [here](https://github.com/andrej-griniuk/cakephp-two-factor-auth/blob/master/src/Authenticator/TwoFactorFormAuthenticator.php) and find out what do they mean [here](https://github.com/RobThree/TwoFactorAuth#usage). To overwrite them, pass them as `TwoFactorForm` authenticator values.

Then you need to set up authentication in your Application.php as you would [normally do it](https://book.cakephp.org/authentication/2/en/index.html#getting-started), but using `TwoFactorForm` authenticator instead of `Form`, e.g.:

```php
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        $this->addPlugin('TwoFactorAuth');
        $this->addPlugin('Authentication');
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Various other middlewares for error handling, routing etc. added here.

        // Create an authentication middleware object
        $authentication = new AuthenticationMiddleware($this);

        // Add the middleware to the middleware queue.
        // Authentication should be added *after* RoutingMiddleware.
        // So that subdirectory information and routes are loaded.
        $middlewareQueue->add($authentication);

        return $middlewareQueue;
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $service->setConfig([
            'unauthenticatedRedirect' => '/users/login',
            'queryParam' => 'redirect',
        ]);

        $fields = [
            'username' => 'username',
            'password' => 'password'
        ];

        // Load the authenticators, you want session first
        $service->loadAuthenticator('Authentication.Session');
        $service->loadAuthenticator('TwoFactorAuth.TwoFactorForm', [
            'fields' => $fields,
            'loginUrl' => '/users/login'
        ]);

        // Load identifiers
        $service->loadIdentifier('Authentication.Password', compact('fields'));

        return $service;
    }
}
```

Next, in your AppController load the `Authentication` and `TwoFactorAuth` components:

```php
// in src/Controller/AppController.php
public function initialize()
{
    parent::initialize();

    $this->loadComponent('Authentication.Authentication');
    $this->loadComponent('TwoFactorAuth.TwoFactorAuth');
}
```

Once you have the middleware applied to your application you’ll need a way for users to login. A simplistic `UsersController` would look like:

```php
class UsersController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login', 'verify']);
    }

    /**
     * Login method
     *
     * @return \Cake\Http\Response|null
     */
    public function login()
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            // If the user is logged in send them away.
            $target = $this->Authentication->getLoginRedirect() ?? '/home';

            return $this->redirect($target);
        }

        if ($this->request->is('post') && !$result->isValid()) {
            if ($result->getStatus() == \TwoFactorAuth\Authenticator\Result::TWO_FACTOR_AUTH_FAILED) {
                $this->Flash->error('Invalid 2FA code');

                return $this->redirect(['action' => 'verify']);
            } elseif ($result->getStatus() == \TwoFactorAuth\Authenticator\Result::TWO_FACTOR_AUTH_REQUIRED) {
                return $this->redirect(['action' => 'verify']);
            } else {
                $this->Flash->error('Invalid username or password');
            }
        }
    }

    public function logout()
    {
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }

    public function verify()
    {
        // This action is only needed to render a vew with one time code form
    }
}
```

And `verify.php` would look like:

```html
<div class="users form content">
    <?= $this->Form->create(null, ['url' => '/users/login']) ?>
    <fieldset>
        <legend><?= __('Please enter your 2FA code') ?></legend>
        <?= $this->Form->control('code') ?>
    </fieldset>
    <?= $this->Form->button(__('Continue')); ?>
    <?= $this->Form->end() ?>
</div>
```

Basically, it works same way CakePHP `Authentication.Form` authenticator does.
After entering correct username/password combination, if the user has `secret` field (can be overwritten via `TwoFactorAuth.TwoFactorForm` configuration) set he will be redirected to the `verify` action where he is asked to enter a one-time code.
There is no logic behind this action, it only renders the form that has to be submitted to the `loginAction` again with `code` field set.

You can access the [RobThree\Auth\TwoFactorAuth](https://github.com/RobThree/TwoFactorAuth) instance from your controller via `$this->TwoFactorAuth->getTfa()` or call some of the methods directly on `TwoFactorAuth` component. For example, you can generate user's secret and get QR code data URI for it this way:
```php
$secret = $this->TwoFactorAuth->createSecret();
$secretDataUri = $this->TwoFactorAuth->getQRCodeImageAsDataUri('CakePHP:user@email.com', $secret);
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

Copyright (c) 2020, [Andrej Griniuk][andrej-griniuk] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[andrej-griniuk]:https://github.com/andrej-griniuk
