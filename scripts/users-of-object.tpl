<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading">';
        echo $terms[$_GET['formula']].' <b>'.$objectId.'</b>'; 
        if ($objectName) 
            echo' ('.$objectName.')'; 
        echo'
    </div>
    <form action="?users-of-object-update&obj='.$objectId.'&formula='.$_GET['formula'].$pagehrefQuery.'" method="post">
        <table>
        <tr>
        <td>';
            if ($objectIsntLiable)
                echo $terms['object-isnt-liable_'.$_GET['formula']];
            elseif ($users) {
                echo'
                <table  class="hor-separators">
                    <tr class="small center middle">
                        <td>&nbsp;</td>
                        <td>&nbsp;'.$terms['id'].'</td>
                        <td>&nbsp;'.$terms['login'].'</td>
                    	<td>&nbsp;</td>
                    </tr>';
                    foreach ($users as $user) {
                        echo'
                        <tr>
                            <td>';
                            if ($_GET['formula'] != 'user-is-subscriber') { # no need to find out higher rights 
                    	    	$higherRight = '';
                    	        if ($user['user-is-admin'])
                    	        	$higherRight = 'user-is-admin';
                                elseif ($user['user-is-editor'] && ($_GET['formula'] == 'user-sees-hidden-page' || $_GET['formula'] == 'user-is-editor-of-block'))
                    	        	$higherRight = 'user-is-editor';
                                elseif ($user['user-is-editor-of-block'] && ($_GET['formula'] == 'user-sees-hidden-page'))
                    	        	$higherRight = 'user-is-editor-of-block';
                            }

                            # Disable checkbox for users that have prior right
                            if ($higherRight)
                                echo'<input type="checkbox" checked disabled />';
                            else {  
                                echo'
                                <input type="hidden" name="users['.$user['id'].']" value="0" />
                                <input type="checkbox" name="users['.$user['id'].']"  value="1" id="'.$user['id'].'"'; if ($user[$_GET['formula']]) echo' checked'; echo' />';
                            }
                            echo'
                            </td>
                            <td class="small"><label for="'.$user['id'].'">&nbsp;'.$user['id'].'</label></td>
                            <td><label for="'.$user['id'].'"'; if (!$user['user-is-activated'] && !$user['user-is-admin'] && !$user['user-is-editor']) echo' class="gray"'; echo'><b>'.$user['login'].'</b></label>&nbsp;</td>
                            <td class="smaller gray">&nbsp;'.$terms['higher-right_'.$higherRight].'</td>
                        </tr>';
                    }
                    echo'
                </table>';
            } else {  
                echo $terms['no-users'];
            }
            echo'
            <br /><br />
            <div class="small" style="width:500px">
                <b>'.$terms['note'].'</b><br />';
                $note = 'note_'.$_GET['formula'];echo'
                '.$terms[$note].'
            </div>
            '.$submitButtons.'
        </td>
        </tr>
        </table>
    </form>
</div>';