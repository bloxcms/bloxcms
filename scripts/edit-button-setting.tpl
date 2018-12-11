<?php
# DEPRECATED. USED AS SAMPLE ONLY
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading" style="margin-bottom:5px">'.$terms['heading'].'</div>
    <div class="subheading">'.$terms['subheading'].'</div>
    <div>&nbsp;</div>
    <form action="?edit-button-setting-update&block='.(int)$_GET['block'].$pagehrefQuery.'" method="post">
        <table class="hor-separators top">
    	<tr>
        	<td class="name">'.$terms['top']['name'].'</td>
        	<td><input name="data[top]" value="'.$data['top'].'" type="text" size="10" /></td>
            <td class="note">'.$terms['top']['note'].'</td>
        </tr>
    	<tr>
        	<td class="name">'.$terms['left']['name'].'</td>
        	<td><input name="data[left]" value="'.$data['left'].'" type="text" size="10" /></td>
            <td class="note">'.$terms['left']['note'].'</td>
        </tr>
    	<tr>
        	<td class="name">'.$terms['z-index']['name'].'</td>
        	<td><input name="data[z-index]" value="'.$data['z-index'].'" type="text" size="10" /></td>
            <td class="note">'.$terms['z-index']['note'].'</td>
        </tr>
        </table>
        <div class="notes">'.$terms['notes'].'</div>
        '.$submitButtons.'
    </form>
</div>';