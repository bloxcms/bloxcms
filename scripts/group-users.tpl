<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?groups'.$pagehrefQuery.'" title="'.$terms['groups'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
    <div class="heading">';
        echo $terms['heading'].' <b>'.$groupInfo['name'].'</b>';
    echo'
    </div>
    <table>
    <tr>
    <td>
      	<form action="?group-users-update&selected-group-id='.$groupInfo['id'].$pagehrefQuery.'" method="post"  id="users">
            <table class="hor-separators">
            	<tr class="small center top">
                    <td>'.$terms['is-member'].'</td>
                	<td>'.$terms['user-is-activated'].'</td>
                	<td>'.$terms['name'].'</td>
                </tr>';
                if ($users) {   
                    foreach ($users as $i => $user) {
                        $userId = $user['id'];
                        echo'
                    	<tr>
                        	<td>';
                                if ($adminUsers[$userId])
                                    echo'<input type="checkbox" name="" disabled /><b class="gray">'.$user['login'].'</b>';
                                else {
                                    echo'
                                    <input type="hidden" name="users['.$userId.'][is-member]" value="0" />
                                    <label for="user-'.$userId.'" title="'.($groupUsers[$userId] ? $terms['exclude-from-members'] : $terms['take-in-members']).'">
                                        <input type="checkbox" name="users['.$userId.'][is-member]" value="1"'.($groupUsers[$userId] ? ' checked' : '').' id="user-'.$userId.'" /><b>'.$user['login'].'</b>                
                                    </label>';
                                }
                            echo'
                            </td>';
                            # @todo After transfer 'activated' to the table "users, replace this code by the underlying one as in groups-of-user
                            echo'
                        	<td class="small'.($activatedUsers[$userId] ? '' : ' gray').'" align="center">'; if (!$adminUsers[$userId]) echo $activatedUsers[$userId] ? $terms['activated'] : $terms['unactivated']; echo'</td>
                    		<td class="small'.($activatedUsers[$userId] ? '' : ' gray').'">'.$user['personalname'].' '.$user['familyname'].'&nbsp;</td>';
                            /**    	
                        	<td class="small'.($user['activated'] ? '' : ' gray').'">'.($user['activated'] ? $terms['activated'] : $terms['unactivated']).'</td>
                    		<td class="small'.($user['activated'] ? '' : ' gray').'">'.$user['description'].' &nbsp;</td>
                            */
                            echo'
                        </tr>';
                    }
                }
                echo'
            </table>
            '.$submitButtons.'
        </form>
    </td>
    </tr>
    </table>
</div>';