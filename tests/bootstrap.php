<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for TwoFactorAuth.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use Authentication\Plugin as AuthPlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\Fixture\SchemaLoader;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require_once $root . '/vendor/autoload.php';

/**
 * Define fallback values for required constants and configuration.
 * To customize constants and configuration remove this require
 * and define the data required by your plugin here.
 */
require_once $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';

if (file_exists($root . '/config/bootstrap.php')) {
    include $root . '/config/bootstrap.php';

    return;
}

Configure::write(
    'App',
    [
    'namespace' => 'TwoFactorAuth',
    'paths' => [
        'plugins' => [ROOT . 'Plugin' . DS],
        'templates' => [ROOT . 'templates' . DS],
    ],
    'encoding' => 'UTF-8',
    ]
);

if (!getenv('db_dsn')) {
    putenv('db_dsn=sqlite:///:memory:');
}

Plugin::getCollection()->add(new AuthPlugin());

$_SERVER['PHP_SELF'] = '/';

$loader = new SchemaLoader();
$loader->loadInternalFile(__DIR__ . '/schema.php');
