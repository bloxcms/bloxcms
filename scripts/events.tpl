<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?statistics'.$pagehrefQuery.'" title=""><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>&#160;
    <a class="button" href="?events-show'.$pagehrefQuery.'" title="">'.$terms['list'].'</a></td>
    <div class="heading">'.$terms['heading'].'</div>
    <div class="explanation"><br />'.$terms['represent'].'</div>
    <form action="?event-insert'.$pagehrefQuery.'" method="post">
        <table>
        <tr><td colspan="2"></td></tr>
        <tr><td>'.$terms['date'].' </td><td><input name="date" value="'.$today.'" type="text" size="10" /></td></tr>
        <tr><td>'.$terms['description'].' </td><td><input name="description" type="text"  size="30" /></td></tr>
        <tr><td colspan="2">'.$submitButtons.'</td></tr>
        </table>
    </form>
</div>';