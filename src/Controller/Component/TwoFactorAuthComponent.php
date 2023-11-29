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
     * @param int $bits Number of bits
     * @param bool $requireCryptoSecure Require crypto secure
     * @return string
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function createSecret($bits = 80, $requireCryptoSecure = true)
    {
        return $this->getTfa()->createSecret($bits, $requireCryptoSecure);
    }

    /**
     * Get data-uri of QRCode
     *
     * @param string $label Label
     * @param string $secret Secret
     * @param int $size Size
     * @return string
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function getQRCodeImageAsDataUri($label, $secret, $size = 200)
    {
        return $this->getTfa()->getQRCodeImageAsDataUri($label, $secret, $size);
    }

    /**
     * Get RobThree\Auth\TwoFactorAuth object
     *
     * @return \RobThree\Auth\TwoFactorAuth
     * @throws \RobThree\Auth\TwoFactorAuthException
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
