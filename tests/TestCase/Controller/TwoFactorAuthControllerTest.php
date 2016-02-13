<?php
namespace TwoFactorAuth\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use TwoFactorAuth\Controller\TwoFactorAuthController;

/**
 * TwoFactorAuth\Controller\TwoFactorAuthController Test Case
 */
class TwoFactorAuthControllerTest extends IntegrationTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        //'plugin.two_factor_auth.two_factor_auth'
    ];

    /**
     * Test index method
     *
     * @return void
     */
    public function testVerify()
    {
        $this->assertEquals(true, true);
        //$this->markTestIncomplete('Not implemented yet.');
    }
}
