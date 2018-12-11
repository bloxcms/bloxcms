<?php
# DEPRECATED. USED AS SAMPLE ONLY
if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
    Blox::execute('?error-document&code=403');

if ($_POST['button-ok'] == 'submit-and-return')
    Url::redirectToReferrer();
else
    Url::redirect(Blox::getPageHref());

if ($_POST['data']) {
    $data = $_POST['data'];
    $data['block-id'] = (int)$_GET['block'];
    Data::replace(Blox::info('db','prefix').'editbuttonsetting', $data);
}
