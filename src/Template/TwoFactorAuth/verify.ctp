<?php
/**
 * @var \App\View\AppView $this
 * @var array $loginAction
 */
echo $this->Form->create(null, ['url' => $loginAction]);
echo $this->Form->input('code', ['label' => __('Verification code')]);
echo $this->Form->button(__('Verify'));
echo $this->Form->end();
