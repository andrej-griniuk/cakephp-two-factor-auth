<?php
declare(strict_types=1);

namespace TwoFactorAuth\Test\TestCase\View\Helper;

use Authentication\AuthenticationService;
use Cake\Http\Response;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use TwoFactorAuth\View\Helper\TwoFactorAuthHelper;

/**
 * TwoFactorAuth\View\Helper\TwoFactorAuthHelper Test Case
 */
class TwoFactorAuthHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \TwoFactorAuth\View\Helper\TwoFactorAuthHelper
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
        $view = new View($request, $response);

        $this->TwoFactorAuth = new TwoFactorAuthHelper($view);
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
