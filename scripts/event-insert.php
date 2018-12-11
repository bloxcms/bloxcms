<?php
    
    $pagehref = Blox::getPageHref();
    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
    Url::redirect($pagehref);
    if ($_POST['description']) {
        $sql = 'INSERT INTO '.Blox::info('db','prefix').'countevents (`date`, description) VALUES(?, ?)';  
        Sql::query($sql, [$_POST['date'], $_POST['description']]);
    }
