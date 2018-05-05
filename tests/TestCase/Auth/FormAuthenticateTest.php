<?php
namespace TwoFactorAuth\Test\TestCase\Auth;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use TwoFactorAuth\Auth\FormAuthenticate;
use TwoFactorAuth\Test\App\Controller\AuthTestController;

/**
 * TwoFactorAuth\Controller\Component\FormAuthenticate Test Case
 *
 * @property \TwoFactorAuth\Auth\FormAuthenticate $auth;
 * @property \Cake\Controller\ComponentRegistry $ComponentRegistry;
 * @property \PHPUnit_Framework_MockObject_MockObject|ServerRequest $request;
 * @property \PHPUnit_Framework_MockObject_MockObject|Response $response;
 * @property AuthTestController $Controller;
 */
class FormAuthenticateTest extends TestCase
{
    /**
     * @var \TwoFactorAuth\Auth\FormAuthenticate $auth
     */
    private $auth;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['plugin.TwoFactorAuth.users', 'plugin.TwoFactorAuth.auth_users'];

    /**
     * setUp method
     *
     * @return void
     * @throws \ReflectionException
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = new ServerRequest();
        $this->response = new Response();

        $this->Controller = new AuthTestController($this->request, $this->response);
        $this->protectedMethodCall($this->Controller->Auth, '_setDefaults');
        $this->ComponentRegistry = new ComponentRegistry($this->Controller);
        $this->auth = new FormAuthenticate($this->ComponentRegistry);

        $password = password_hash('password', PASSWORD_DEFAULT);
        TableRegistry::getTableLocator()->clear();
        $Users = TableRegistry::getTableLocator()->get('Users');
        $Users->updateAll(['password' => $password], []);
        $AuthUsers = TableRegistry::getTableLocator()->get('AuthUsers', [
            'className' => 'TwoFactorAuth\Test\App\Model\Table\AuthUsersTable'
        ]);
        $AuthUsers->updateAll(['password' => $password], []);
    }

    /**
     * test applying settings in the constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'fields' => ['username' => 'user', 'password' => 'password', 'secret' => 'secret', 'remember' => 'remember']
        ]);

        $this->assertEquals('AuthUsers', $this->auth->getConfig('userModel'));
        $this->assertEquals(
            ['username' => 'user', 'password' => 'password', 'secret' => 'secret', 'remember' => 'remember'],
            $this->auth->getConfig('fields')
        );
    }

    /**
     * test getting user credentials from request
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetCredentialsFromRequest()
    {
        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'fields' => ['username' => 'user', 'password' => 'password', 'secret' => 'secret']
        ]);

        $this->request = $this->request
            ->withData('user', 'testUsername')
            ->withData('password', 'testPassword');

        $this->assertEquals(
            ['username' => 'testUsername', 'password' => 'testPassword'],
            $this->protectedMethodCall($this->auth, '_getCredentials', [$this->request])
        );
    }

    /**
     * test getting user credentials from session
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetCredentialsFromSession()
    {
        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'fields' => ['username' => 'user', 'password' => 'password', 'secret' => 'secret']
        ]);

        $this->request->getSession()->write([
            'TwoFactorAuth.credentials' => [
                'username' => $this->_encrypt('testUsername'),
                'password' => $this->_encrypt('testPassword'),
            ]
        ]);

        $this->assertEquals(
            ['username' => 'testUsername', 'password' => 'testPassword'],
            $this->protectedMethodCall($this->auth, '_getCredentials', [$this->request])
        );
    }

    /**
     * test getting user credentials from request priority over session
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetCredentialsFromRequestOverSession()
    {
        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'fields' => ['username' => 'user', 'password' => 'password', 'secret' => 'secret']
        ]);

        $this->request = $this->request
            ->withData('user', 'testUsernameFromRequest')
            ->withData('password', 'testPasswordFromRequest');
        $this->request->getSession()->write([
            'TwoFactorAuth.credentials' => [
                'username' => $this->_encrypt('testUsername'),
                'password' => $this->_encrypt('testPassword')
            ]
        ]);

        $this->assertEquals(
            ['username' => 'testUsernameFromRequest', 'password' => 'testPasswordFromRequest'],
            $this->protectedMethodCall($this->auth, '_getCredentials', [$this->request])
        );
    }

    /**
     * test getting user credentials when they're not set
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetCredentialsNone()
    {
        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'fields' => ['username' => 'user', 'password' => 'password', 'secret' => 'secret']
        ]);

        $this->assertFalse($this->protectedMethodCall($this->auth, '_getCredentials', [$this->request]));
    }

    /**
     * test authenticating user having a secret, but no one-time code passed
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateWithSecretNoCode()
    {
        $this->request = $this->request
            ->withData('username', 'nate')
            ->withData('password', 'password');
        $this->response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['withLocation'])->getMock();

        $this->response->expects($this->once())
            ->method('withLocation')
            ->with('/Users/verify')
            ->will($this->returnSelf());

        $this->assertFalse($this->auth->authenticate($this->request, $this->response));
    }

    /**
     * test authenticating user having a secret, invalid code passed
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateWithSecretCodeInvalid()
    {
        $this->request = $this->request->withData('code', '123');
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);
        $this->response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['withLocation'])->getMock();

        $this->response->expects($this->once())
            ->method('withLocation')
            ->with('/Users/verify')
            ->will($this->returnSelf());

        $this->assertFalse($this->auth->authenticate($this->request, $this->response));
        $this->assertEquals(
            'Invalid two-step verification code.',
            $this->request->getSession()->read('Flash.two-factor-auth.0.message')
        );
    }

    /**
     * test authenticating user having a secret, credentials in session, but no one-time code passed
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateWithSecretCodeNone()
    {
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);
        $this->response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['withLocation'])->getMock();

        $this->response->expects($this->once())
            ->method('withLocation')
            ->with('/Users/verify')
            ->will($this->returnSelf());

        $this->assertFalse($this->auth->authenticate($this->request, $this->response));
    }

    /**
     * test authenticating user having a secret and correct code
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateWithSecretSuccess()
    {
        $secret = TableRegistry::getTableLocator()->get('Users')->find()->where(['username' => 'nate'])->first()->get('secret');

        $this->request = $this->request->withData('code', $this->Controller->Auth->tfa->getCode($secret));
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);
        $this->response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['withLocation'])->getMock();

        $this->response->expects($this->never())->method('withLocation');

        $expected = [
            'id' => 2,
            'username' => 'nate',
            'created' => new Time('2008-03-17 01:18:23'),
            'updated' => new Time('2008-03-17 01:20:31')
        ];

        $this->assertEquals(
            $expected,
            $this->auth->authenticate($this->request, $this->response)
        );

        $this->assertNull($this->request->getSession()->read('TwoFactorAuth.credentials'));
    }

    /**
     * test authenticating when wrong Auth component used
     *
     * @return void
     * @expectedException \Exception
     * @expectedExceptionMessage TwoFactorAuth.Auth component has to be used for authentication.
     */
    public function testWrongAuthComponentUsed()
    {
        $this->request = $this->request->withData('code', '123');
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);

        $this->Controller->Auth = new Component\AuthComponent($this->ComponentRegistry);

        $this->assertFalse($this->auth->authenticate($this->request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateNoData()
    {
        $request = new ServerRequest('posts/index');
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateNoUsername()
    {
        $request = new ServerRequest('posts/index');
        $this->request = $this->request->withData('password', 'foobar');
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateNoPassword()
    {
        $request = new ServerRequest('posts/index');
        $this->request = $this->request->withData('username', 'mariano');
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate password is false method
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticatePasswordIsFalse()
    {
        $request = new ServerRequest('posts/index');
        $this->request = $this->request
            ->withData('username', 'mariano')
            ->withData('password', null);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * Test for password as empty string with _getCredentials() call skipped
     * Refs https://github.com/cakephp/cakephp/pull/2441
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticatePasswordIsEmptyString()
    {
        $request = new ServerRequest('posts/index');
        $this->request = $this->request
            ->withData('username', 'mariano')
            ->withData('password', '');
        $this->auth = $this->getMockBuilder('TwoFactorAuth\Auth\FormAuthenticate')
            ->setConstructorArgs([
                $this->ComponentRegistry,
                [
                    'userModel' => 'Users'
                ]
            ])
            ->setMethods(['_getCredentials'])
            ->getMock();

        // Simulate that check for ensuring password is not empty is missing.
        $this->auth->expects($this->once())
            ->method('_getCredentials')
            ->will($this->returnValue(true));
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate field is not string
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateFieldsAreNotString()
    {
        $request = new ServerRequest('posts/index');
        $this->request = $this->request
            ->withData('username', 'phpnut')
            ->withData('password', 'my password');
        $this->assertFalse($this->auth->authenticate($request, $this->response));
        $this->request = $this->request
            ->withData('username', 'mariano')
            ->withData('password', ['password1', 'password2']);
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test the authenticate method
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateInjection()
    {
        $request = new ServerRequest('posts/index');
        $this->request = $this->request
            ->withData('username', '> 1')
            ->withData('password', "' OR 1 = 1");
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * test authenticate success
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateSuccess()
    {
        $request = (new ServerRequest('posts/index'))
            ->withData('username', 'mariano')
            ->withData('password', 'password');
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that authenticate() includes virtual fields.
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateIncludesVirtualFields()
    {
        $users = TableRegistry::getTableLocator()->get('Users');
        $users->setEntityClass('TwoFactorAuth\Test\App\Model\Entity\VirtualUser');
        $request = (new ServerRequest('posts/index'))
            ->withData('username', 'mariano')
            ->withData('password', 'password');
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'bonus' => 'bonus',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test using custom finder
     *
     * @return void
     * @throws \Exception
     */
    public function testFinder()
    {
        $request = (new ServerRequest('posts/index'))
            ->withData('username', 'mariano')
            ->withData('password', 'password');
        $this->auth->setConfig([
            'userModel' => 'AuthUsers',
            'finder' => 'auth'
        ]);
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
        ];
        $this->assertEquals($expected, $result, 'Result should not contain "created" and "modified" fields');
    }

    /**
     * test password hasher settings
     *
     * @return void
     * @throws \Exception
     */
    public function testPasswordHasherSettings()
    {
        $this->auth->setConfig('passwordHasher', [
            'className' => 'Default',
            'hashType' => PASSWORD_BCRYPT
        ]);
        $passwordHasher = $this->auth->passwordHasher();
        $result = $passwordHasher->getConfig();
        $this->assertEquals(PASSWORD_BCRYPT, $result['hashType']);
        $hash = password_hash('mypass', PASSWORD_BCRYPT);
        $User = TableRegistry::getTableLocator()->get('Users');
        $User->updateAll(
            ['password' => $hash],
            ['username' => 'mariano']
        );
        $request = new ServerRequest('posts/index');
        $request = $request->withData('username', 'mariano')
            ->withData('password', 'mypass');
        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'mariano',
            'created' => new Time('2007-03-17 01:16:23'),
            'updated' => new Time('2007-03-17 01:18:31')
        ];
        $this->assertEquals($expected, $result);
        $this->auth = new FormAuthenticate($this->ComponentRegistry, [
            'fields' => ['username' => 'username', 'password' => 'password'],
            'userModel' => 'Users'
        ]);
        $this->auth->setConfig('passwordHasher', [
            'className' => 'Default'
        ]);
        $this->assertEquals($expected, $this->auth->authenticate($request, $this->response));
        $User->updateAll(
            ['password' => '$2y$10$/G9GBQDZhWUM4w/WLes3b.XBZSK1hGohs5dMi0vh/oen0l0a7DUyK'],
            ['username' => 'mariano']
        );
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }

    /**
     * Tests that using default means password don't need to be rehashed
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateNoRehash()
    {
        $request = (new ServerRequest('posts/index'))
            ->withData('username', 'mariano')
            ->withData('password', 'password');
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertNotEmpty($result);
        $this->assertFalse($this->auth->needsPasswordRehash());
    }

    /**
     * Tests that not using the Default password hasher means that the password
     * needs to be rehashed
     *
     * @return void
     * @throws \Exception
     */
    public function testAuthenticateRehash()
    {
        $this->auth = new FormAuthenticate($this->ComponentRegistry, [
            'userModel' => 'Users',
            'passwordHasher' => 'Weak'
        ]);
        $password = $this->auth->passwordHasher()->hash('password');
        TableRegistry::getTableLocator()->get('Users')->updateAll(['password' => $password], []);
        $request = (new ServerRequest('posts/index'))
            ->withData('username', 'mariano')
            ->withData('password', 'password');
        $result = $this->auth->authenticate($request, $this->response);
        $this->assertNotEmpty($result);
        $this->assertTrue($this->auth->needsPasswordRehash());
    }

    /**
     * Test if the security salt is used as encryption key when no custom key is set
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testEncryptionKeySecuritySalt()
    {
        $salt = 'this is just another random salt which should not be used';
        Security::setSalt($salt);

        $this->assertEquals($salt, $this->protectedMethodCall($this->auth, '_encryptionKey'));
    }

    /**
     * Test if the custom encryption key is used when set
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testCustomEncryptionKey()
    {
        $encryptionKey = 'unsafe encryption key';
        Configure::write('TwoFactorAuth.encryptionKey', $encryptionKey);

        $this->assertEquals($encryptionKey, $this->protectedMethodCall($this->auth, '_encryptionKey'));
    }

    /**
     * Test if cookie is set when remember is checked
     *
     * @return void
     * @throws \Exception
     */
    public function testLoginWithRememberTrue()
    {
        $secret = TableRegistry::getTableLocator()->get('Users')->find()->where(['username' => 'nate'])->first()->get('secret');

        $this->request = $this->request->withData('code', $this->Controller->Auth->tfa->getCode($secret));
        $this->request = $this->request->withData('remember', 1);
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);
        $this->response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['withLocation'])->getMock();

        $this->Controller->Auth->setConfig('verifyAction', 'testVerifyAction');

        $this->response->expects($this->never())->method('withLocation');

        $this->auth->setConfig('remember', true);
        $this->auth->authenticate($this->request, $this->response);

        $this->assertEquals(['secret' => $secret], $this->Controller->Cookie->read('TwoFactorAuth'));
    }

    /**
     * Test if cookie is not set if remember isn't checked
     *
     * @return void
     * @throws \Exception
     */
    public function testLoginWithRememberFalse()
    {
        $secret = TableRegistry::getTableLocator()->get('Users')->find()->where(['username' => 'nate'])->first()->get('secret');

        $this->request = $this->request->withData('code', $this->Controller->Auth->tfa->getCode($secret));
        $this->request = $this->request->withData('remember', 0);
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);
        $this->response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['withLocation'])->getMock();

        $this->Controller->Auth->setConfig('verifyAction', 'testVerifyAction');

        $this->response->expects($this->never())->method('withLocation');

        $this->auth->setConfig('remember', true);
        $this->auth->authenticate($this->request, $this->response);
        $this->assertNull($this->Controller->Cookie->read('TwoFactorAuth'));
    }

    /**
     * Test if no secret is asked when cookie is set
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testRememberedLogin()
    {
        $secret = TableRegistry::getTableLocator()->get('Users')->find()->where(['username' => 'nate'])->first()->get('secret');

        $this->Controller->loadComponent('Cookie');
        $this->Controller->Cookie->write('TwoFactorAuth', compact('secret'));
        $this->request->getSession()->write([
            'TwoFactorAuth' => [
                'credentials' => [
                    'username' => $this->_encrypt('nate'),
                    'password' => $this->_encrypt('password')
                ]
            ]
        ]);

        $this->Controller->Auth->setConfig('verifyAction', 'testVerifyAction');

        $this->auth->setConfig('remember', true);
        $this->assertTrue($this->protectedMethodCall($this->auth, '_verifyCode', [$secret, null, $this->response]));
    }

    /**
     * Call a protected method on an object
     *
     * @param object $obj object
     * @param string $name method to call
     * @param array $args arguments to pass to the method
     * @return mixed
     * @throws \ReflectionException
     */
    public function protectedMethodCall($obj, $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * Call the encrypt method
     *
     * @param string $value the string to encrypt
     * @return string
     * @throws \ReflectionException
     */
    private function _encrypt($value)
    {
        return $this->protectedMethodCall($this->auth, '_encrypt', [$value]);
    }
}
