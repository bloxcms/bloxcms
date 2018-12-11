<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
# KLUDGE: Query2 used in output-multirec-buttons.php again
Query2::capture();
Query2::remove('edit');
Query2::remove('page');
Query2::remove('pagehref');
$filtersQuery = Query2::build();
echo'
<div class="blox-bar">
    <div class="blox-menubar">'.$logoIcon.'<span class="bold">'.$terms[$xprefix.'bar-title'].Admin::tooltip("editing-window.htm", $terms['editing-help']).'&#160;</span></div>
    <div class="blox-menubar">
        <ul id="blox-dropdown-menu">';
            if (Permission::ask('record', [$blockInfo['src-block-id']])['']['edit']) {
                if ($xprefix)
                    echo'<li class="blox-menu-item"><span class="blox-menu-link"><span>'.$terms['menu_block'].'</span></span></li>';
                else {
                    echo'<li class="blox-menu-item"><a href="#" class="blox-menu-link"><span>'.$terms['menu_block'].'</span></a>                    
                    <ul class="blox-submenu">';
                        if (Blox::info('user','user-is-admin')) {
                            if ($delegatedContainerParams['id'] && $delegatedContainerParams['block-page-id'] != Blox::getPageId())
                                $hasDelegatedAncestor = true;
                            #
                            if ($blockInfo['tpl']) {
                                $aa = $terms['tpl-changing'];
                                $bb = '&old-tpl='.urlencode($blockInfo['tpl']);
                            } else 
                                $aa = $terms['tpl-assigning'];
                            #
                            if ($hasDelegatedAncestor)
                                echo'<li><span>'.$aa.'</span></li>';
                            else
                                echo'<li><a href="?check&block='.$blockInfo['id'].'&what=before-tpl-selected'.$bb.$pagehrefQuery.'">'.$aa.'</a></li>';
                            #
                            if ($blockInfo['tpl']) {
                                if ($hasDelegatedAncestor)
                                    echo'<li><span>'.$terms['tpl-remove'].'</span></li>';
                                else {
                                    echo'<li><a href="?tpl-remove&block='.$blockInfo['id'].$pagehrefQuery.'"';
                                        if (empty($blockInfo['delegated-id']))
                                            echo' onclick="return confirm(\''.$terms['tpl-remove-confirm'].'\')"';
                                    echo'>'.$terms['tpl-remove'].'</a></li>';
                                }
                            }
                        }
                        echo'<li class="blox-separator"></li>';
                        #echo'<li><a href="?edit-button-setting-&block='.$blockInfo['id'].$pagehrefQuery.'">'.$terms['edit-button-setting'].'</a></li>';
                        echo'<li><a href="?block-settings&block='.$blockInfo['id'].$pagehrefQuery.'">'.$terms['block-settings'].'</a></li>';

                        if (!($noTddTypes || empty($blockInfo['tpl']))) {
                            if (Blox::info('user','user-is-admin')) {
                                echo'
                                <li class="blox-separator"></li>
                                <li><a href="?users-of-object&obj='.$blockInfo['src-block-id'].'&formula=user-is-editor-of-block'.$pagehrefQuery.'" data-blox-shortcut-key="69" data-blox-shortcut-url="?users-of-object&obj='.$blockInfo['src-block-id'].'&formula=user-is-editor-of-block'.$pagehrefQuery.'"><span class="blox-shortcut">E</span>'.$terms['user-is-editor-of-block'].'</a></a></li>';
                                if ($groupsExist) {
                                    echo'
                                    <li><a href="?groups-of-object&obj='.$blockInfo['src-block-id'].'&formula=group-is-editor-of-block'.$pagehrefQuery.'">'.$terms['group-is-editor-of-block'].'</a></a></li>
                                    <li class="blox-separator"></li>';
                                }
                            }

                            if ($userIsSubscriber_anyUser_thisBlock) {
                                echo'<li><a href="?users-of-object&obj='.$blockInfo['src-block-id'].'&formula=user-is-subscriber'.$pagehrefQuery.'">'.$terms['user-is-subscriber'].'</a></li>';
                                if ($groupsExist) {
                                    echo'
                                    <li><a href="?groups-of-object&obj='.$blockInfo['src-block-id'].'&formula=group-is-subscriber'.$pagehrefQuery.'">'.$terms['group-is-subscriber'].'</a></li>
                                    <li class="blox-separator"></li>';
                                }
                            }
                                    
                            if (Blox::info('user','user-is-admin'))
                                echo'<li><a href="?block-structure&block='.$blockInfo['id'].$pagehrefQuery.'">'.$terms['structure'].'</a></li>';

                            echo'
                            <li><a href="" class="blox-fly">'.$terms['sort'].'</a>
            					<ul>
                                    <li><a href="?sort-manualy&'.$filtersQuery.$pagehrefQuery.'"><span class="blox-shortcut"  data-blox-shortcut-key="83" data-blox-shortcut-url="?sort-manualy&'.$filtersQuery.$pagehrefQuery.'">S</span>'.$terms['sort-manualy'].'</a></li>
                                    <li><a href="?sort&'.$filtersQuery.$pagehrefQuery.'">'.$terms['sort-by-columns'].'</a></li>
            					</ul>
                            </li>';//?sort-manualy...'&rec='.$dat['rec']
                        }

                        if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))
                            echo'<li><a href="?mail-block&block='.$blockInfo['id'].$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="77" data-blox-shortcut-url="?mail-block&block='.$blockInfo['id'].$pagehrefQuery.'">M</span>'.$terms['mail-block'].'</a></li>';

                        /**  NOT DEBUGGED IMPORT SCRIPTS
                        if (!($noTddTypes || empty($blockInfo['tpl'])))
                        {
                            echo'
                            <li><a href="" class="blox-fly">'.$terms['import'].'</a>
            					<ul>
                                    <li><a href="?import-rowwise&'.$filtersQuery.'&rec='.$dat['rec'].$pagehrefQuery.'"><span class="blox-shortcut" data-blox-shortcut-key="73" data-blox-shortcut-url="?import-rowwise&'.$filtersQuery.$pagehrefQuery.'">I</span>'.$terms['import-rowwise'].'</a></li>
                                    <li><a href="?import-columnwise&'.$filtersQuery.$pagehrefQuery.'">'.$terms['import-columnwise'].'</a></li>
            					</ul>
                            </li>
                            ';
                        }
                        */
                        # Create new rec
                        if ($params['multi-record']) #TODO: && ($userSeesEditButtons || $params['public']['show-new-rec-edit-button'])  - from newRecButton
                        {
                            echo'
                            <li class="blox-separator"></li>';
                            # Link to multirec edit (without "&rec=")
                            $multiEditUrl2 = $multiEditUrl.$pagehrefQuery;
                            echo'<li><a href="'.$multiEditUrl2.'"><span class="blox-shortcut" data-blox-shortcut-key="82" data-blox-shortcut-url="'.$multiEditUrl2.'">R</span>'.$terms['multi-rec-edit'].'</a></li>';
                            # Link to new rec
                            $newEditUrl2 = $newEditUrl.$pagehrefQuery;
                            echo'<li><a href="'.$newEditUrl2.'"><span class="blox-shortcut" data-blox-shortcut-key="78" data-blox-shortcut-url="'.$newEditUrl2.'">N</span>'.$terms['create-new-rec'].'</a></li>';                                                        
                        }
                        # parentBlockUrl
                        if ($blockInfo['parent-block-id'] && $blockInfo['parent-rec-id']) {
                            if (!$params['multi-record']) echo'<li class="blox-separator"></li>';
                            $parentBlockUrl = '?edit&block='.$blockInfo['parent-block-id'].'&rec='.$blockInfo['parent-rec-id'].$pagehrefQuery;
                            echo'<li><a href="'.$parentBlockUrl.'"><span class="blox-shortcut" data-blox-shortcut-key="66" data-blox-shortcut-url="'.$parentBlockUrl.'">B</span>'.$terms['parent-block'].'</a></li>';
                        }
                        echo'
                        </ul>
                    </li>
        			';
                }
            }

			# Delete
			if ($params['multi-record']) {
                if (Request::get($blockInfo['id'],'pick'))
                    $_SESSION['Blox']['confirm-recs-to-del'] = $terms['confirm-recs-to-del'];
                else
                    $_SESSION['Blox']['confirm-recs-to-del'] = '';
                #
                if ($idTypeExists && !Blox::info('user','user-is-admin'))
    			    echo'<li class="blox-menu-item"><span class="blox-menu-link"><span>'.$terms['delete'].'</span></span>';
                else {  
        			echo'
                    <li class="blox-menu-item"><a href="#" class="blox-menu-link"><span>'.$terms['delete'].'</span></a>
                        <ul class="blox-submenu">';
                            if (isset($_GET['rec'])) { # Single rec editing
                                echo '
                                <li><a href="?recs-delete&'.$filtersQuery.'&which=current'.$pagehrefQuery.'">'.$terms['current-rec'].'</a></li>
                                <li class="blox-separator"></li>';
                            }
                            if (Permission::ask('record', [$blockInfo['src-block-id']])['']['edit']) { //get
                                $aa = '?recs-select&'.$filtersQuery.$pagehrefQuery;
                                echo'        	                    
        	                    <li><a href="'.$aa.'"><span class="blox-shortcut" data-blox-shortcut-key="68" data-blox-shortcut-url="'.$aa.'">D</span>'.$terms['selected-recs'].'</a></li>';
                                if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
                                    echo'<li><a href="?recs-range&'.$filtersQuery.$pagehrefQuery.'">'.$terms['range-of-recs'].'</a></li>';                                    
                                    $aa = ($_SESSION['Blox']['confirm-recs-to-del']) ? $terms['confirm-recs-to-del'].' ' : '';
                                    echo'<li><a href="?recs-delete&block='.$blockInfo['id'].'&which=all'.$pagehrefQuery.'" onclick="return confirm(\''.$aa.$terms['sure-all-recs'].'\')">'.$terms['all-recs'].'</a></li>';
                                }
                            }
                            echo'
                        </ul>
                    </li>
        			';
                }
			}
			else
			    echo'<li class="blox-menu-item"><span class="blox-menu-link"><span>'.$terms['delete'].'</span></span></li>';
		    echo'
        </ul>
    </div>
</div>';