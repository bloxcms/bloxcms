<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
    # switch on/off
    if (isset($_GET['toggle'])) {
        if (Store::get('statisticsIsOff'))
            Store::delete('statisticsIsOff');
        else
            Store::set('statisticsIsOff', true);
    }

    if (Store::get('statisticsIsOff'))
        $template->assign('statisticsIsOff', true);


    if ($_SESSION['Blox']['stat']['time-interval']=='custom') {
        $from = $_SESSION['Blox']['stat']['from'];
        $till = $_SESSION['Blox']['stat']['till'];
    } else {
        $now = getdate();
        $year = $now['year'];
        $month = $now['mon'];
        $day = $now['mday'];
        $today = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
        $from = $today;
        $till = $today;
    }
    $template->assign('from', $from);
    $template->assign('till', $till);
    $template->assign('today', $today);

    # Default
    if (empty($_SESSION['Blox']['stat']['subject'])) {
        $_SESSION['Blox']['stat']['subject'] = 'referers';
        $_SESSION['Blox']['stat']['time-interval'] = 'year';
    }
    $template->assign('statisticsSubject', $_SESSION['Blox']['stat']['subject']);
    $template->assign('timeInterval', $_SESSION['Blox']['stat']['time-interval']);

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
