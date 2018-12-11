<?php
Blox::includeTerms($terms);
if (empty($cancelUrl)) {
    $cancelUrl = Blox::getPageUrl();
    /*
    if (empty($pagehref))            
        $pagehref = Blox::getPageHref();
    $cancelUrl = $pagehref;
    */
}
#<form> Opening tag is outside
$submitButtons = '    
    <div class="submit-separator"></div>
    <div style="min-width:350px">
    <button name="button-ok" type="submit" value="ok" class="submit">'.$terms['submit'].'</button>';
    if ($terms['submit-and-return'])
        $submitButtons .='<button name="button-ok" type="submit" value="submit-and-return" class="submit">'.$terms['submit-and-return'].'</button>';
    $submitButtons .='
</form>
<form action="'.$cancelUrl.'" method="post" id="cancel-form">                    
    <button type="submit" value="cancel" class="submit" data-blox-shortcut-key="27" data-blox-shortcut-url="'.$cancelUrl.'">'.$terms['cancel'].'</button>
    </div>';
    
#</form> Closing tag moved outside for beauty, because the opening tag is also outside
$template->assign('submitButtons', $submitButtons);
