<?php
if (!Blox::info('user','id')) 
    Blox::execute('?error-document&code=403');              
Store::set($_GET['var'], $_GET['value']);    
Url::redirect(Blox::info('site','url').'/?'.$_GET['redirect']);
