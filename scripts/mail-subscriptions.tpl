<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['bar-title'].'</div>
    <table>
    <tr>
    <td>';
        if ($scriptResult == 'noSubscriptions') {
            echo'
            <div class="alert orange">'.$terms['no-subscribed-blocks'].'</div><br />
            '.$cancelButton;
        } elseif ($scriptResult == 'OldNewslettersAreNotSent') {
            echo'
            <form action="?mail-subscriptions-send&phase=send-letters'.$pagehrefQuery.'" method="post">
            <div class="alert orange">'.$terms['prev-mailing-is-not-completed'].'</div>
            '.$submitButtons.'
            </form>';
        }
        else
        {
            if ($scriptResult == 'nothingToSend') {
                $disabled=' disabled';
                echo'<div class="alert orange">'.$terms['no-new-recs'].'</div><br />';
            }
        	if ($subscriptions) {
        	    echo'
                    <form action="?mail-subscriptions-send&phase=get-recipients'.$pagehrefQuery.'" method="post">
        	        <table  class="hor-separators">
        	            <tr class="small center middle">
        	            <td>&#160;'.$terms['mail'].'</td>
        	        	<td>&#160;'.$terms['subscribed-block'].'</td>
                        <td>&#160;'.$terms['block-is-on-page'].'</td>
                        <td>&#160;'.$terms['num-of-new-recs'].'</td>
                        <td>&#160;'.$terms['num-of-subscribers'].'</td>
        	            </tr>';

        	    foreach ($subscriptions as $subscription) {
        	        echo'
                    <tr>
    	            <td align="center">
        	            <input type="hidden" name="activated['.$subscription['block-id'].']" value="0"'.$disabled.' />
        	            <input type="checkbox" name="activated['.$subscription['block-id'].']"  value="1"'; if ($subscription['activated']) echo' checked'; echo $disabled.' />
                    </td>
                    <td align="center">'.$subscription['block-id'].'</td>
                    <td><a href="?page='.$subscription['page-id'].'" target="_blank">'.$subscription['page-id'].' <span class="small">('.$subscription['page-title'].')</span></a></td>
                    <td align="center">'.$subscription['num-of-new-recs'].'</td>
                    <td align="center">'.$subscription['num-of-subscribers'].'</td>
                    </tr>';
        	    }
        	    echo'
                <tr class="small">
                <td colspan="3" align="right">'.$terms['in-total'].'</td>
                <td align="center"'; if (empty($newsletters_sums['new-recs'])) echo' class="red"'; echo'><b>'.$newsletters_sums['new-recs'].'</b></td>
                <td align="center"'; if (empty($newsletters_sums['subscribers'])) echo' class="red"'; echo'><b>'.$newsletters_sums['subscribers'].'</b></td>
                </tr>
                </table>';

                if ($scriptResult=='nothingToSend') {
                    echo'
                    </form>
                    '.$cancelButton;
                } else {
                    echo'
                    <br />
                    <table class="small" style="border-collapse:separate; border-spacing:4px;">
                        <tr><th colspan="2">'.$terms['letter-params'].'<br /><td>&#160;</td>
                    	<tr><td>'.$terms['letter-subject'].'</td><td><input type="text" name="newsletter-params[subject]" value="'.$newsletterParams['subject'].'" size="40" /></td><td>&#160;</td></tr>
                        <tr><td>'.$terms['from_email'].'<span class="red">*</span></td><td><input type="text" name="newsletter-params[email-from]" value="'.$newsletterParams['email-from'].'" size="40" /></td><td><span class="smaller">'.$terms['from_note'].'</span></td></tr>
                        <tr><td>'.$terms['from_name'].'</td><td><input type="text" name="newsletter-params[name_from]" value="'.$newsletterParams['name_from'].'" size="40" /></td><td>&#160;</td></tr>
                    </table>
                    <br />
                    <input type="checkbox" name="test-sending"  value="1" /> '.$terms['mailing_trial'].' <span class="small gray">('.$terms['mailing-only-to'].' <b>'.Blox::info('user','login').'</b>)</span>
                    '.$submitButtons.'
                    </form>';
                }
        	} else
                echo $cancelButton;
        }
    echo'
    <br /><br />
    </td>
    </tr>
    </table>
</div>';