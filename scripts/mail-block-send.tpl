<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">';
    echo'<div class="heading" style="margin-bottom:5px">';
        if ('report' == $_GET['phase'] || 'delete-letter' == $_GET['phase'] || ('get-recipients' == $_GET['phase']) && $scriptResult > 2)
            ;
        else
            echo'<div class="loading"></div>';
        echo $terms['heading'].' <b>'.$blockletterParams['block-id'].'</b> ('.$blockletterParams['tpl'].')';
    echo'</div><br />';
    echo'<table><tr><td>';
        echo $reportsHtm;
        if ('get-recipients' == $_GET['phase']) {
            if ($scriptResult == 0)
                $newPhase = 'create-letter';
            elseif ($scriptResult > 2) {
                if ($scriptResult === 3)//csvFileNotCreated
                    echo'<div class="alert red">'.$terms['file-create-failed'].'</div><br />';
                elseif ($scriptResult === 6)//no-message
                    echo'<div class="alert red">'.$terms['no-letter'].'</div><br />';
                else {
                    $formatBytes = function($size){
                        $units = [' B', ' KB', ' MB', ' GB', ' TB'];
                        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
                        return round($size, 2).$units[$i];
                    };
                    $fbase = Blox::info('site','url').'/temp/mail-block-send-download/recipients';
                    echo'<div>'.$terms['files-with-list'].'</div><a href="'.$fbase.'.csv">• recipients.csv</a> <span class="gray small">('.$formatBytes(filesize('.$fbase.'.csv')).')</span>';
                    if ($scriptResult === 4)//zippedCsvFileIsCreated
                        echo'<br /><a href="'.$fbase.'.zip">• recipients.zip</a> <span class="gray small">('.$formatBytes(filesize('.$fbase.'.zip')).')</span>';
                    echo'<br />';
                }
                echo $cancelButton;
                Report::reset();}
            else
                $newPhase = 'report';
        } elseif ('create-letter' == $_GET['phase']) {
            $newPhase = 'send-letter';
            echo $cancelButton;
        } elseif ('send-letter' == $_GET['phase']){
            if ($scriptResult == 1)
                $newPhase = 'send-letter';
            else ##...if ($scriptResult == 0)
                $newPhase = 'report';
            echo $cancelButton;
        } elseif ('report' == $_GET['phase']) {
            if (empty($numOfunsent['failed']) && empty($numOfunsent['untouched'])){
                echo'<div class="alert green">'.$terms['mailing-completed'].'</div><br />';
                echo $cancelButton;}
            else {
                echo'<form action="?mail-block-send&block='.$_GET['block'].'&phase=send-letter'.$pagehrefQuery.'" method="post">';
                echo'<div class="alert orange">'.$terms['prev-mailing-not-completed'].'</div><br />';
                echo'<table>';
                if ($numOfunsent['failed']) 
                    $atribute = 'checked';
                else 
                    $atribute = 'disabled';
                echo'<tr><td><input type="hidden" name="send-again[failed]" value="0" /><input type="checkbox" name="send-again[failed]" value="1" '.$atribute.' /><td>'.$numOfunsent['failed'].' <td>&ndash; '.$terms['num-of-recepients1'].'</td>';
                if ($numOfunsent['untouched']) 
                    $atribute = 'checked';
                else 
                    $atribute = 'disabled';
                echo'<tr><td><input type="hidden" name="send-again[untouched]" value="0" /><input type="checkbox" name="send-again[untouched]" value="1" '.$atribute.' /><td>'.$numOfunsent['untouched'].' <td>&ndash; '.$terms['num-of-recepients2'].'</td>';
                echo'</table>';
                echo'<br />'.$terms['send-again'].'<br />';
                echo'
                '.$submitButtons.'
                </form>';
                Report::reset();
            }
        } elseif ('delete-letter' == $_GET['phase']) {
            echo'
            '.$terms['mailing-deleted'].'
            '.$cancelButton.'
            ';
        }
        echo'</td>';
        # This mailing will no longer be sent
        if ('report' == $_GET['phase'] && !(empty($numOfunsent['failed']) && empty($numOfunsent['untouched']))){
            echo'
            <td class="blox-vert-sep">&nbsp;</td>
            <td style="vertical-align:top">• <a href="?mail-block-send&block='.$_GET['block'].'&phase=delete-letter'.$pagehrefQuery.'">'.$terms['delete-mailing'].'</a></td>'; # unsent
        }
        elseif ('send-letter' == $_GET['phase']){
            echo'
            <td class="blox-vert-sep">&nbsp;</td>
            <td style="vertical-align:top">
                • <a href="" onClick="window.location.reload();">'.$terms['send-next'].'</a>
                <br /><br />
                <div class="small gray" style="margin: 1px">'.$terms['you-can-exit'].'</div>
            </td>';
        }
    echo'</tr></table>';
echo'
</div>';

if ($newPhase){
    $loc = "location.href='?mail-block-send&block=".$_GET['block']."&phase=$newPhase".$pagehrefQuery."'; target='blank';";
    echo'
    <script type="text/javascript">';
        if ($blockletterParams['interval'] && $newPhase=='send-letter' && $scriptResult==1){
            $interval = $blockletterParams['interval']*1000; #ms
            if ($blockletterParams['randomize'])
                $interval = rand(round($interval * 0.7), round($interval * 1.4));
            echo'
            timer = setInterval("'.$loc.'", '.$interval.');';
            /** TODO stop clearInterval(timer); */
        }
        else
            echo $loc;
        echo'
    </script>';
}
