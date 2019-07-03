<?php
Blox::includeTerms($terms);
if (empty($cancelUrl))
    $cancelUrl = Blox::getPageUrl();
# Delete new rec if the form will be canceled
if (isset($_GET['edit']) && $_GET['rec']=='new' && $dat['rec'])
    $cancelUrl = '?recs-delete&which='.$dat['rec'].'&block='.$blockInfo['id'].'&rec='.$dat['rec'].$pagehrefQuery; //$filtersQuery

#<form> Opening tag lays outside
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
#</form> Closing tag moved outside for beauty, because the opening tag lays also outside

$template->assign('submitButtons', $submitButtons);
