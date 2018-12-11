<?php  
if (Blox::info('user')) {
    if (($_GET['formula']=='editing-denied' || $_GET['formula']=='site-is-down') && !Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');
} else {
    Blox::execute('?error-document&code=403');
}
$subject = $_GET['subject'] ?: null;
$obj  = $_GET['object']  ?: null;
$value = ($_GET['old-value']) ? false : true;
Proposition::set($_GET['formula'], $subject, $obj, $value);
if ($_GET['redirect'])
    Url::redirect(Blox::info('site','url').'/?'.$_GET['redirect']);
else {
    $pagehref = Blox::getPageHref();
    Url::redirect($pagehref);
}
