<?php

    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    Url::redirect(Blox::info('site','url').'/?assign&block='.$_GET['block'].'&instance-option=delete'.$pagehrefQuery,'exit');