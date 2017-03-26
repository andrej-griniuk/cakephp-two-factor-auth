<?php
namespace TwoFactorAuth\Controller\Component;

use Cake\Controller\Component\AuthComponent as CakeAuthComponent;
use Cake\Core\Configure;
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

        $this->tfa = new TwoFactorAuth(
            Configure::read('TwoFactorAuth.issuer'),
            Configure::read('TwoFactorAuth.digits'),
            Configure::read('TwoFactorAuth.period'),
            Configure::read('TwoFactorAuth.algorithm'),
            Configure::read('TwoFactorAuth.qrcodeprovider'),
            Configure::read('TwoFactorAuth.rngprovider')
        );
    }

    /**
     * Sets defaults for configs.
     *
     * @return void
     */
    protected function _setDefaults()
    {
        parent::_setDefaults();

        if ($this->getConfig('verifyAction') === null) {
            $this->setConfig(
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
