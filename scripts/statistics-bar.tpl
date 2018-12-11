<?php
$pagehref = Blox::getPageHref();
echo'
<div class="blox-bar">
    <div class="blox-menubar">'.$logoIcon.' <span class="bold">'.$barTitle.'</span></div>
    <div class="blox-menubar">
        <ul id="blox-dropdown-menu">
            <li class="blox-menu-item">
                <a href="#" class="blox-menu-link"><span>'.$terms['service'].'</span></a>
                <ul class="blox-submenu">
                    <li><a href="?events'.$pagehrefQuery.'">'.$terms['event-mark'].'</a></li>
                    <li><a href="?statistics-to-delete'.$pagehrefQuery.'">'.$terms['statistics-delete'].'</a></li>
                    <li><a href="?statistics&toggle'.$pagehrefQuery.'">'.($statisticsIsOff ? $terms['counters-switch-on'] : $terms['counters-switch-off']).'</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>';