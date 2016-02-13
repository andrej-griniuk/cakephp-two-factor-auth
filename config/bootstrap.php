<?php
use Cake\Core\Configure;

Configure::load('TwoFactorAuth.two_factor_auth');

if (file_exists(CONFIG . 'two_factor_auth.php')) {
    Configure::load('two_factor_auth');
}
