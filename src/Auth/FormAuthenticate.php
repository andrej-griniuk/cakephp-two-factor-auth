<?php
namespace TwoFactorAuth\Auth;

use Cake\Auth\FormAuthenticate as CakeFormAuthenticate;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Exception;
use TwoFactorAuth\Controller\Component\AuthComponent;

/**
 * TwoFactorAuth Authenticate
 */
class FormAuthenticate extends CakeFormAuthenticate
{
    /**
     * Constructor
     *
     * @param ComponentRegistry $registry The Component registry used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_defaultConfig['fields']['secret'] = 'secret';

        parent::__construct($registry, $config);
    }

    /**
     * Get user's credentials (username and password) from either session or request data
     *
     * @param Request $request Request instance
     * @return array|bool
     */
    protected function _getCredentials(Request $request)
    {
        $credentials = [];
        foreach (['username', 'password'] as $field) {
            if (!$credentials[$field] = $request->data($this->_config['fields'][$field])) {
                $credentials[$field] = $request->session()->read('TwoFactorAuth.' . $field);
            }

            if (!$credentials[$field]) {
                return false;
            }
        }

        return $credentials;
    }

    /**
     * Verify one-time code
     *
     * @param string $secret user's secret
     * @param string $code one-time code
     * @param Response $response response instance
     * @var AuthComponent $Auth used Auth component
     * @return bool
     * @throws Exception
     */
    protected function _verifyCode($secret, $code, Response $response)
    {
        $Auth = $this->_registry->getController()->Auth;
        if (!($Auth instanceof AuthComponent)) {
            throw new Exception('TwoFactorAuth.Auth component has to be used for authentication.');
        }

        $verifyAction = Router::url($Auth->config('verifyAction'), true);

        if ($code === null) {
            $response->location($verifyAction);

            return false;
        }

        if (!$Auth->verifyCode($secret, $code)) {
            $Auth->flash(__d('TwoFactorAuth', 'Invalid two-step verification code.'));
            $response->location($verifyAction);

            return false;
        }

        return true;
    }

    /**
     * Authenticates the identity contained in a request. Will use the `config.userModel`, and `config.fields`
     * to find POST data that is used to find a matching record in the `config.userModel`. Will return false if
     * there is no post data, either username or password is missing, or if the scope conditions have not been met.
     *
     * @param Request $request The request that contains login information.
     * @param Response $response Unused response object.
     * @return mixed False on login failure.  An array of User data on success.
     */
    public function authenticate(Request $request, Response $response)
    {
        if (!$credentials = $this->_getCredentials($request)) {
            return false;
        }

        if (!$user = $this->_findUser($credentials['username'], $credentials['password'])) {
            return false;
        }

        if ($secret = Hash::get($user, $this->config('fields.secret'))) {
            $request->session()->write('TwoFactorAuth', $credentials);
            if (!$this->_verifyCode($secret, $request->data('code'), $response)) {
                return false;
            }

            unset($user[$this->config('fields.secret')]);
            $request->session()->delete('TwoFactorAuth');
        }

        return $user;
    }
}
