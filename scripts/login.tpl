<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    
?>    
<div class="blox-edit">
<div class="heading"><?=$terms['bar-title'].Admin::tooltip('login.htm')?></div>
    <table>
    <tr>
    <td>
    <form action="?login<?=$pagehrefQuery ?>" method="post"  id="login">
        <table>
            <tr><td><?=$terms['login']?> </td><td><input name="data[login]" value="<?=$data['login']?>" type="text"></td>
                <td rowspan="2" style="vertical-align:middle; color:red; font-size: 11px;"><?=$errors['auth']?></td>
            </tr>
            <tr><td><?=$terms['password']?> </td><td><input name="data[password]" value="<?=$data['password']?>" type="password"></td></tr>
            <?php
            if ($errors) { # If there's error we begin to use captcha
            echo'
            <tr><td style="vertical-align:middle">'.$terms['captcha'].'</td>
                <td><img src="'.Captcha::getImageUrl('data[captcha]').'" style="vertical-align:middle"><input style="width: 99px;" type="text" name="data[captcha]" />
                <td style="color:red; font-size: 11px;">'.$errors['captcha'].'</td>
                </td>
            </tr>';
            }
            ?>
            <input type="hidden" name="data[save-password]" value="0" />
            <tr><td></td><td colspan="2" class="small"><input type="checkbox"  name="data[save-password]"  value="1"<?php if ($data['save-password']) echo' checked'?> id="login1"><label for="login1"><?=$terms['save-password']?></label></td></tr>
            <tr><td colspan="3"><?=$submitButtons ?></td></tr>
        </table>
    </form>
    </td>
    <td class="blox-vert-sep">&nbsp;</td>
    <td class="warnings">
        <p><?=($allowOuterRegistration) ? '<a class="button" rel="nofollow" href="'.Blox::info('site','url').'/?user-info&selected-user-id=new'.$pagehrefQuery.'">'.$terms['registration'].'</a><br><br>' : ''?></p>
        <p><a class="button" rel="nofollow" href="<?=Blox::info('site','url') ?>/?password&login=<?=$data['login'].$pagehrefQuery ?>"><?=$terms['forgot-password']?></a></p>
    </td>
    </tr>
    </table>
</div>