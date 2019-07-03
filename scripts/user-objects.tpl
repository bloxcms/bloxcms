<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<table>
<tr>
<td>';
    if ($mode=='editByAdmin') {
        echo'
        <a class="button" href="?users'.$pagehrefQuery.'" title="'.$terms['back'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
      	<form action="?user-objects-update&formula='.$_GET['formula'].'&selected-user-id='.$_GET['selected-user-id'].$pagehrefQuery.'" method="post">
        <div class="heading" style="margin-bottom:5px"><span style="font-weight:normal">'.$headline.'</span> <b>'.$login.'</b></div>
        <br />';
    }
    # user-is-editor-of-block
    if ('user-is-editor-of-block' == $_GET['formula'] || 'user-is-editor-of-records' == $_GET['formula'] || 'user-is-subscriber' == $_GET['formula']) {
        echo'
        <table  class="hor-separators">
            <tr class="small center middle">
            <td>'.$terms['block'].'</td>
            <td>&nbsp; '.$terms['tpl'].'</td>
            <td>&nbsp; '.$terms['on-pages'].'</td>
            </tr>';
            if ($userObjects) {
                foreach ($userObjects as $objectOfUser) {
                    echo'
                    <tr>
                    <td style="font-weight: bold; white-space: nowrap">';
                    if ($mode=='editByAdmin') {
                        echo'
                        <input type="hidden"    name="user-objects['.$objectOfUser['object-id'].']" value="0" />
                        <input type="checkbox"  name="user-objects['.$objectOfUser['object-id'].']" value="1" checked />';
                    }
                    echo'
                    <span'; if ($objectOfUser['delegated-id']) echo' style="color:#900"'; echo'>'.$objectOfUser['object-id'].'</span>
                    </td>
                    <td style="font-weight: normal; white-space: nowrap">&nbsp;
                        '.$objectOfUser['tpl'].'
                    </td>
                    <td style="font-weight: normal">&nbsp;
                        <a href="?page='.$objectOfUser['container-page']['id'].'&bound-block='.$objectOfUser['object-id'].'#bound-block'.$objectOfUser['object-id'].'" class="small" target=_blank>'.$objectOfUser['container-page']['id'].'. '.$objectOfUser['container-page']['title'].'</a>
                    </td>
                    </tr>';
                }
            }
            echo'
        </table>';
        if ($mode=='editByAdmin') # Do not put this into the table, as there is a border
            if ($note = $terms['note_'.$_GET['formula']])
                echo'<br /><div class="small" style="width:500px"><b>'.$terms['note'].'</b><br />'.$note.'</div>';
    }
    # Guest
    elseif ('user-sees-hidden-page' == $_GET['formula'])
    {
        echo'
        <table  class="hor-separators">
            <tr class="small center middle">
            <td>'.$terms['page'].'</td>
            <td>&nbsp; '.$terms['page-title'].'</td>
            </tr>';
            if ($userObjects) {
                foreach ($userObjects as $objectOfUser) {
                    echo'
                        <tr>
                        <td style="font-weight: bold">';
                            if ($mode=='editByAdmin') {
                                echo'
                                <input type="hidden"    name="user-objects['.$objectOfUser['object-id'].']" value="0" />
                                <input type="checkbox"  name="user-objects['.$objectOfUser['object-id'].']" value="1" checked />';
                            }
                            echo'
                            <a href="?page='.$objectOfUser['object-id'].'" target=_blank>'.$objectOfUser['object-id'].'</a>
                        </td>
                        <td style="font-weight: normal">&nbsp;
                            '.$objectOfUser['title'].'
                        </td>
                        </tr>';
                }
            }
            echo'
        </table>';
        if ($mode=='editByAdmin') # Do not put this into the table, as there is a border
            echo'<br /><div class="small" style="width:500px"><b>'.$terms['note'].'</b><br />'.$terms['guest-access'].'</div>';
    }
    
    if ($mode=='editByAdmin') {
        echo'
        '.$submitButtons.'
        </form>';
    } else {
        echo $cancelButton;
    }
    echo'
</td>
</tr>
</table>
</div>';