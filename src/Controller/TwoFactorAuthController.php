<?php
namespace TwoFactorAuth\Controller;

use Cake\Event\Event;
use App\Controller\AppController;

/**
 * TwoFactorAuth Controller
 */
class TwoFactorAuthController extends AppController
{

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->Auth->allow(['verify']);
    }

    public function verify()
    {
        $this->set('loginAction', $this->Auth->config('loginAction'));
    }
}
