<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading" style="margin-bottom:5px">';
        if ('report' == $_GET['phase'] || 'delete-letters' == $_GET['phase'])
            ;
        else
            echo'<div class="loading"></div>';
        echo $terms['heading'];
        echo'
    </div><br />';
    echo'
    <table>
    <tr>
    <td>';
        echo $reportsHtm;
        if ('get-recipients' == $_GET['phase']) {
            $newPhase = 'createNewsletters';
        } elseif ('createNewsletters' == $_GET['phase']) {
            $newPhase = 'send-letters';
        } elseif ('send-letters' == $_GET['phase']) {
            $newPhase = 'report';
        } elseif ('report' == $_GET['phase']) {
            if ($testUserParams)
                echo'<div>'.sprintf($terms['mail-test'], '<b>'.$testUserParams['login'].'</b>').'</div>';
            if (empty($numOfunsent['failed']) && empty($numOfunsent['untouched'])) {
                echo'<div class="alert green">'.$terms['mailing-completed'].'</div><br />';
                echo '.$cancelButton.';
            } else {
                echo'
                <form action="?mail-subscriptions-send&phase=send-letters'.$pagehrefQuery.'" method="post">
                    <div class="alert orange">'.$terms['mailing-not-completed'].'</div><br />
                    <table>';
                        if ($numOfunsent['failed']) 
                            $atribute = 'checked';
                        else 
                            $atribute = 'disabled';
                        echo'<tr><td><input type="checkbox" name="send-again[failed]" value="1" $atribute /><td>'.$numOfunsent['failed'].' <td>&ndash; '.$terms['num-of-recepients1'].'</td>';
                        if ($numOfunsent['untouched']) 
                            $atribute = 'checked';
                        else 
                            $atribute = 'disabled';
                        echo'
                        <tr><td><input type="checkbox" name="send-again[untouched]" value="1" $atribute /><td>'.$numOfunsent['untouched'].' <td>&ndash; '.$terms['num-of-recepients2'].'</td>
                    </table>
                    <br />'.$terms['send-again'].'<br />
                    '.$submitButtons.'
                </form>';
                Report::reset();
            }
        } elseif ('delete-letters' == $_GET['phase']){
            echo'
            '.$terms['mailing-deleted'].'
            '.$cancelButton;
        }
        echo'</td>';
        # This mailing will no longer be sent
        if ('report' == $_GET['phase'] && !(empty($numOfunsent['failed']) && empty($numOfunsent['untouched']))) {
            echo'
            <td class="blox-vert-sep">&nbsp;</td>
            <td>• <a href="?mail-subscriptions-send&phase=delete-letters'.$pagehrefQuery.'">'.$terms['delete-mailing'].'</a></td>'; # unsent
        }
        echo'
    </td>
    </tr>
    </table>';
echo'
</div>';
if ($newPhase) {
    echo'
    <script type="text/javascript">
            location.href="?mail-subscriptions-send&phase=$newPhase'.$pagehrefQuery.'"; target="blank";
    </script>';
}