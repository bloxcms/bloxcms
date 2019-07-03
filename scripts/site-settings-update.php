<?php
/**
 * @todo #reconvert-url. If you have changed the human url mode, in order to avoid unnecessary redirects, you will have to manually re-save all records that have string fields (varchar) with 'reconvert-url' parameter, and text fields (text, tinytext, mediumtext, longtext) without 'dont-convert-url' parameter
 */
$fileUploadTypes = ['favicon'];
    
if (!Blox::info('user','user-is-admin')) 
    Blox::execute('?error-document&code=403');
$pagehref = Blox::getPageHref();
$pagehrefQuery = '&pagehref='.Url::encode($pagehref);    

$errorsQuery = '';
$valuesQuery = '';
$oldSettings = Store::get('site-settings'); # TODO Take from global
$newSettings = $_POST;
unset($newSettings['button-ok']);
############### Checks ###############################
# favicon delete-file
if ($_POST['favicon']['delete-file']) {
    $oldFaviconFile = Blox::info('site','dir').'/datafiles/'.$oldSettings['favicon']['file-name'];
    if (Files::unLink($oldFaviconFile)) {
        Blox::prompt($terms['favicon']['icon-is-deleted']);
        $newSettings['favicon']['file-name'] = '';}
    else {
        $errorsQuery .= '&errors[favicon]=icon-not-deleted';
    }
} elseif ($_FILES['favicon']['error']['file-name']===0) { # favicon upload
    if (
        'image/x-icon'       == $_FILES['favicon']['type']['file-name'] ||
        'application/x-icon' == $_FILES['favicon']['type']['file-name'] ||
        'image/png'          == $_FILES['favicon']['type']['file-name']
    ) {
        $oldFaviconFile = Blox::info('site','dir').'/datafiles/'.$oldSettings['favicon']['file-name'];
        Files::unLink($oldFaviconFile); # TODO: Remove in advance when a successful upload, but for that Upload::uploadFiles() should report error messages
        $uploadedFiles = Upload::uploadFiles(Upload::format($_FILES), Blox::info('site','dir').'/datafiles');
        if ($uploadedFiles['favicon']['file-name']) {
            $newSettings['favicon']['file-name'] =  $uploadedFiles['favicon']['file-name'];
            $oldFaviconFile = Blox::info('site','dir').'/datafiles/'.$oldSettings['favicon']['file-name'];
            Blox::prompt($terms['favicon']['icon-is-uploaded']);
        } else
            $errorsQuery .= '&errors[favicon]=file-not-uploaded';
    } else
        $errorsQuery .= '&errors[favicon]=icon-not-correct';
}


# Check Error Pages 
foreach ($_POST['errorpages'] as $option=>$value) {
    if ('' === $value)
        $errorPage = '';
    elseif ($errorPage = Router::convertUrlToPartFreePhref($value))
        ;
    else {
        $errorsQuery .= '&errors[errorpages]['.$option.']=no-url';
        $errorPage = $oldSettings['errorpages'][$option];
        $valuesQuery .= '&invalids[errorpages]['.$option.']='.urlencode($value);
    }
    $newSettings['errorpages'][$option] = $errorPage;
}

# Check emails
if ($_POST['emails']) {
    foreach ($_POST['emails'] as $option=>$value) {
        if ($value = trim($value)) {
            if (in_array($option, ['to','from'])) {
                Email::format($value, $invalidEmails);
                if ($invalidEmails) {
                    $errorsQuery .= '&errors[emails]['.$option.']=incorrect-email';
                    $valuesQuery .= '&invalids[emails]['.$option.']='.urlencode($value);
                    $newSettings['emails'][$option] = $oldSettings['emails'][$option];
                }
            } elseif ($option == 'transport') {
                $err = false;
                $jsonError = '';
                if (function_exists('json_decode')) {
                    $aa = json_decode($value, true);
                    $jsonError = trim(Str::getJsonError());
                    if (!($jsonError === '' || $jsonError === 'No error')) {
                        $err = true; # 'json syntax error. You can check the code on the website <a href="http://jsonlint.com" target="_blank">jsonlint.com</a>';
                        $errorsQuery .= '&errors[emails][transport]=incorrect-json';
                    }
                } elseif ($dat['edit']) {
                    $err = true; # $jsonErrorMsg = 'The function <b>json_decode()</b> does not exist';
                    $errorsQuery .= '&errors[emails][transport]=no-function';
                }
                
                if ($err) {
                    $valuesQuery .= '&invalids[emails]['.$option.']='.urlencode($value);
                    //$valuesQuery .= '&invalids[emails]['.$option.']=';
                    $newSettings['emails'][$option] = $oldSettings['emails'][$option];
                }
            }   
        }
    }
}

# human-urls
# @todo #reconvert-url
if (isset($_POST['human-urls'])) {
    # Remove page caches
    if (
        $oldSettings['human-urls']['on'] <> $newSettings['human-urls']['on'] || 
        !Blox::info('site', 'caching')
    ) Cache::delete();
}
############################################################
    
# For FILE upload only. If there is no new upload then store the old setting
foreach ($fileUploadTypes as $type) {
    $aa = $oldSettings[$type];
    foreach ($aa as $k1 => $v1) {
        if (is_array($v1)) { # No need yet
            foreach ($v1 as $k2 => $v2) {
                if (!isset($newSettings[$type][$k1][$k2])) # If new setting is not specified, write the old one
                    $newSettings[$type][$k2] = $v2;
            }
        } else {
            if (!isset($newSettings[$type][$k1])) # If new setting is not specified, write the old one
                $newSettings[$type][$k1] = $v1;
        }
    }
}


if ($errorsQuery || $valuesQuery)
    Url::redirect(Blox::info('site','url').'/?site-settings'.$errorsQuery.$valuesQuery.$pagehrefQuery);
else {
    Store::set('site-settings', $newSettings);
    Url::redirect($pagehref);
}



    