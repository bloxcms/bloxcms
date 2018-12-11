<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-bar">
    <div class="blox-menubar">'.$logoIcon.' <span class="bold">'.$barTitle.'</span></div>
    <div class="blox-menubar">
        <ul id="blox-dropdown-menu">
            <li class="blox-menu-item"><a  href="'.Blox::info('site','url').'/?group-info&selected-group-id=new'.$pagehrefQuery.'" title="'.$terms['new-group-info'].'" class="blox-menu-link"><span>'.$terms['new-group'].'</span></a></li>
        </ul>
    </div>
</div>';