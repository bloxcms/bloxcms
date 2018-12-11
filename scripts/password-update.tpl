<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading" style="margin-bottom:5px">'.$terms['heading'].'</div>
    <div class="subheading">'.$terms['subheading'].'</div>';
    if ($errors) {
        echo'
        <div class="red" style="margin:15px 0px">';
            foreach ($errors as $k=>$v)
                echo $v;
            echo'
        </div>';
    }
    echo'
    <form action="?password-update&step=update'.$pagehrefQuery.'" method="post">
        <input name="data[code]" value="'.($data['code']?:$_GET['code']).'" type="hidden" />
        <table class="hor-separators top">
    	<tr>
        	<td class="name">'.$terms['login'].'</td>
        	<td colspan="2"><b>'.$userInfo['login'].'</b></td>
        </tr>
    	<tr>
        	<td class="name">'.$terms['new-password'].'</td>
        	<td><input name="data[new-password]" value="'.$data['new-password'].'" type="password" size="16" /></td>
            <td>'.$notes['new-password'].'</td>
        </tr>
    	<tr>
        	<td class="name">'.$terms['new-password-2'].'</td>
        	<td><input name="data[new-password-2]" value="'.$data['new-password-2'].'" type="password" size="16" /></td>
            <td>'.$notes['new-password-2'].'</td>
        </tr>
        </table>
        <div class="notes">'.$terms['notes'].'</div>
        '.$submitButtons.'
    </form>
</div>';
