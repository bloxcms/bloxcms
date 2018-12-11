<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
    $sql = "SELECT * FROM ".Blox::info('db','prefix')."countevents WHERE id=? LIMIT 1";
    if ($result = Sql::query($sql, [$_GET['id']])) {
        $row = $result->fetch_assoc();
        $result->free();
        $event = $row;
        $template->assign('event', $event);
    }
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
