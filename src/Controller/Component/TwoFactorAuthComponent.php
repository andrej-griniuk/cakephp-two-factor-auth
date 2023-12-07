<?php
declare(strict_types=1);

namespace TwoFactorAuth\Controller\Component;

use Cake\Controller\Component;
use RobThree\Auth\TwoFactorAuth;

/**
 * Authentication component
 */
class TwoFactorAuthComponent extends Component
{
    /**
     * Verify one-time code
     *
     * @param string $secret users's secret
     * @param string $code   one-time code
     * @return bool
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->getTfa()->verifyCode($secret, str_replace(' ', '', $code));
    }

    /**
     * Create 2FA secret
     *
     * @param int  $bits                Number of bits
     * @param bool $requireCryptoSecure Require crypto secure
     * @return string
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function createSecret(int $bits = 80, bool $requireCryptoSecure = true): string
    {
        return $this->getTfa()->createSecret($bits, $requireCryptoSecure);
    }

    /**
     * Get data-uri of QRCode
     *
     * @param string $label  Label
     * @param string $secret Secret
     * @param int    $size   Size
     * @return string
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function getQRCodeImageAsDataUri(string $label, string $secret, int $size = 200): string
    {
        return $this->getTfa()->getQRCodeImageAsDataUri($label, $secret, $size);
    }

    /**
     * Get RobThree\Auth\TwoFactorAuth object
     *
     * @return \RobThree\Auth\TwoFactorAuth
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function getTfa(): TwoFactorAuth
    {
        /** @var \Authentication\AuthenticationService $authenticationService */
        $authenticationService = $this->getController()->getRequest()->getAttribute('authentication');

        /** @var \TwoFactorAuth\Authenticator\TwoFactorFormAuthenticator $twoFactorFormAuthenticator */
        $twoFactorFormAuthenticator = $authenticationService->authenticators()->get('TwoFactorForm');

        return $twoFactorFormAuthenticator->getTfa();
    }
}
