<?php

Blox::includeTerms($terms);
$pageUrl = Blox::getPageUrl();
/*
if (empty($pagehref))            
    $pagehref = Blox::getPageHref();
*/
$cancelButton = '
<div class="submit-separator"></div>        
<form action="'.$pageUrl.'" method="post" id="cancel-form">
    <button type="submit" class="submit" data-blox-shortcut-key="27" data-blox-shortcut-url="'.$pageUrl.'">'.$terms['cancel'].'</button>
</form>';
$template->assign('cancelButton', $cancelButton);
