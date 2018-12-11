<?php
/*
 * Main Toolbar
 * "Modes" sample: bar-is-fixed, user-sees-block-boundaries
 */

$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
$pageId = Blox::getPageId();
echo'
<div class="blox-bar" id="blox-page-bar">
    <div class="blox-menubar">'.$logoIcon;
        if ($pageIsHidden) 
            echo'<div style="float:right; color:#D4D0C8; background: #525252; padding: 0px 9px 0px 9px; border-right:solid 1px #fff; border-left:solid 1px #808080; margin-top:2px; height:19px; line-height:17px">'.$terms['page-is-hidden'].'</div>';
        echo' '.$terms['user'].' <strong>';
        if (!Blox::info('user','familyname'))
            echo Blox::info('user','login');
        else {
            echo Blox::info('user','personalname').' ';
            echo Blox::info('user','familyname');
        }
        echo'</strong>';
        /* 2018-05-12
        if (!Blox::info('user','user-is-activated'))
            echo' <span style="color:red">'.$terms['user-is-not-activated'].'</span>';
        else */
        {
            if (Blox::info('user','user-is-admin'))
            	echo' ('.$terms['user-is-admin'].')';
            elseif (Blox::info('user','user-is-editor'))
            	echo' ('.$terms['user-is-editor'].')';
        }
    echo'
    </div>
    <div class="blox-menubar" style="clear:both;">
        <ul id="blox-dropdown-menu">';
            # Site
            echo'
            <li class="blox-menu-item"><a  href="#" class="blox-menu-link"><span>'.$terms['this-site'].'</span></a>
                <ul class="blox-submenu">';
                    if (Blox::info('user','user-is-admin')) {
                        echo'
                        <li><a href="?site-settings'.$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="69" data-blox-shortcut-url="?site-settings'.$pagehrefQuery.'">E</span>'.$terms['site-settings'].'</a></li>
                        <li><a href="?users'.$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="85" data-blox-shortcut-url="?users'.$pagehrefQuery.'">U</span>'.$terms['users'].'</a></li>';
                        if ($userIsSubscriber_anyUser_anyBlock)
                            echo'<li><a href="?mail-subscriptions'.$pagehrefQuery.'">'.$terms['subscription-blocks'].'</a></li>';
                        if ($pageIsHidden_anyPage)
                            echo'<li><a href="?pages-hidden'.$pagehrefQuery.'">'.$terms['hidden-pages'].'</a></li>';
                    } else {
                        echo'<li><a href="?user-info'.$pagehrefQuery.'">'.$terms['user-info'].'</a></li>';
                        if (!Blox::info('user','user-is-editor')) {
							if ($userIsEditorOfAnyBlock)
                            	echo'<li><a href="?user-objects&formula=user-is-editor-of-block'.$pagehrefQuery.'">'.$terms['user-is-editor-of-block'].'</a></li>';
                            if ($userIdFieldExists)
                        		echo'<li><a href="?user-objects&formula=user-is-editor-of-records'.$pagehrefQuery.'">'.$terms['user-is-editor-of-records'].'</a></li>';
                            if ($userIsSubscriberOfAnyBlock)
                        		echo'<li><a href="?user-objects&formula=user-is-subscriber'.$pagehrefQuery.'">'.$terms['user-is-subscriber-of-block'].'</a></li>';
                        }
                    }
                    if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
                        echo'<li class="blox-separator"></li>';
                        if (Blox::info('user','user-is-admin')) {   
                            echo'
                            <li><a href="?templates'.$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="84" data-blox-shortcut-url="?templates'.$pagehrefQuery.'">T</span>'.$terms['tpls-description'].'</a></li>
                            ';
                        }
                        echo'
                        <li><a href="?statistics'.$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="83" data-blox-shortcut-url="?statistics'.$pagehrefQuery.'">S</span>'.$terms['statistics'].'</a></li>
                        <li><a href="?site-structure'.$pagehrefQuery.'">'.$terms['site-structure'].'</a></li>
                        ';
                    }
                    echo'<li class="blox-separator"></li>';
                	if ($pageId != 1) 
                        echo'<li><a href="'.Blox::info('site','url').'">'.$terms['home'].'</a></li>';
                    else 
                        echo'<li><span>'.$terms['home'].'</span></li>';
                    echo'<li><a href="?logout'.$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="76" data-blox-shortcut-url="?logout'.$pagehrefQuery.'">L</span>'.$terms['logout'].'</a></li>
                </ul>
            </li>';
            # Page
            if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
                echo'
                <li class="blox-menu-item"><a  href="#" class="blox-menu-link"><span>'.$terms['this-page'].'</span></a>
                    <ul class="blox-submenu">';
                        $pageInfoHref = '?page-info'.$pagehrefQuery;
                        echo'
                        <li><a href="'.$pageInfoHref.'"><span class="blox-shortcut" data-blox-shortcut-key="80" data-blox-shortcut-url="'.$pageInfoHref.'">P</span>'.$terms['page-settings'].'</a></li>';
                    	if (Blox::info('user','user-is-admin')) {
                            if ($pageIsHidden) {
                                echo'
                                <li class="blox-separator"></li>
                                <li><a href="?users-of-object&obj='.$pageId.'&formula=user-sees-hidden-page'.$pagehrefQuery.'">'.$terms['guests'].'</a></li>';
                                if (Sql::select('SELECT * FROM '.Blox::info('db','prefix').'groups LIMIT 1')) # groupsExist KLUDGE
                                    echo'<li><a href="?groups-of-object&obj='.$pageId.'&formula=group-sees-hidden-page'.$pagehrefQuery.'">'.$terms['groups-of-guests'].'</a></li>';
                                echo'<li class="blox-separator"></li>';
                            }
                        }

                        if ($outerBlockId && !Blox::info('user','user-as-visitor')) 
                        {
                            $href = '?page-structure&page='.$pageId.'&block='.$outerBlockId.$pagehrefQuery;
                            echo'<li><a href="'.$href.'"><span class="blox-shortcut" data-blox-shortcut-key="66" data-blox-shortcut-url="'.$href.'">B</span>'.$terms['page-structure'].'</a></li>';
                            $editOuterBlockHref = '?edit&block='.$outerBlockId.'&rec=1'.$pagehrefQuery;
                            echo'<li><a href="'.$editOuterBlockHref.'"><span class="blox-shortcut" data-blox-shortcut-key="79" data-blox-shortcut-url="'.$editOuterBlockHref.'">O</span>'.$terms['edit-outer-block'].'</a></li>';
                        }
                        echo'
                    </ul>
                </li>';
            }
            # Modes
            /*
            $recordPermission = Arr::remove(
                Permission::get('record'),
                ['undefined', false, '', 0, '0', null]
            );
            */
            $recordPermission = Permission::ask('record');

            if (
				Blox::info('user','user-is-admin') ||
				Blox::info('user','user-is-editor') ||
				$recordPermission ||
				$userIsEditorOfAnyBlock ||
				$userIdFieldExists
            ) {
                echo'
                <li class="blox-menu-item">
                    <a href="#" class="blox-menu-link"><span'; 
                        $menuColor = '';
                        $aa = ' class="blox-checked blox-marked-';
                        if (Blox::info('user','user-as-visitor')) {
                            $menuColor = 'yellow'; 
                            $userAsVisitorClass = ' class="blox-checked blox-marked-'.$menuColor.'"';
                        }
                        if ($editingDenied = Blox::info('site','editing-denied')) {
                            if (Blox::info('user', 'user-is-admin')) {
                                $menuColor = 'cyan'; 
                                $editingDeniedClass = ' class="blox-checked blox-marked-'.$menuColor.'"';
                            }
                            Blox::prompt($terms['editing-denied-prompt']);
                        }
                        if ($siteIsDown = Blox::info('site','site-is-down')) {
                            $menuColor = 'magenta"'; 
                            $siteIsDownClass = ' class="blox-checked blox-marked-'.$menuColor.'"';
                        }
                        if ($menuColor)
                            echo' class="blox-marked-'.$menuColor.'"';
                        echo'>'.$terms['modes'].'</span>
                    </a>
                    <ul class="blox-submenu blox-checking">';
                        $userAsVisitorHref = Blox::info('site','url').'/?update-proposition&formula=user-as-visitor&subject='.Blox::info('user','id').'&old-value='.Blox::info('user','user-as-visitor').$pagehrefQuery;
                        echo'<li><a href="'.$userAsVisitorHref.'"'.$userAsVisitorClass.'><span class="blox-shortcut" data-blox-shortcut-key="192" data-blox-shortcut-url="'.$userAsVisitorHref.'">~</span>'.$terms['user-as-visitor'].'</a></li>';
                        # bar-is-fixed
                        $checked = Blox::info('user','bar-is-fixed');
                        $clas = $checked ? 'blox-checked' : '';
                        $href = '?update-proposition&formula=bar-is-fixed&subject='.Blox::info('user','id').'&old-value='.$checked.$pagehrefQuery;
                        $shortcut = '<span class="blox-shortcut" data-blox-shortcut-key="70" data-blox-shortcut-url="'.$href.'">F</span>'; # Use assets/blox.shortcut.html
                        $title = $terms['bar-is-fixed']['title'];
                        $label = $terms['bar-is-fixed']['label'];
                        echo'<li><a href="'.$href.'" title="'.$title.'" class="'.$clas.'">'.$shortcut.$label.'</a></li>';
                        # user-sees-block-boundaries
                        $checked = Blox::info('user','user-sees-block-boundaries');
                        $clas = $checked ? 'blox-checked' : '';
                        $href = '?update-proposition&formula=user-sees-block-boundaries&subject='.Blox::info('user','id').'&old-value='.$checked.$pagehrefQuery;
                        $shortcut = '';
                        $title = '';
                        $label = $terms['user-sees-block-boundaries'];
                        echo (Blox::info('user','user-as-visitor'))
                            ? '<li><span>'.$shortcut.$label.'</span></li>'
                            : '<li><a href="'.$href.'" title="'.$title.'" class="'.$clas.'">'.$shortcut.$label.'</a></li>'
                        ;
                        #
                        if (Blox::info('user','user-is-admin')) {
                            echo'
                            <li class="blox-separator"></li>
                            <li><a href="?update-proposition&formula=editing-denied&old-value='.$editingDenied.$pagehrefQuery.'"'.$editingDeniedClass.'>'.$terms['editing-denied'].'</a></li>
                            <li><a href="?update-proposition&formula=site-is-down&old-value='.$siteIsDown.$pagehrefQuery.'"'.$siteIsDownClass.'>'.$terms['site-is-down'].'</a></li>';
                        }
                        echo'
                    </ul>
                </li>';
            }
            # commands
            echo'
            <li class="blox-menu-item"><a  href="#" class="blox-menu-link"><span>'.$terms['commands'].'</span></a>
                <ul class="blox-submenu">';
                    if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
                        echo'
                        <li><a href="?update-human-urls'.$pagehrefQuery.'" title="'.$terms['update-human-urls-title'].'">'.$terms['update-human-urls-label'].'</a></li>
                        <li><a href="?update-sitemap-xml'.$pagehrefQuery.'">'.$terms['update-sitemap-xml'].'</a></li>';
                        if (Blox::info('site', 'caching'))
                        echo'<li><a href="?update-page-caches'.$pagehrefQuery.'">'.$terms['update-page-caches'].'</a></li>';
                        if (Blox::info('user','user-is-admin')) echo'
                        <li><a href="?db-refresh'.$pagehrefQuery.'" title="'.$terms['db-refresh-title'].'">'.$terms['db-refresh-label'].'</a></li>';
                    }
                    echo'
                </ul>
            </li>';
            $contents_href = 'http://bloxcms.net/documentation/_default.htm?version='.Blox::getVersion();
            echo'
            <li class="blox-menu-item"><a href="#" class="blox-menu-link"><span>'.$terms['help'].'</span></a>
                <ul class="blox-submenu">
                    <li><a href="'.$contents_href.'" target="_blank" class="blox-tooltip" rel="nofollow" title="<center>'.$terms['cms'].'&nbsp;<br>&nbsp;&nbsp;<b>Blox CMS</b> '.$terms['version'].' '.Blox::getVersion().'</center>">'.$terms['contents'].'</a></li>
                </ul>
            </li>
        </ul>       
    </div>
</div>';
