<?php
    
    if (!Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');

    $pagehref = Blox::getPageHref();
    Url::redirect($pagehref);
    
    # only denying   
    foreach ($_POST['user-objects'] as $objectId => $action) {
        if (empty($action)) {
            $selectedUserId =  Sql::sanitizeInteger($_GET['selected-user-id']);
            Proposition::set($_GET['formula'], $selectedUserId, $objectId, false);
        }
    }