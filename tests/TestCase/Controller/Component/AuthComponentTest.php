<?php
namespace TwoFactorAuth\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
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
    public $TwoFactorAuth;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->TwoFactorAuth = new TwoFactorAuthComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->TwoFactorAuth);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
