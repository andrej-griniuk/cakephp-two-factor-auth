<?php
namespace TwoFactorAuth\Test\TestCase\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\TestSuite\TestCase;
use TwoFactorAuth\Controller\Component\AuthComponent;

/**
 * TwoFactorAuth\Controller\Component\TwoFactorAuthComponent Test Case
 */
class TwoFactorAuthComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \TwoFactorAuth\Controller\Component\AuthComponent
     */
    public $Auth;
    public $Controller;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $request = new Request();
        $response = $this->getMockBuilder('Cake\Http\Response')->setMethods(['stop'])->getMock();

        $this->Controller = new Controller($request, $response);
        $this->Auth = new AuthComponent($this->Controller->components());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Auth);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->assertInstanceOf('RobThree\Auth\TwoFactorAuth', $this->Auth->tfa);
    }

    public function testDefaultVerifyAction()
    {
        $this->protectedMethodCall($this->Auth, '_setDefaults');

        $this->assertEquals([
            'controller' => 'TwoFactorAuth',
            'action' => 'verify',
            'plugin' => 'TwoFactorAuth',
            'prefix' => false,
        ], $this->Auth->getConfig('verifyAction'));
    }

    public function testVerifyAction()
    {
        $this->Auth->setConfig('verifyAction', 'testAction');
        $this->protectedMethodCall($this->Auth, '_setDefaults');

        $this->assertEquals('testAction', $this->Auth->getConfig('verifyAction'));
    }

    public function testVerifyCode()
    {
        $secret = $this->Auth->tfa->createSecret();
        $code = $this->Auth->tfa->getCode($secret);

        $this->assertTrue($this->Auth->tfa->verifyCode($secret, $code));
    }

    public function testVerifyCodeWrong()
    {
        $secret = $this->Auth->tfa->createSecret();
        $code = (int)$this->Auth->tfa->getCode($secret) + 1;

        $this->assertFalse($this->Auth->tfa->verifyCode($secret, (string)$code));
    }

    /**
     * Call a protected method on an object
     *
     * @param Component $obj object
     * @param string $name method to call
     * @param array $args arguments to pass to the method
     * @return mixed
     */
    public function protectedMethodCall($obj, $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
