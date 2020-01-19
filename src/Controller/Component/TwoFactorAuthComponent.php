<?php
declare(strict_types=1);

namespace TwoFactorAuth\Controller\Component;

use Cake\Controller\Component;

/**
 * Authentication component
 */
class TwoFactorAuthComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];
    /**
     * Verify one-time code
     *
     * @param string $secret users's secret
     * @param string $code one-time code
     * @return bool
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function verifyCode($secret, $code)
    {
        return $this->getTfa()->verifyCode($secret, str_replace(' ', '', $code));
    }

    /**
     * Create 2FA secret
     *
     * @param int $bits
     * @param bool $requireCryptoSecure
     * @return string
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function createSecret($bits = 80, $requireCryptoSecure = true)
    {
        return $this->getTfa()->createSecret($bits, $requireCryptoSecure);
    }

    /**
     * Get RobThree\Auth\TwoFactorAuth object
     *
     * @return \RobThree\Auth\TwoFactorAuth
     * @throws \RobThree\Auth\TwoFactorAuthException
     * @throws \Exception
     */
    public function getTfa()
    {
        /** @var \Authentication\AuthenticationService $authenticationService */
        $authenticationService = $this->getController()->getRequest()->getAttribute('authentication');

        /** @var \TwoFactorAuth\Authenticator\TwoFactorFormAuthenticator $twoFactorFormAuthenticator */
        $twoFactorFormAuthenticator = $authenticationService->authenticators()->get('TwoFactorForm');

        return $twoFactorFormAuthenticator->getTfa();
    }
}
