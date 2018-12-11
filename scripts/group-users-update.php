<?php
    
/**
 * Accepts or exclude one user from a group
 */

if (!Blox::info('user','user-is-admin'))
    Blox::execute('?error-document&code=403');
//$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
Url::redirect(Blox::getPageHref());

if (isset($_GET['selected-group-id']))
    $selectedGroupId = (int)$_GET['selected-group-id'];

foreach ($_POST['users'] as $userId => $commands) {        
    if ($commands['is-member'])
        Proposition::set('group-has-user', $selectedGroupId, $userId, true);        
    else # Remove
        Proposition::set('group-has-user', $selectedGroupId, $userId, false);
}
