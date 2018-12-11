<?php

    if ($_POST['login']) # Redirect from ?install
        Url::redirect(Blox::info('site','url').'/?login');
    else {
        $pagehref = Blox::getPageHref();
        Url::redirect($pagehref);
    }
        
    if (setcookie('blox', "", time() + 10));
    # Full logout
    $_SESSION = [];
    session_destroy();