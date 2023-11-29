<?php
declare(strict_types=1);

namespace TwoFactorAuth\Authenticator;

use ArrayAccess;
use Authentication\Authenticator\FormAuthenticator as CakeFormAuthenticator;
use Authentication\Authenticator\ResultInterface;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\UrlChecker\UrlCheckerTrait;
use Cake\Utility\Hash;
use Psr\Http\Message\ServerRequestInterface;
use RobThree\Auth\TwoFactorAuth;

/**
 * Two Factor Form Authenticator
 *
 * Authenticates an identity based on the POST data of the request.
 */
class TwoFactorFormAuthenticator extends CakeFormAuthenticator
{
    use UrlCheckerTrait;

    protected ?TwoFactorAuth $_tfa;

    /**
     * Default config for this object.
     * - `fields` The fields to use to identify a user by.
     * - `loginUrl` Login URL or an array of URLs.
     * - `urlChecker` Url checker config.
     * - `userSessionKey` Session key to store user after 1ss factor auth
     * - `secretProperty` User model property containing user's 2FA secret key
     * - `codeField` Request field containing one-time code
     * - `issuer` Will be displayed in the app as issuer name.
     * - `digits` The number of digits the resulting codes will be.
     * - `period` The number of seconds a code will be valid.
     * - `algorithm` The algorithm used.
     * - `qrcodeprovider` QR-code provider.
     * - `rngprovider` Random Number Generator provider.
     * - `timeprovider` Time provider.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'loginUrl' => null,
        'userSessionKey' => 'TwoFactorAuth.user',
        'urlChecker' => 'Authentication.Default',
        'fields' => [
            AbstractIdentifier::CREDENTIAL_USERNAME => 'username',
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
        ],
        'codeField' => 'code',
        'secretProperty' => 'secret',
        'isEnabled2faProperty' => 'secret',
        'issuer' => null,
        'digits' => 6,
        'period' => 30,
        'algorithm' => 'sha1',
        'qrcodeprovider' => null,
        'rngprovider' => null,
        'timeprovider' => null,
    ];

    /**
     * Authenticates the identity contained in a request. Will use the `config.userModel`, and `config.fields`
     * to find POST data that is used to find a matching record in the `config.userModel`. Will return false if
     * there is no post data, either username or password is missing, or if the scope conditions have not been met.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request that contains login information.
     * @return \Authentication\Authenticator\ResultInterface
     */
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        if (!$this->_checkUrl($request)) {
            return $this->_buildLoginUrlErrorResult($request);
        }

        $code = Hash::get($request->getParsedBody(), $this->getConfig('codeField'));
        if (!is_null($code)) {
            return $this->authenticateCode($request, $code);
        } else {
            return $this->authenticateCredentials($request);
        }
    }

    /**
     * 2nd factor authentication
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param string $code One-time code
     * @return \Authentication\Authenticator\ResultInterface
     */
    protected function authenticateCode(ServerRequestInterface $request, $code): ResultInterface
    {
        $user = $this->_getSessionUser($request);
        if (!$user) {
            // User hasn't passed 1st factor auth
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING);
        }

        if (!$this->_verifyCode($this->_getUserSecret($user), $code)) {
            // 2nd factor auth code is invalid
            return new Result(null, Result::TWO_FACTOR_AUTH_FAILED);
        }

        $this->_unsetSessionUser($request);

        return new Result($user, Result::SUCCESS);
    }

    /**
     * 1st factor authentication
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return \Authentication\Authenticator\ResultInterface
     */
    protected function authenticateCredentials(ServerRequestInterface $request): ResultInterface
    {
        $result = parent::authenticate($request);

        if (!$result->isValid() || !$this->_getUser2faEnabledStatus($result->getData()) || !$this->_getUserSecret($result->getData())) {
            // The user is invalid or the 2FA secret is not enabled/present
            return $result;
        }

        $user = $result->getData();

        // Store user authenticated with 1 factor
        $this->_setSessionUser($request, $user);

        return new Result(null, Result::TWO_FACTOR_AUTH_REQUIRED);
    }

    /**
     * Verify 2FA code
     *
     * @param string $secret Secret
     * @param string $code One-time code
     * @return bool
     */
    protected function _verifyCode($secret, $code): bool
    {
        try {
            return $this->getTfa()->verifyCode($secret, $code);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get pre-authenticated user from the session
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return array|null
     */
    protected function _getSessionUser(ServerRequestInterface $request)
    {
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');

        return $session->read($this->getConfig('userSessionKey'));
    }

    /**
     * Store pre-authenticated user in the session
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @param \ArrayAccess $user User
     */
    protected function _setSessionUser(ServerRequestInterface $request, ArrayAccess $user)
    {
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');
        $session->write($this->getConfig('userSessionKey'), $user);
    }

    /**
     * Clear pre-authenticated user from the session
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     */
    protected function _unsetSessionUser(ServerRequestInterface $request)
    {
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');

        $session->delete($this->getConfig('userSessionKey'));
    }

    /**
     * Get user's 2FA secret
     *
     * @param array $user User
     * @return string|null
     */
    protected function _getUserSecret($user)
    {
        return Hash::get($user, $this->getConfig('secretProperty'));
    }

    /**
     * Check if 2FA is enabled for the given user
     *
     * @param array $user User
     * @return bool
     */
    protected function _getUser2faEnabledStatus($user)
    {
        return (bool)Hash::get($user, $this->getConfig('isEnabled2faProperty'));
    }

    /**
     * Get RobThree\Auth\TwoFactorAuth object
     *
     * @return \RobThree\Auth\TwoFactorAuth
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function getTfa()
    {
        if (!$this->_tfa) {
            $this->_tfa = new TwoFactorAuth(
                $this->getConfig('issuer'),
                $this->getConfig('digits'),
                $this->getConfig('period'),
                $this->getConfig('algorithm'),
                $this->getConfig('qrcodeprovider'),
                $this->getConfig('rngprovider'),
                $this->getConfig('timeprovider')
            );
        }

        return $this->_tfa;
    }
}
