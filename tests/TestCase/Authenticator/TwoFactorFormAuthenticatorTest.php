<?php
declare(strict_types=1);

namespace TwoFactorAuth\Test\TestCase\Authenticator;

use Authentication\Authenticator\Result;
use Authentication\Identifier\IdentifierCollection;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;
use TwoFactorAuth\Authenticator\TwoFactorFormAuthenticator;

class TwoFactorFormAuthenticatorTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Users',
    ];

    /**
     * @var \Cake\ORM\Table
     */
    protected $Users;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_setupUsersAndPasswords();
    }

    /**
     * _setupUsersAndPasswords
     *
     * @return void
     */
    protected function _setupUsersAndPasswords()
    {
        TableRegistry::getTableLocator()->clear();
        $this->Users = TableRegistry::getTableLocator()->get('Users', [
            'className' => 'TestApp\Model\Table\UsersTable',
        ]);

        $password = password_hash('password', PASSWORD_DEFAULT);
        $this->Users->updateAll(['password' => $password], []);
    }

    /**
     * testAuthenticate
     *
     * @return void
     */
    public function testAuthenticate()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers);
        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
    }

    /**
     * testCredentialsNotPresent
     *
     * @return void
     */
    public function testCredentialsNotPresent()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/does-not-match'],
            [],
            []
        );

        $form = new TwoFactorFormAuthenticator($identifiers);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIALS_MISSING, $result->getStatus());
        $this->assertEquals([0 => 'Login credentials not found'], $result->getErrors());
    }

    /**
     * testCredentialsEmpty
     *
     * @return void
     */
    public function testCredentialsEmpty()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/does-not-match'],
            [],
            ['username' => '', 'password' => '']
        );

        $form = new TwoFactorFormAuthenticator($identifiers);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIALS_MISSING, $result->getStatus());
        $this->assertEquals([0 => 'Login credentials not found'], $result->getErrors());
    }

    /**
     * testSingleLoginUrlMismatch
     *
     * @return void
     */
    public function testSingleLoginUrlMismatch()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/does-not-match'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/users/login',
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_OTHER, $result->getStatus());
        $this->assertEquals([0 => 'Login URL `/users/does-not-match` did not match `/users/login`.'], $result->getErrors());
    }

    /**
     * testMultipleLoginUrlMismatch
     *
     * @return void
     */
    public function testMultipleLoginUrlMismatch()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/does-not-match'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => [
                '/en/users/login',
                '/de/users/login',
            ],
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_OTHER, $result->getStatus());
        $this->assertEquals([0 => 'Login URL `/users/does-not-match` did not match `/en/users/login` or `/de/users/login`.'], $result->getErrors());
    }

    /**
     * testLoginUrlMismatchWithBase
     *
     * @return void
     */
    public function testLoginUrlMismatchWithBase()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/login'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );
        $uri = $request->getUri();
        $uri->base = '/base';
        $request = $request->withUri($uri);
        $request = $request->withAttribute('base', $uri->base);

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/users/login',
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_OTHER, $result->getStatus());
        $this->assertEquals([0 => 'Login URL `/base/users/login` did not match `/users/login`.'], $result->getErrors());
    }

    /**
     * testSingleLoginUrlSuccess
     *
     * @return void
     */
    public function testSingleLoginUrlSuccess()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/Users/login'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/Users/login',
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertEquals([], $result->getErrors());
    }

    /**
     * testMultipleLoginUrlSuccess
     *
     * @return void
     */
    public function testMultipleLoginUrlSuccess()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/de/users/login'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => [
                '/en/users/login',
                '/de/users/login',
            ],
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertEquals([], $result->getErrors());
    }

    /**
     * testLoginUrlSuccessWithBase
     *
     * @return void
     */
    public function testLoginUrlSuccessWithBase()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/login'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );
        $uri = $request->getUri();
        $uri->base = '/base';
        $request = $request->withUri($uri);
        $request = $request->withAttribute('base', $uri->base);

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/base/users/login',
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertEquals([], $result->getErrors());
    }

    /**
     * testRegexLoginUrlSuccess
     *
     * @return void
     */
    public function testRegexLoginUrlSuccess()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/de/users/login'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '%^/[a-z]{2}/users/login/?$%',
            'urlChecker' => [
                'useRegex' => true,
            ],
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertEquals([], $result->getErrors());
    }

    /**
     * testFullRegexLoginUrlFailure
     *
     * @return void
     */
    public function testFullRegexLoginUrlFailure()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            [
                'REQUEST_URI' => '/de/users/login',
            ],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '%auth\.localhost/[a-z]{2}/users/login/?$%',
            'urlChecker' => [
                'useRegex' => true,
                'checkFullUrl' => true,
            ],
        ]);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_OTHER, $result->getStatus());
        $this->assertEquals([0 => 'Login URL `http://localhost/de/users/login` did not match `%auth\.localhost/[a-z]{2}/users/login/?$%`.'], $result->getErrors());
    }

    /**
     * testRegexLoginUrlSuccess
     *
     * @return void
     */
    public function testFullRegexLoginUrlSuccess()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            [
                'REQUEST_URI' => '/de/users/login',
                'SERVER_NAME' => 'auth.localhost',
            ],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );
        $response = new Response();

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '%auth\.localhost/[a-z]{2}/users/login/?$%',
            'urlChecker' => [
                'useRegex' => true,
                'checkFullUrl' => true,
            ],
        ]);

        $result = $form->authenticate($request, $response);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
        $this->assertEquals([], $result->getErrors());
    }

    /**
     * testAuthenticateCustomFields
     *
     * @return void
     */
    public function testAuthenticateCustomFields()
    {
        $identifiers = $this->createMock(IdentifierCollection::class);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/login'],
            [],
            ['email' => 'mariano@cakephp.org', 'secret' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/users/login',
            'fields' => [
                'username' => 'email',
                'password' => 'secret',
            ],
        ]);

        $identifiers->expects($this->once())
            ->method('identify')
            ->with([
                'username' => 'mariano@cakephp.org',
                'password' => 'password',
            ])
            ->willReturn([
                'username' => 'mariano@cakephp.org',
                'password' => 'password',
            ]);

        $form->authenticate($request);
    }

    /**
     * testAuthenticateValidData
     *
     * @return void
     */
    public function testAuthenticateValidData()
    {
        $identifiers = $this->createMock(IdentifierCollection::class);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/login'],
            [],
            ['id' => 1, 'username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/users/login',
        ]);

        $identifiers->expects($this->once())
            ->method('identify')
            ->with([
                'username' => 'mariano',
                'password' => 'password',
            ])
            ->willReturn([
                'username' => 'mariano',
                'password' => 'password',
            ]);

        $form->authenticate($request);
    }

    /**
     * testAuthenticateValidData
     *
     * @return void
     */
    public function testAuthenticateMissingChecker()
    {
        $identifiers = $this->createMock(IdentifierCollection::class);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/login'],
            [],
            ['id' => 1, 'username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/users/login',
            'urlChecker' => 'Foo',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('URL checker class `Foo` was not found.');

        $form->authenticate($request);
    }

    /**
     * testAuthenticateValidData
     *
     * @return void
     */
    public function testAuthenticateInvalidChecker()
    {
        $identifiers = $this->createMock(IdentifierCollection::class);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/users/login'],
            [],
            ['id' => 1, 'username' => 'mariano', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers, [
            'loginUrl' => '/users/login',
            'urlChecker' => self::class,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The provided URL checker class `TwoFactorAuth\Test\TestCase\Authenticator\TwoFactorFormAuthenticatorTest` ' .
            'does not implement the `Authentication\UrlChecker\UrlCheckerInterface` interface.'
        );

        $form->authenticate($request);
    }

    /**
     * testAuthenticateTwoFactorRequired
     *
     * @return void
     */
    public function testAuthenticateTwoFactorRequired()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['username' => 'nate', 'password' => 'password']
        );

        $form = new TwoFactorFormAuthenticator($identifiers);
        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(\TwoFactorAuth\Authenticator\Result::TWO_FACTOR_AUTH_REQUIRED, $result->getStatus());
    }

    /**
     * testAuthenticateTwoFactorInvalidCode
     *
     * @return void
     */
    public function testAuthenticateTwoFactorInvalidCode()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['code' => 123]
        );
        $user = $this->Users->find()->where(['username' => 'nate'])->firstOrFail();
        $request->getAttribute('session')->write('TwoFactorAuth.user', $user);

        $form = new TwoFactorFormAuthenticator($identifiers);
        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(\TwoFactorAuth\Authenticator\Result::TWO_FACTOR_AUTH_FAILED, $result->getStatus());
    }

    /**
     * testAuthenticateTwoFactorWithCodeNoUser
     *
     * @return void
     */
    public function testAuthenticateTwoFactorWithCodeNoUser()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['code' => 123456]
        );

        $form = new TwoFactorFormAuthenticator($identifiers);
        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::FAILURE_CREDENTIALS_MISSING, $result->getStatus());
    }

    /**
     * testAuthenticateTwoFactorCorrectCode
     *
     * @return void
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function testAuthenticateTwoFactorCorrectCode()
    {
        $identifiers = new IdentifierCollection([
            'Authentication.Password',
        ]);

        $user = $this->Users->find()->where(['username' => 'nate'])->firstOrFail();
        $form = new TwoFactorFormAuthenticator($identifiers);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/testpath'],
            [],
            ['code' => $form->getTfa()->getCode($user->secret)]
        );
        $request->getAttribute('session')->write('TwoFactorAuth.user', $user);

        $result = $form->authenticate($request);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(Result::SUCCESS, $result->getStatus());
    }
}
