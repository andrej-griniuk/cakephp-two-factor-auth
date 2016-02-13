<?php
use Cake\Routing\Router;

Router::extensions('json');
Router::scope('/', function (\Cake\Routing\RouteBuilder $routes) {
    $routes->connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);
    $routes->connect('/some_alias', ['controller' => 'tests_apps', 'action' => 'some_method']);
    $routes->fallbacks();
});
