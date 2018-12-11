<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<div class="heading">'.$terms['bar-title-'.$_GET['what']].'</div>
<table style="width:100%">
    <tr><td>';
    #1. Before replacing the template (after the edit window). Check the nesting in the delegate block
        #if ('before-tpl-selected' == $_GET['what'])
    #2. after-tpl-selected
    if ('after-tpl-selected' == $_GET['what']) {
        echo'<p>'.$terms['is-inc-in-delegated'].' <b>'.$blockPage.'</b>'; 
            if ($pageTitle) 
                echo' ('.$pageTitle.')'; 
        echo'.</p>
        <p>'.$terms['go-to-native-page'].'</p>
        <form action="?page='.$blockPage.'" method="post">
        '.$submitButtons.'
        </form>';
    }
    #3. afterInstanceSelected (reserved)
    #4. Check the manually selected template before assigning.
    elseif ('beforeAssign' == $_GET['what']) //old   deprecate from change
    {
        echo'
        <form action="?assign&block='.$regularId.Query2::get('tpl').$pagehrefQuery.'" method="post">
        <p>
            '.$terms['warning'].' ('.$terms['template'].': '.Query2::get('tpl').').
            <div class="note">'.$terms['explanation'].'</div>';
        echo'
        </p>
        <p>'.$terms['proceed'].'</p>
        '.$submitButtons.'
        </form>';
    }
    echo'
    </td></tr>
</table>
</div>';