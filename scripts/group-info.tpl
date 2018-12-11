<?php
/**
 * @todo Arrange ?group-info as ?site-settings with separators. Rearrange ?user-info too
 */
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">';
if ($acceptMessage) {
    echo'
    <table>
        <tr><td><div class="alert green">'.$acceptMessage.'</div></td></tr>
        <tr><td>'.$cancelButton.'</td></tr>
    </table>';
}
elseif ($errorMessage)
{
    echo'
    <table>
        <tr><td class="red">'.$errorMessage.'</td></tr>
        <tr><td>'.$cancelButton.'</td></tr>
    </table>';
}
else
{
    if (Blox::info('user','user-is-admin'))
        echo'<a class="button" href="?groups'.$pagehrefQuery.'" title="'.$terms['groups'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />';

    if (isset($selectedGroupId))
        $selectedGroupIdQuery = '&selected-group-id='.$selectedGroupId;
        
    echo'
    <form action="?group-info'.$selectedGroupIdQuery.$pagehrefQuery.'" method="post" enctype="multipart/form-data" id="group-info">
        <input type="hidden" name="fields[id]" value="'.$fields['id']['value'].'" />
        <table>
        	<tr>
            <td colspan="2">
                <div class="heading">'.$terms['bar-title'].'<br /><b>';
                if (empty($fields['name']['value']) || $selectedGroupId == 'new')
                    echo '. . . . .';
                else
                    echo $fields['name']['value'];
                echo'</b>                        
                </div>
            </td>
            </tr>
        	<tr><td colspan="2"><span style="color:#F00">'.$notAcceptMessage.'</span></td></tr>
            <tr><td>ID</td><td>'.($selectedGroupId == 'new' ? '...' : $selectedGroupId).'</td></tr>
        	<tr>
        	<td class="name">'.$fields['name']['represent'].'<span class="red">*</span></td>
        	<td>';
            $aa = 'name="fields[name]" value="'.$fields['name']['value'].'"';
            if (Blox::info('user','user-is-admin')) {
                echo'<input type="text" '.$aa.' />';                
            } elseif ($selectedGroupId == 'new') { # A new external user
            	if (!Blox::info('user','id'))
            		echo'<input type="text" '.$aa.' />';
            	else {
                    # If the user is already registered then do not give to register to him
            		echo Blox::info('group', 'name');
            		$isAlreadyRegistered = true;
                }
            } else {
                echo'
                <input type="text" '.$aa.' disabled />
                <input type="hidden" '.$aa.' />';
            }
            echo'
            <span style="font-size: 11px;">'; 
                if ($isAlreadyRegistered) 
                    echo'<span style="color:#F00">'.$terms['is-already-registered'].'</span>'; 
                else { 
                    if (isset($_POST['fields'])) 
                        echo'<span style="color:#F00">'.$fields['name']['invalid-message'].'</span>';
                    else
                        echo $terms['group-name'];
                }
                echo'
            </span>
        	</td>
            </tr>
            <tr><td class="name">'.$fields['description']['represent'].'</td>
                <td>
                <textarea name="fields[description]" rows="5">'.$fields['description']['value'].'</textarea>
                </td>
            </tr>';
            if (Blox::info('user','user-is-admin')) {
                echo'
                <tr>
                    <td class="name">'.$fields['regdate']['represent'].'</td>
                    <td><input type="text" name="fields[regdate]" value="'.$fields['regdate']['value'].'" disabled /></td>
                </tr>';
            }
            echo'
            <tr><td colspan="2" class="small"><br /><span class="red">*</span> '.$terms['required-fields'].'</td></tr>
            <tr><td colspan="2">'.$submitButtons.'</td></tr>
        </table>
    </form>
    ';
}
    echo'
</div>
';