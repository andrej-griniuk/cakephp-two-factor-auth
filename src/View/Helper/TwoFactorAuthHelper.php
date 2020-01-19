<?php
declare(strict_types=1);

namespace TwoFactorAuth\View\Helper;

use Cake\View\Helper;

/**
 * TwoFactorAuth helper
 */
class TwoFactorAuthHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

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
        $authenticationService = $this->getView()->getRequest()->getAttribute('authentication');

        /** @var \TwoFactorAuth\Authenticator\TwoFactorFormAuthenticator $twoFactorFormAuthenticator */
        $twoFactorFormAuthenticator = $authenticationService->authenticators()->get('TwoFactorForm');

        return $twoFactorFormAuthenticator->getTfa();
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
     * Builds a string to be encoded in a QR code
     *
     * @param string $label Label
     * @param string $secret Secret
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function getQRText($label, $secret)
    {
        $this->getTfa()->getQRText($label, $secret);
    }
}
