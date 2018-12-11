<?php
    
    if (!Blox::info('user','id')) {
        Url::redirect(Blox::info('site','url').'/?login&pagehref='.Blox::getPageHref(true), true);          
    } else {
        Subscription::subscribe(
            Blox::getBlockInfo($_GET['block'], 'src-block-id'), 
            Blox::info('user','id'), 
            $_GET['action']
        );
        Url::redirect(Blox::getPageHref());
    }