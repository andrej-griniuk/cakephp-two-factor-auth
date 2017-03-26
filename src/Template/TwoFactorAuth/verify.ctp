<?php
/**
 * @var \Cake\View\View $this
 * @var array $loginAction
 */
echo $this->Flash->render('two-factor-auth');
echo $this->Form->create(null, ['url' => $loginAction]);
echo $this->Form->control('code', ['label' => __('Verification code')]);
echo $this->Form->button(__('Verify'));
echo $this->Form->end();
