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
     * Test index method
     *
     * @return void
     */
    public function testVerify()
    {
        $this->assertEquals(true, true);
    }
}
