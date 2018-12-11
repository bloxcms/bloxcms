<?php

    $pagehref = Blox::getPageHref();
    
    if (empty($_POST['date']) || !Blox::info('user','id') || !(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')))
        Url::redirect($pagehref,'exit');
    Url::redirect($pagehref);
    
    $statisticsSubjects = ['pages', 'updates', 'downloads', 'remotehosts', 'referers'];
    foreach ($statisticsSubjects as $statisticsSubject) {
        $sql = 'DELETE FROM '.Blox::info('db','prefix').'count'.$statisticsSubject.' WHERE `date`<?';
        Sql::query($sql, [$_POST['date']]);  // `date` obj counter      
    }

    $sql = 'DELETE FROM '.Blox::info('db','prefix').'countevents WHERE `date`<?';
    Sql::query($sql, [$_POST['date']]); // id  `date` description 
        
    $sql = 'TRUNCATE '.Blox::info('db','prefix').'countremotehosts_names';
    Sql::query($sql);