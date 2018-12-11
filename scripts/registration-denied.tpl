<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">    
    <div class="heading">'.$terms['bar-title'].'</div>
    <p class="red">'.$terms['message'].'</p>
    <p>';
        if (Blox::info('user','user-is-admin'))
            echo $terms['message2'].'<a href="?users'.$pagehrefQuery.'">&rarr;</a>';
        else
            echo $terms['message3'];
        echo'
    </p>
    '.$cancelButton.'
</div>';
