<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
    $now = getdate(); 
    $year = $now['year'];
    $month = $now['mon'];
    $day = $now['mday'];
    $today = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
    $template->assign('today', $today);
    include Blox::info('cms','dir').'/includes/buttons-submit.php';
    include Blox::info('cms','dir').'/includes/display.php';