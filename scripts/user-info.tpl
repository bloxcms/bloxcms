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
        if (Blox::info('user','user-is-admin'))
            echo'<a class="button" href="?users'.$pagehrefQuery.'" title="'.$terms['users'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />';
        if (isset($selectedUserId))
            $selectedUserIdQuery = "&selected-user-id=".$selectedUserId;
        
        /** 
         * @todo Change the URL <form action="'.$_SERVER['REQUEST_URI'].$selectedUserIdQuery.'" method="post" enctype="multipart/form-data" id="user-info"> ...when output system scripts on public pages: http://bloxcms.net/documentation/customize-admin-page.htm
         */ 
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
                        # Admin
                        if (Blox::info('user','user-is-admin'))
                            echo'<input type="text" '.$aa.' />';                
                        elseif ($selectedUserId == 'new') {# A new external user
                        	if (!Blox::info('user','id'))
                        		echo'<input type="text" '.$aa.' />';
                        	else { # If the user is already registered then do not give him to register again
                        		echo Blox::info('user','login');
                        		$isAlreadyRegistered = true;
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
                            if (Blox::info('user','user-is-admin') && $fields['email']['value'])
                                echo'<a href="mailto:'.$fields['email']['value'].'">'.$fields['email']['represent'].'</a>';
                            else
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
                    if (Blox::info('user','user-is-admin')) {
                        echo'
                        <tr>
                            <td class="name">'.$fields['regdate']['represent'].'</td>
                            <td><input type="text" name="fields[regdate]" value="'.$fields['regdate']['value'].'" disabled /></td>
                        </tr>
                        <tr>
                            <td class="name">'.$fields['visitdate']['represent'].'</td>
                            <td><input type="text" name="fields[visitdate]" value="'.$fields['visitdate']['value'].'" disabled /></td>
                        </tr>
                        <tr>
                            <td class="name">'.$fields['ip']['represent'].'</td>';
                            $humanIp = inet_ntop($fields['ip']['value']);
                            echo'
                            <td><input type="text" name="fields[ip]" value="'.$humanIp.'" disabled /><div class="smaller gray">'.gethostbyaddr($humanIp).'</div></td>
                        </tr>

                        <tr><td class="name">'.$fields['notes']['represent'].'<div class="smaller gray">'.$terms['notes2'].'</div></td>
                            <td>
                            <textarea name="fields[notes]" rows="5">'.$fields['notes']['value'].'</textarea>
                            <span style="color:red; font-size: 11px;">'.$fields['notes']['invalid-message'].'</span>
                            </td>
                        </tr>';
                    }
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