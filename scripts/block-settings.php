<?php
/**
 * Made on the basis of "site-settings.php"
 */
if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
    Blox::execute('?error-document&code=403');
$regularId = (int)$_GET['block'];
$template->assign('regularId', $regularId);
if ($oldSettings = unserialize(Blox::getBlockInfo($regularId, 'settings')))
    $template->assign('oldSettings', $oldSettings);
include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";