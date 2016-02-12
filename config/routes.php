<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'TwoFactorAuth',
    ['path' => '/two-factor-auth'],
    function (RouteBuilder $routes) {
        //$routes->connect('/', ['controller' => 'TwoFactorAuth', 'action' => 'login'], ['_name' => 'two-factor-auth']);

        $routes->fallbacks();
    }
);
