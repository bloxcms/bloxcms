<?php
# Remind password
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['bar-title'].'</div>';
    if (isset($_GET['login'])) {
        echo'
        <form action="'.Blox::info('site','url').'/?password-restore'.$pagehrefQuery.'" method="post">
            <table>
            <tr><th colspan="2" style="padding-bottom:7px">'.$terms['enter'].'</th></tr>
            <tr><td>'.$terms['login'].'</td><td><input name="login" value="'.$_GET['login'].'" type="text" /></td></tr>
            <tr><td>'.$terms['email'].'</td><td><input name="email" value="" type="text" /></td></tr>
            <tr><td colspan="2">'.$submitButtons.'</td></tr>
            </table>
        </form>';
    } else {
        echo'
        <table><tr><td>';
            if ($acceptMessage)
                echo'<div class="alert green">'.$acceptMessage.'</div>';
            if ($errorMessage)
                echo'<div class="alert orange">'.$errorMessage.'</div>';
            echo'
    	    '.$cancelButton.'
        </td></tr></table>';
    }
    echo'
</div>';