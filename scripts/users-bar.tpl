<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-bar">
    <div class="blox-menubar">'.$logoIcon.' <span class="bold">'.$barTitle.'</span></div>
    <div class="blox-menubar">
        <ul id="blox-dropdown-menu">
            <li class="blox-menu-item"><a  href="'.Blox::info('site','url').'/?user-info&selected-user-id=new&amp;not-outer'.$pagehrefQuery.'" title="'.$terms['new-user-info'].'" class="blox-menu-link"><span>'.$terms['new-user'].'</span></a></li>
            <li class="blox-menu-item"><a  href="#"  class="blox-menu-link"><span>'.$terms['settings'].'</span></a>
                <ul class="blox-submenu">
                    <li><a href="?update-store&var=allow-outer-registration&value='.($allowOuterRegistration ? '0' : '1').'&redirect=users'.$pagehrefQuery.'">';
                            if ($allowOuterRegistration) 
                                echo $terms['allow-outer-registration-not'];
                            else 
                                echo $terms['allow-outer-registration'];
                            echo'
                        </a>
                    </li>
                    <li><a href="?message-to-users-write'.$pagehrefQuery.'">'.$terms['message-to-users-write'].'</a></li>
                </ul>
            </li>
            <li class="blox-menu-item"><a  href="'.Blox::info('site','url').'/?groups'.$pagehrefQuery.'" title="'.$terms[''].'" class="blox-menu-link"><span>'.$terms['groups'].'</span></a></li>
        </ul>
    </div>
</div>';