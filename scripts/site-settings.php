<?php
if (!Blox::info('user','user-is-admin')) 
    Blox::execute('?error-document&code=403');
         
# TODO Take from global
if ($oldSettings = Store::get('site-settings'))
    $template->assign('oldSettings', $oldSettings);

include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";