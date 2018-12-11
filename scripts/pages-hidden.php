<?php
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');

    $sql = "SELECT * FROM ".Blox::info('db','prefix')."pages WHERE `page-is-hidden`=1";
    if ($hiddenPages = Sql::select($sql))
        $template->assign('hiddenPages', $hiddenPages);
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";