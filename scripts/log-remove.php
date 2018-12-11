<?php

    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');

    $logsFile = Blox::info('cms','dir').'/misc/logs.txt';
    if (file_exists($logsFile)) {
        $logsTxt = file_get_contents($logsFile);
        # Check permission
        if (mb_strpos($logsTxt, $_GET['file']) !== false)
            unlink($_GET['file']);
        else
            Blox::prompt($terms['error1']);
    } else
        Blox::prompt($terms['error2']);
 
    Url::redirectToReferrer();
    