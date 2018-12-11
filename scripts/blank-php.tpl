<?php
if (Blox::info('user','user-is-admin') && empty($blockInfo['parent-block-id']) && $blockInfo['id']) {
    if (isset($_GET['change']))
        $warningStyle = ' background:#ff0;color:red';
    else
        $warningStyle = ' background:#fff;color:#000';
    echo'
    <div style="display:table; width:100%; height:100%">
        <div style="display:table-cell; text-align:center; vertical-align:middle; font:12px Verdana; height:300px;'.$warningStyle.'">';    
            if (isset($_GET['change'])) {
                if (empty($_GET['tpl']))
                    echo'<p><b>'.$blankTerms['select-outer-tpl'].'</b></p>';
                else
                    echo'<p>'.$blankTerms['tpl-is-selected'].'</p><p style="font:11px Tahoma">'.$blankTerms['delegate'].' <b>'.urldecode($_GET['tpl']).'</b></p>';
            } else {
                echo'
                <p>'.$blankTerms['no-outer-tpl'].'</p>
                <div style="height:9px"></div>';
                if (Blox::info('user','user-as-visitor'))
                    echo'<span style="color:red">'.$blankTerms['visitor-mode'].'</span>';
                elseif (Blox::info('user','user-is-admin'))
                    echo'<!--noindex--><a class="blox-edit-button blox-no-tpl blox-maintain-scroll" href="'.$dat['edit-href'],'" title="" rel="nofollow" style="color:#fff !important; padding:5px 9px 6px !important; font:normal 13px Verdana, Tahoma !important; position:relative; top:5px !important">'.$blankTerms['select-tpl'].'</a><!--/noindex-->';
            }
            echo'
            <div style="height:10%"></div>
        </div>
    </div>';
} else
    echo $dat['edit'];