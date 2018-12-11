<?php
    
    /**
     * Accepts or exclude one user from groups
     */
     
    if (!Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');
    //$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    Url::redirect(Blox::getPageHref());

    if (isset($_GET['selected-user-id']))
        $selectedUserId = (int)$_GET['selected-user-id'];

    foreach ($_POST['groups'] as $groupId => $commands) {        
        if ($commands['is-member'])
            Proposition::set('group-has-user', $groupId, $selectedUserId, true);        
        else # Remove
            Proposition::set('group-has-user', $groupId, $selectedUserId, false);
    }