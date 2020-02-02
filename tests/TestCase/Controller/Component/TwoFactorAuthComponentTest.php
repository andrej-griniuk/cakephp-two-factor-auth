<?php
declare(strict_types=1);

namespace TwoFactorAuth\Test\TestCase\Controller\Component;

use Authentication\AuthenticationService;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use TwoFactorAuth\Controller\Component\TwoFactorAuthComponent;

/**
 * TwoFactorAuth\Controller\Component\TwoFactorAuthComponent Test Case
 */
class TwoFactorAuthComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \TwoFactorAuth\Controller\Component\TwoFactorAuthComponent
     */
    protected $TwoFactorAuth;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $service = new AuthenticationService([
            'identifiers' => [
                'Authentication.Password',
            ],
            'authenticators' => [
                'Authentication.Session',
                'TwoFactorAuth.TwoFactorForm',
            ],
        ]);

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_URI' => '/'],
            [],
            ['username' => 'mariano', 'password' => 'password']
        );
        $request = $request->withAttribute('authentication', $service);
        $response = new Response();
        $controller = new Controller($request, $response);
        $registry = new ComponentRegistry($controller);

        $this->TwoFactorAuth = new TwoFactorAuthComponent($registry);
    }

    /**
     * getTfa method test
     *
     * @return void
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function testGetTfa(): void
    {
        $tfa = $this->TwoFactorAuth->getTfa();
        $this->assertInstanceOf(\RobThree\Auth\TwoFactorAuth::class, $tfa);
    }

    /**
     * createSecret method test
     *
     * @return void
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function testCreateSecret(): void
    {
        $secret = $this->TwoFactorAuth->createSecret();
        $this->assertNotNull($secret);
    }

    /**
     * verifyCode method test
     *
     * @return void
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function testVerifyCodeFailure(): void
    {
        $secret = $this->TwoFactorAuth->createSecret();
        $this->assertFalse($this->TwoFactorAuth->verifyCode($secret, '123456'));
    }

    /**
     * verifyCode method test
     *
     * @return void
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function testVerifyCodeSuccess(): void
    {
        $secret = $this->TwoFactorAuth->createSecret();
        $code = $this->TwoFactorAuth->getTfa()->getCode($secret);
        $this->assertTrue($this->TwoFactorAuth->verifyCode($secret, $code));
    }

    /**
     * getQRCodeImageAsDataUri method test
     *
     * @return void
     * @throws \RobThree\Auth\TwoFactorAuthException
     */
    public function testGetQRCodeImageAsDataUri(): void
    {
        $uri = $this->TwoFactorAuth->getQRCodeImageAsDataUri('label', $this->TwoFactorAuth->getTfa()->createSecret());
        $this->assertNotNull($uri);
    }
}
