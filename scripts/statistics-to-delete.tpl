<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?statistics'.$pagehrefQuery.'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />    
    <div class="heading">'.$terms['bar-title'].'</div>
    <form action="?statistics-delete'.$pagehrefQuery.'" method="post">
        <table>
        <tr><td width="200">'.$terms['input'].'</td><td style="padding-top:7px; vertical-align:top"><input name="date" value="'.date("Y-m-d").'" type="text" size="10" onclick="note.style.display=\'none\'" />';
        //<div id="note" class="smaller">'.$terms['older-than-a-year'].'</div>
        echo'</td></tr>
        <tr><td colspan="2">'.$submitButtons.'</td></tr>
        </table>
    </form>
</div>';