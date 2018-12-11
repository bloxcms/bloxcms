<?php 
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<div class="heading">'.$terms['heading'].' <b>'.$blockInfo['id'].'</b> ('.$blockInfo['tpl'].')</div>
<table>
    <tr><td>
    <noscript><div class="alert orange">'.$terms['no-js'].'</div><br /></noscript>
    <form action="?mail-block-send&block='.$blockInfo['id'].'&phase=get-recipients'.$pagehrefQuery.'" method="post" enctype="multipart/form-data" onsubmit="return validateForm();">
    <div class="section">
    <div class="section-header">'.$terms['list-of-recipients'].'</div>
    <table  class="hor-separators middle">
        <tr class="small center">
            <td>'.$terms['recipient-status'].'</td>
            <td class="blox-vert-sep">&#160;</td>
        	<td>'.$terms['include'].'</td>
            <td>'.$terms['exclude'].'</td>
            <td>'.$terms['dont-use'].'</td>
        </tr>';
        $usersStatuses = ['activated', 'admins', 'editors', 'editors-of-block', 'editor-of-records', 'guests', 'subscribers'];
        $statusesStr = '';
        foreach ($usersStatuses as $status)
            $statusesStr .= ','.$status;
        $statusesStr = substr($statusesStr, 1);
        ?>
        
        <script type="text/javascript">
            function setRadioChecks(value, disableOnlyIncs)
            {
                var statuses = [<?=$statusesStr?>];
                for (var j = 0; j < statuses.length; j++) {
                    aa = document.getElementsByName('recipients['+statuses[j]+']');
                    for (var i = 0; i < aa.length; i++) {
                        if (value)
                            aa[i].disabled = true;
                        else
                            aa[i].disabled = false;
                    }
                    /* Enable "exclude" and "dont-use" */
                    if (disableOnlyIncs && value) {
                        for (var i = 1; i < aa.length; i++)
                            aa[i].disabled = false;
                    }
                }
            }
        </script>
        <?php 
    echo'
    <tr>
        <td>'.$terms['registered'].'</td>
        <td>&#160;</td>
        <td align="center"><input type="radio" name="recipients[registered]" value="inc" onClick="setRadioChecks(1,1)" /></td>
        <td align="center"><input type="radio" name="recipients[registered]" value="exc" onClick="setRadioChecks(1,0)" /></td>
        <td align="center"><input type="radio" name="recipients[registered]" value="" onClick="setRadioChecks(0,0)" checked /></td>
    </tr>';

    foreach ($usersStatuses as $status) {
        echo'
        <tr>
            <td>'.$terms[$status].'</td>
            <td>&#160;</td>
            <td align="center"><input type="radio" name="recipients['.$status.']" value="inc" /></td>
            <td align="center"><input type="radio" name="recipients['.$status.']" value="exc" /></td>
            <td align="center"><input type="radio" name="recipients['.$status.']" value="" checked /></td>
        </tr>
        ';
    }
        echo'
        <tr>
            <td>'.$terms['upload-list'].' 1<br /><input name="uploaded1" type="file"  size="10" class="smaller" /></td>
            <td>&#160;</td>
            <td align="center"><input type="radio" name="recipients[uploaded1]" value="inc" /></td>
            <td align="center"><input type="radio" name="recipients[uploaded1]" value="exc" /></td>
            <td align="center"><input type="radio" name="recipients[uploaded1]" value="" checked /></td>
        </tr>
        <tr>
            <td>'.$terms['upload-list'].' 2<br /><input name="uploaded2" type="file"  size="10" class="smaller" /></td>
            <td>&#160;</td>
            <td align="center"><input type="radio" name="recipients[uploaded2]" value="inc" /></td>
            <td align="center"><input type="radio" name="recipients[uploaded2]" value="exc" /></td>
            <td align="center"><input type="radio" name="recipients[uploaded2]" value="" checked /></td>
        </tr>
    </table>
    </div>

    <br />
    <div class="section">
        <div class="section-header">'.$terms['letter-params'].'</div>
        <table class="small" style="border-collapse:separate; border-spacing:4px;">
        	<tr><td>'.$terms['letter-subject'].'</td><td><input type="text" name="blockletter-params[subject]" value="'.$blockletterParams['subject'].'" size="40" /></td><td>&#160;</td></tr>
            <tr><td>'.$terms['from_email'].'<span class="red">*</span></td><td><input type="text" name="blockletter-params[email-from]" value="'.$blockletterParams['email-from'].'" id="blockletter-params[email-from]" size="40" /></td><td><span class="smaller">'.$terms['from_note'].'</span></td></tr>
            <tr><td>'.$terms['from_name'].'</td><td><input type="text" name="blockletter-params[name_from]" value="'.$blockletterParams['name_from'].'" size="40" /></td><td>&#160;</td></tr>
            <tr><td>'.$terms['host'].'</td><td><input type="text" name="blockletter-params[mail-server-host]" value="'.$blockletterParams['mail-server-host'].'" size="40" /></td><td><span class="smaller">'.$terms['host-note'].'</span></td></tr>
        </table>
    </div>
    &#160;
    <input type="hidden" name="blockletter-params[block-id]" value="'.$blockInfo['id'].'" />
    <input type="hidden" name="blockletter-params[parent-block-id]" value="'.$blockInfo['parent-block-id'].'" />
    <input type="hidden" name="blockletter-params[tpl]" value="'.$blockInfo['tpl'].'" />

    <div class="section">
        <div class="section-header">'.$terms['send_interval'].'</div>
        <table class="small" style="border-collapse:separate; border-spacing:4px;">
        	<tr><td>'.$terms['send-number-of'].' </td><td><input type="text" name="blockletter-params[numberOfLetters]" value="'.$blockletterParams['number-of-letters'].'" size="5" />'.$terms['send-of-letters'].'</td></tr>
            <tr><td>'.$terms['send-with-interval'].'</td><td><input type="text" name="blockletter-params[interval]" value="'.$blockletterParams['interval'].'" size="5" />'.$terms['send_sec'].'</td></tr>
            <tr><td>'.$terms['send_randomly'].'</td><td><input type="hidden" name="blockletter-params[randomize]" value="0" /><input type="checkbox" name="blockletter-params[randomize]" value="1"'; if ($blockletterParams['randomize']) echo' checked'; echo' /></td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-header">'.$terms['mailing_type'].'</div>
        <input type="radio" name="sending-option" id="sending-option-normal" value="normal" checked /> <label for="sending-option-normal">'.$terms['mailing_normal'].'</label>
        <br />
        <input type="radio" name="sending-option" id="sending-option-test" value="test" /> <label for="sending-option-test">'.$terms['mailing_trial'].' <span class="small gray">('.$terms['mailing-only-to'].' <b>'.Blox::info('user','login').'</b>)</span></label>
        <br />
        <input type="radio" name="sending-option" id="sending-option-create-file" value="create-file" /> <label for="sending-option-create-file">'.$terms['mailing-build-list'].'</label>
    </div>
    '.$submitButtons.'
    </form>
    </td></tr>
</table>
</div>';
?>

<script type="text/javascript">
    function validateForm()
    {
        var email = document.getElementById('blockletter-params[email-from]').value;
        if ((/(.+)@(.+){2,}\.(.+){2,}/.test(email)) || email=='' || email==null)
            return true;
        else {
            alert(<?=$terms['noncorrect-email']?>);
            return false;
        }
    }
</script>