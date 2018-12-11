<?php
if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
    Blox::execute('?error-document&code=403');
$pagehref = Blox::getPageHref();
$pagehrefQuery = '&pagehref='.Url::encode($pagehref);    
$regularId = (int)$_GET['block'];
$errorsQuery = '';
$valuesQuery = '';
$oldSettings = unserialize(self::getBlockInfo($regularId, 'settings'));
$newSettings = $_POST;
unset($newSettings['button-ok']);
############### Checks ####################

# edit-button-style
foreach ($_POST['edit-button-style'] as $option=>$value) {
    $value = trim($value);
    if (!($value==='' || Str::isInteger($value, ['zero', 'negative']))) {
        $errorsQuery .= '&errors[edit-button-style]['.$option.']=not-integer';
        $valuesQuery .= '&invalids[edit-button-style]['.$option.']='.urlencode($value);
    }
    $newSettings['edit-button-style'][$option] = $value;
}


#block-caching
if (!($oldSettings['block-caching'] == $newSettings['block-caching']))
    unset($newSettings['block-caching']['cached']);
if (!$newSettings['block-caching']['cache']) {
    unset($newSettings['block-caching']['cached']);
    #unlink('cached-blocks/'.$regularId.'.htm'); # How to avoid empty dependency?
}
############### /Checks ####################   


if ($errorsQuery || $valuesQuery)
    Url::redirect(Blox::info('site','url').'/?block-settings&block='.$regularId.$errorsQuery.$valuesQuery.$pagehrefQuery);
else {
    Sql::query('UPDATE '.Blox::info('db','prefix').'blocks SET `settings`=? WHERE id=?', [serialize($newSettings), $regularId]);
    Url::redirect($pagehref);
}