<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?events-show'.$pagehrefQuery.'" title=""><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
    <div class="hor-separator"></div>
    <div class="heading">'.$terms['bar-title'].'</div>
  	<form action="?events-show'.$pagehrefQuery.'" method="post">
        <input type="hidden" name="event[id]" value="'.$event['id'].'" />
        <table>
            <tr><td colspan="2"><b>'.$terms['event'].' '.$event['id'].'</b></td></tr>
            <tr><td>'.$terms['date'].'</td><td><input name="event[date]" value="'.$event['date'].'" type="text" size="10" /></td></tr>
            <tr><td>'.$terms['description'].'</td><td><input name="event[description]" value="'.$event['description'].'" type="text"  size="30" /></td></tr>
            <tr><td colspan="2">'.$submitButtons.'</td></tr>
        </table>
    </form>
</div>';