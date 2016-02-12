<?php
namespace TwoFactorAuth\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use RobThree\Auth\TwoFactorAuth;

/**
 * TwoFactorAuth component
 *
 * @property TwoFactorAuth $tfa instance of RobThree\Auth\TwoFactorAuth
 */
class AuthComponent extends CakeAuthComponent
{
    public $tfa;

    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->tfa = new TwoFactorAuth('My Company');
    }

    /**
     * Sets defaults for configs.
     *
     * @return void
     */
    protected function _setDefaults()
    {
        parent::_setDefaults();

        if ($this->config('verifyAction') === null) {
            $this->config(
                'verifyAction',
                [
                    'controller' => 'TwoFactorAuth',
                    'action' => 'verify',
                    'plugin' => 'TwoFactorAuth',
                    'prefix' => false,
                ]
            );
        }
    }

    /**
     * Verify one-time code
     *
     * @param string $secret users's secret
     * @param string $code one-time code
     * @return bool
     */
    public function verifyCode($secret, $code)
    {
        return $this->tfa->verifyCode($secret, $code);
    }
}
