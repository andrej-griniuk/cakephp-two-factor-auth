<?php
namespace TwoFactorAuth\Test\App\Controller;

use Cake\Controller\Controller;
use Cake\Routing\Router;
use TwoFactorAuth\Controller\Component\AuthComponent;

/**
 * AuthTestController class
 */
class AuthTestController extends Controller
{
    /** @var  AuthComponent $Auth */
    public $Auth;

    /**
     * components property
     *
     * @var array
     */
    public $components = ['TwoFactorAuth.Auth'];

    /**
     * testUrl property
     *
     * @var mixed
     */
    public $testUrl = null;

    /**
     * login method
     *
     * @return void
     */
    public function login()
    {
    }

    /**
     * logout method
     *
     * @return void
     */
    public function logout()
    {
    }

    /**
     * add method
     *
     * @return void
     */
    public function add()
    {
        echo "add";
    }

    /**
     * view method
     *
     * @return void
     */
    public function view()
    {
        echo "view";
    }

    /**
     * add method
     *
     * @return void
     */
    public function camelCase()
    {
        echo "camelCase";
    }

    /**
     * redirect method
     *
     * @param mixed $url
     * @param mixed $status
     * @return void|\Cake\Network\Response
     */
    public function redirect($url, $status = null)
    {
        $this->testUrl = Router::url($url);
        return parent::redirect($url, $status);
    }

    /**
     * isAuthorized method
     *
     * @return void
     */
    public function isAuthorized()
    {
    }
}
