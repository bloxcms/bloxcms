<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<div class="heading">
    '.$terms[$_GET['formula']].' <b>'.$objectId.'</b>';
    if ($objectName)
        echo' ('.$objectName.')'; 
    echo' 
</div>
<form action="?groups-of-object-update&obj='.$objectId.'&formula='.$_GET['formula'].$pagehrefQuery.'" method="post">
<table><tr><td>';
if ($noGroupIdField)
	echo'<div style="width:500px; color:red">'.$terms['no-group-id-field'].'<br /></div>';
else {
	if ($objectIsntLiable)
        echo $terms['object-isnt-liable_'.$_GET['formula']];
	elseif ($groups) {
	    echo'
	    <table class="hor-separators">
            <tr class="small center middle">
            <td>&#160;</td>
            <td>&#160;'.$terms['name-of-group'].'</td>
        	<td>&#160;</td>
            </tr>';
    	    foreach ($groups as $group) {
    	        echo'<tr><td>';
                # In the formula "group-is-subscriber" all users have equak rights, so do not calculate higher rights
                if ($_GET['formula'] != 'group-is-subscriber') {
        	    	$higherRight = '';
                    if ($group['group-is-editor'] && ($_GET['formula'] == 'group-sees-hidden-page' || $_GET['formula'] == 'group-is-editor-of-block')) # todo with $_GET['formula']
        	        	$higherRight = 'group-is-editor';
                    elseif ($group['group-is-editor-of-block'] && ($_GET['formula'] == 'group-sees-hidden-page'))
        	        	$higherRight = 'group-is-editor-of-block';
                }
                # Disable the checkbox for users that have prior right
    	        if ($higherRight)
    	            echo'<input type="checkbox" checked disabled />';
    	        else {
    	            echo'
    	            <input type="hidden" name="groups['.$group['id'].']" value="0" />
    	            <input type="checkbox" name="groups['.$group['id'].']"  value="1" id="'.$group['id'].'"'; if ($group[$_GET['formula']]) echo' checked'; echo' />';
    	        }
    	        echo'
	            </td>
	            <td><label for="'.$group['id'].'"'; if (!$group['activated'] && !$group['group-is-editor']) echo' class="gray"'; echo'>'.$group['name'].'</label>&#160;</td>
	            <td class="smaller gray">&#160;'.$terms['higher-right_'.$higherRight].'</td>
	            </tr>';
    	    }
    	    echo'
        </table>';
	} else
	    echo $terms['no-groups'];
}
    echo'
    <br /><br />
    <div class="small" style="width:500px">
        <b>'.$terms['note'].'</b><br />';
        $note = 'note_'.$_GET['formula'];echo'
        '.$terms[$note].'
    </div>
    '.$submitButtons.'
</td></tr></table>
</form>
</div>';