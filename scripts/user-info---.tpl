<?php
/**
 * @todo Arrange ?user-info as ?site-settings or ?edit-button-setting- with separators. Rearrange ?group-info too
 */
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<style>
    .blox-edit input,
    .blox-edit textarea {width:240px}
</style>
<div class="blox-edit">';
    if ($acceptMessage) {
        echo'
        <table>
            <tr><td><div class="alert green">'.$acceptMessage.'</div></td></tr>
            <tr><td>'.$cancelButton.'</td></tr>
        </table>';
    } elseif ($errorMessage) {
        echo'
        <table>
            <tr><td class="red">'.$errorMessage.'</td></tr>
            <tr><td>'.$cancelButton.'</td></tr>
        </table>';
    } else {  
        if (isset($selectedUserId))
            $selectedUserIdQuery = "&selected-user-id=".$selectedUserId;
        echo'
        <table>
        <tr>
        <td>
            <form action="?user-info'.$selectedUserIdQuery.$pagehrefQuery.'" method="post" enctype="multipart/form-data" id="user-info">
                <input type="hidden" name="fields[id]" value="'.$fields['id']['value'].'" />
                <table>
                    <tr>
                    	<td colspan="2">
                            <div class="heading">';
                            if ($selectedUserId == "new")
                                echo $terms['bar-title-new-user'];
                            else {
                                echo $terms['bar-title'].'<br /><b>';
                                if (empty($fields['login']['value']) || !Blox::info('user','id'))
                                    echo '. . . . .';//$terms['new-user']
                                else
                                    echo $fields['login']['value'];
                                echo'</b>';
                            }
                            echo'           
                            </div>
                        </td>
                    </tr>   
                	<tr>
                        <td colspan="2"><span style="color:red">'.$notAcceptMessage.'</span></td>
                    </tr>
                	<tr>
                        <td class="name">'.$fields['login']['represent'].'<span class="red">*</span></td>
                        <td>';
                        $aa = 'name="fields[login]" value="'.$fields['login']['value'].'"';
                        if ($selectedUserId == 'new') {# A new external user
                        	if (Blox::info('user','id')) { # If the user is already registered then do not give him to register again
                        		echo Blox::info('user','login');
                        		$isAlreadyRegistered = true;
                        	} else { 
                                echo'<input type="text" '.$aa.' />';
                            }

                        } else {
                            echo'
                            <input type="text" '.$aa.' disabled />
                            <input type="hidden" '.$aa.' />';
                        }
                        echo'
                        <span style="color:red; font-size: 11px;">'; if ($isAlreadyRegistered) echo $terms['is-already-registered']; else echo $fields['login']['invalid-message']; echo'</span>
                    	</td>
                    </tr>';
                    if ($selectedUserId == 'new') {
                        echo'
                    	<tr>
                        	<td class="name">'.$fields['password']['represent'].'<span class="red">*</span></td>
                        	<td><input type="text" name="fields[password]" value="'.$fields['password']['value'].'" /> <span style="color:red; font-size: 11px;">'.$fields['password']['invalid-message'].'</span></td>
                        </tr>';
                        }
                    echo'
                	<tr>
                        <td class="name">';
                            echo $fields['email']['represent'];
                            echo'<span class="red">*</span>
                        </td>
                    	<td>
                            <input type="text" name="fields[email]" value="'.$fields['email']['value'].'" />
                            <span style="color:red; font-size: 11px;">'.$fields['email']['invalid-message'].'</span>
                        </td>
                    </tr>
                	<tr>
                        <td class="name">'.$fields['personalname']['represent'].'</td><td><input type="text" name="fields[personalname]" value="'.$fields['personalname']['value'].'" /></td>
                    </tr>
                	<tr>
                        <td class="name">'.$fields['familyname']['represent'].'</td><td><input type="text" name="fields[familyname]" value="'.$fields['familyname']['value'].'" /></td>
                    </tr>';


                    if ($selectedUserId == 'new') {
                    echo'
                    <tr><td style="vertical-align:middle">'.$fields['captcha']['represent'].'</td>
                        <td><img src="'.Captcha::getImageUrl('fields[captcha]', ['color'=>'ff0000', 'num-of-chars'=>3, 'font-size'=>14]).'" style="vertical-align:middle"><input style="width: 99px;" type="text" name="fields[captcha]" />
                            <span style="color:red; font-size: 11px;">'.$fields['captcha']['invalid-message'].'</span>
                        </td>
                    </tr>';
                    }
                    echo'
                    <tr><td colspan="2" class="small"><br /><span class="red">*</span> '.$terms['required-fields'].'</td></tr>
                    <tr><td colspan="2">'.$submitButtons.'</td></tr>
                </table>
            </form>
        </td>
        <td class="blox-vert-sep">&nbsp;</td>
        <td class="warnings">';
        if ($selectedUserId == 'new') {
            echo'<p><a class="button" rel="nofollow" href="'.Blox::info('site','url').'/?password&login='.Blox::info('user','login').$pagehrefQuery.'">'.$terms['forgot-password'].'</a></p>';
            if (Blox::info('user'))
                echo'<p><a class="button" rel="nofollow" href="'.Blox::info('site','url').'/?logout'.$pagehrefQuery.'">'.$terms['logout'].'</a></p>';
        } elseif ($passwordUpdateUrl) {
            echo'
            <p><a class="button" rel="nofollow" href="'.$passwordUpdateUrl.'">'.$terms['update-password'].'</a></p>';
        }
        echo'
        </td>
        </tr>
        </table>';
    }
    echo'
</div>';