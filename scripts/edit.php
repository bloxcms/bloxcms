<?php
    /**
     * @todo \Wrec\W|select|direction|\$dat\W         only for single rec editing or xprefix
     */
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $pagehref = Blox::getPageHref();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref); # Do not remove pagehref param with empty value, because it is used the code `isset($_GET['pagehref'])`
    $blockInfo = Blox::getBlockInfo($regularId);
    $srcBlockId = $blockInfo['src-block-id'];
    $prefix = Blox::info('db','prefix');

    if ($blockInfo['tpl']) {
        Request::set(); # Place this above Tdd::get() for $defaults=Request::get()
        $tpl = $blockInfo['tpl'];
        $tdd = Tdd::get($blockInfo);
        if ($_GET['rec']) {
            if ('new'==$_GET['rec'] || Str::isInteger($_GET['rec']))
                $getRecId = $_GET['rec']; # Apply "single" request because the date format will be converted in geTab()
        }
        if (!$getRecId && !$tdd[$xprefix.'params']['multi-record'])
            $getRecId = 1;
        if (isset($getRecId)) {
            $template->assign('getRecId', $getRecId);
            Request::add('block='.$regularId.'&single='.$getRecId);
        }
        $defaults = Arr::mergeByKey($tdd['defaults'], $_GET['defaults']);
        #
        if ($tdd['xtypes'])
            $template->assign('xdatExists', true);

        $aa = Blox::info('templates', 'dir').'/'.$tpl.'.tpl';
        if (!file_exists($aa))
            Blox::prompt(sprintf($terms['no-tpl-file'], $aa), true);
            if ($_GET['xprefix']=='x')
                $xprefix = 'x';
            else
                $xprefix = '';
        $tbl = Blox::getTbl($tpl, $xprefix);
    }

    Permission::addBlockPermits($srcBlockId, $tdd);

    # public  !user-is-editor-of-records
    # KLUDGE: permissions
    # BUG: $tab[0]['rec'] IS NOT DEFINED! SEE BELOW FOR $tab
    if ($getRecId) {
        if (!Permission::ask('record', [$srcBlockId])['']['edit']) {//get
            if ($tdd[$xprefix.'params']['public']) {
                if ($tdd[$xprefix.'params']['public']['editing-allowed'] && $_SESSION['Blox']['fresh-recs'][$srcBlockId][$tab[0]['rec']]) # Fresh public rec
                    Permission::add('record', [$srcBlockId, $tab[0]['rec']], ['edit'=>true]);
                elseif ($getRecId=='new')
                    Permission::add('record', [$srcBlockId, ''], ['create'=>true]);
            }
        }
    }

    if ($tdd['params']['public']['editing-allowed'] && ($getRecId || $getRecId=='new'))
        ; 
    elseif (Permission::get('record', $srcBlockId)) #KLUDGE: more specific permissions will be given below
        ;
    else
        Blox::execute('?error-document&code=403');

    # Number of delegations of the delegated block. Calculate it always.
    $sql = 'SELECT COUNT(id) FROM '.$prefix."blocks WHERE `delegated-id`=?";
    if ($result = Sql::query($sql, [$blockInfo['src-block-id']])) {
        $numOfDelegations = $result->fetch_row()[0]; # NOTTESTED
        $result->free();
        $template->assign('numOfDelegations', $numOfDelegations);
    }
    

    # Is the block nested into a nested block?
    $blockPageId = Blox::getBlockPageId($regularId, $delegatedAncestorId);# returns $delegatedAncestorId too
    if ($delegatedAncestorId) {
        $delegatedContainerParams = Blox::getBlockInfo($delegatedAncestorId);
        $delegatedContainerPageId = Blox::getBlockPageId($delegatedAncestorId);
        $delegatedContainerParams['block-page-id'] = $delegatedContainerPageId;
        $bb = Router::getPageInfoById($delegatedContainerPageId);
        $delegatedContainerParams['block-page-title'] = $bb['title'];
        $template->assign('delegatedContainerParams', $delegatedContainerParams);
    }

    if (empty($tpl)) {
        if (Blox::info('user','user-is-admin'))
            Url::redirect(Blox::info('site','url').'/?check&block='.$regularId.'&what=before-tpl-selected'.$pagehrefQuery,'exit');
        else {
            $aa = $terms['no-tpl'].' ';
            Blox::prompt($aa.$terms['no-tpl-editor']);
        }
    }
    else
    {   
        $template->assign('xprefix', $xprefix);
        
        if (empty($tdd[$xprefix.'types'])) {
            $template->assign('noTddTypes', true);
        } 
        else
        {
        	if ($tdd[$xprefix.'params']['user-id-field']) {
                $userIdField = $tdd[$xprefix.'params']['user-id-field'];
                Permission::add('record', [$srcBlockId, ''], ['create'=>true]);
                Permission::add('record', [$srcBlockId, ''], 
                    function($keys, $data) { # Allow to edit own recs
                        if ($data['dat'][$data['tdd']['params']['user-id-field']] == Blox::info('user','id'))
                            return ['edit'=>true];
                    }
                );
                $template->assign('userIdFieldExists', true);
            }

            # Settings of edit window
            $settings = Store::get($xprefix.'editSettings'.$srcBlockId);
            $template->assign('settings', $settings);

           	if ($getRecId) {
                # Create new rec 
    	        if ($getRecId=='new') {
                    # KLUDGE: Sometimes  appeares duplicating spurious request "?edit&...&rec=new..." with appended url param "&_=1504001421310" where value is the time. The reasons are not found.
                    if ($_SESSION['Blox']['edit']['new-rec-time'] && Blox::ajaxRequested()) {
                        if (microtime(true) - $_SESSION['Blox']['edit']['new-rec-time'] < 100) {
                            Blox::error('Too quick attempt to create a new record');
                            return false;
                        }
                    }
                    $_SESSION['Blox']['edit']['new-rec-time'] = microtime(true);
                    #/
                    $dat = Dat::insert($blockInfo, $defaults, $xprefix, $tdd);
                    # Do again standart retrieve because of "select" data types
                    Request::add('block='.$regularId.'&single='.$dat['rec']);
                    $tab = Request::getTab($blockInfo, $tdd, $xprefix);
                }
                # existing rec editing
    	        else {
                    # We came from edit window - rec is unknown
                    if ($xprefix) {   
                        #  Retrieve extradata
                        if ($tab = Request::getTab($blockInfo, $tdd, $xprefix))
                            ;
                        else { # No recs yet
                            $dat = Dat::insert($blockInfo, $defaults, $xprefix, $tdd);
                            Request::add('block='.$regularId.'&single='.$dat['rec']);# Do again standart retrieve because of "select" data types
                            $tab = Request::getTab($blockInfo, $tdd, $xprefix);
                        }   
                    } else {
                        if ($tab = Request::getTab($blockInfo, $tdd, $xprefix)) {
                            ;
                        } elseif ($getRecId) {
                            if ($getRecId==1) {
                                $dat = Dat::insert($blockInfo, $defaults, '', $tdd);
                                Request::add('block='.$regularId.'&single=1');# Do again standart retrieve because of "select" data types
                                $tab = Request::getTab($blockInfo, $tdd);
                            } else {
                                Blox::prompt(sprintf($terms['no-rec'], '<b>'.$getRecId.'</b>', '<b>'.$srcBlockId.'</b>'), true);
                                Url::redirect($pagehref,'exit');
                            }
                        }
                    }
                              
                    if ($tdd[$xprefix.'params']['multi-record'] && $_SESSION['Blox']['initial-rec-is-created'][$srcBlockId])
                        unset($_SESSION['Blox']['initial-rec-is-created'][$srcBlockId]);
                }
                # Do not use $dat below
           	}
            # MultyRecEdit
            else {                
                # Show all records of the block
                if (Blox::info('user','user-is-admin')) 
                    include Blox::info('cms','dir')."/includes/disable-multirec-filters.php";
                $tab = Request::getTab($blockInfo, $tdd, $xprefix);
            }
		    # Editors of block
            if ($getRecId) {
    		    if ($tab[0][$userIdField]) {
    		        $sql = "SELECT * FROM ".$prefix."users WHERE id=?";
    		        if ($result = Sql::query($sql, [$tab[0][$userIdField]])) {
        		        if ($aa = $result->fetch_assoc())
        			        $template->assign('editorOfRecordInfo', $aa);
                        $result->free();
                    }

                    if ($getRecId=='new') {
                        if (!Permission::ask('record', $srcBlockId)['']['create']) //get
                            Blox::execute('?error-document&code=403');
                    } elseif (!Permission::ask('record', [$srcBlockId, $tab[0]['rec']], ['dat'=>$tab[0], 'tdd'=>$tdd])['edit']) { //get
                        # The author should not edit other people's records by a direct link
                        Blox::execute('?error-document&code=403');
                    }
    		    }
            }
            
            if ($typesDetails = Tdd::getTypesDetails($tdd[$xprefix.'types'])) # Get all types as they are used in template too
            {
                # Fields with NULL   
                if ($tdd[$xprefix.'fields']['nullable']) {
                    $nullableFields = [];   
                    $sql = 'DESCRIBE '.$tbl;
                    //$sql = 'DESCRIBE `'.trim($tbl, '`').'`';
                    if ($result = Sql::query($sql)) {
                        while ($row = $result->fetch_assoc()) {
                            $field = preg_replace('~^dat~', '', $row['Field']);        
                            if (Str::isInteger($field) && $row['Null']=='YES' && ($tdd[$xprefix.'fields'][$field]['nullable']))
                                $nullableFields[$field] = true;
                        }
                        $result->free();
                    }
                }
                unset($_SESSION['Blox']['select-lists']);
                $captions = $tdd[$xprefix.'captions'];
                if ($getRecId) {
                    # Return to edit fields after adding or deleting a record in the "select" list of this field
                    if ($_GET['direction'] == 'left' && $_GET['edit-field']) {
                        if ($dependedSelectFields = Admin::getDependedSelectFields($typesDetails, $_GET['edit-field']))
                            foreach ($dependedSelectFields as $field)
                                $tab[0][$field] = '';

                        if ($_GET['mode'] == 'delete')
                            $tab[0][$_GET['edit-field']] = '';
                        elseif ($_GET['mode'] == 'add')
                            $tab[0][$_GET['edit-field']] = $_GET['edit-value'];
                        Dat::update(['src-block-id'=>$srcBlockId,'tpl'=>$tpl], $tab[0], ['rec'=>$getRecId], $xprefix);
                    }
                }

                # Redirect from multi to single for block with 0 or 1 records
                if (!isset($getRecId)) {
                    $getRecId2 = '';
                    if (!Request::get($regularId, 'pick')) {
                        if (!$tab)
                            $getRecId2 = 'new';
                        /** Temporarily closed due to obscurity of the interface
                        elseif (count($tab)==1)
                            $getRecId2 = $tab[0]['rec'];
                        */
                    }
                    if ($getRecId2) {
                        # Check if we go from single to multi
                        $prevHref2 = preg_replace(
                            '~&rec=\d+~', '', 
                            Url::convertToRelative($_SERVER['HTTP_REFERER']) # From single rec: ?edit&block=161&rec=1&pagehref=aHR0cDovL
                        );
                        $currHref = Url::convertToRelative($_SERVER['REQUEST_URI']);
                        #
                        if ($prevHref2 != $currHref) { # Not going from single to multi
                            Query2::capture();
                            Url::redirect('?'.Query2::build('rec='.$getRecId2),'exit');
                        }
                    }
                }



                $idTypeExists = false; # To prevent deleting of records that contain special data by nonadmin
                $useTextEditor = false;
                foreach ($typesDetails as $field => $typeDetails)
                {
                    $typeName = $typeDetails['name'];
                    if ('block' == $typeName) {
                        $idTypeExists = true; 
                        unset($nullableFields[$field]);
                    } elseif ('page' == $typeName) {
                        $idTypeExists = true; 
                        unset($nullableFields[$field]);
                    } elseif ('select' == $typeName) {
                        unset($nullableFields[$field]);
                        $params = $typeDetails['params'];
                        # Data for "select" list
                        if ($params['template'][0] && $params['edit'][0]) {
                            $selectListTpl = Files::normalizeTpl($params['template'][0], $tpl);
                            foreach ($tab as $dat) {
                                $sql = '';
                                if (empty($params['parentfield'][0])) { # Independent list
                                    $sql = 'SELECT `select-list-block-id` FROM '.$prefix.'selectlistblocks WHERE `edit-block-id`=? AND `edit-field`=?';
                                    $sqlParams = [$srcBlockId, $field];  
                                } elseif ($dat['selects'][$params['parentfield'][0]]) { # Dependent list
                                    if ($params['templateparentidfield'][0]) { # Pick-navigation
                                        $sql = 'SELECT `select-list-block-id` FROM '.$prefix.'dependentselectlistblocks WHERE `parent-list-block-id`=? LIMIT 1';
                                        $sqlParams = [$selectListBlockIds[$params['parentfield'][0]]];
                                        $defaultsQueries[$field] = $defaultsQueries[$params['parentfield'][0]].'&defaults['.$params['templateparentidfield'][0].']='.$dat['selects'][$params['parentfield'][0]];
                                        $pickValuesFields[$params['parentfield'][0]] = 1;
                                    } else {
                                        $sql = 'SELECT `select-list-block-id` FROM '.$prefix.'dependentselectlistblocks WHERE `parent-list-block-id`=? AND `parent-list-rec-id`=?';
                                        $sqlParams = [
                                            $selectListBlockIds[$params['parentfield'][0]], 
                                            $dat['selects'][$params['parentfield'][0]]
                                        ];
                                    }
                                }

                                if ($sql) {
                                    if ($result = Sql::query($sql, $sqlParams)) {
                                        if ($row = $result->fetch_assoc()) {
                                            $result->free();
                                            $selectListBlockIds[$field] = $row['select-list-block-id']; # [$field] is added to avoid calculating the parent $selectListBlockId for the dependent list.
                                            
                                            
                                            /**
                                             * KLUDGE-987 
                                             * see below too
                                             */
                                            if ($selectListBlockIds[$field] == $srcBlockId)
                                                Request::set([$selectListBlockIds[$field] => []]); # Reset request params if select list block and current block are the same (to show all records)
                        
                                                                                            
                                            $selects2 = getSelects($selectListTpl, $selectListBlockIds[$field], $params['edit'][0], $params['templateparentidfield'][0], $dat['selects'][$params['parentfield'][0]], $xprefix);

                                            /**
                                             * KLUDGE-987 
                                             * Consider all "select" data that lays in the same block as PARENT ID.
                                             * This is done for tree nav block with parent field managed by select data.
                                             * see above too
                                             */
                                            if ($selectListBlockIds[$field] == $srcBlockId) {
                                                # Exclude from the list of options all children records (ancestors are allowed),
                                                # i.e you cannot became a child of your child but you can change your parent!
                                                $children2[$dat['rec']][] = $dat['rec'];
                                                # Retrieve data again since `rec-id` is substituted by text. Conditions does not affect.
                                                $table2 = Sql::select('SELECT `rec-id`, dat'.$field.' FROM '.Blox::getTbl($tpl).' WHERE `block-id`=?', [$srcBlockId]);
                                                foreach ($table2 as $row2) {                                                    
                                                    if ($row2['dat2'] != 0) 
                                                        $children2[$row2['dat2']][] = $row2['rec-id'];
                                                    else
                                                        $children2[0][] = $row2['rec-id']; 
                                                }
                                                foreach ($children2[$dat['rec']] as $rec2)
                                                    unset($selects2[$rec2]);
                                            }
                                            
                                            
                                            if ($selects[$dat['rec']][$field] = $selects2)
                                                ;
                                            else
                                                $selects[$dat['rec']][$field] = []; # Declare var even if there are no list items to know that a list is assigned 
                                        }
                                    }
                                    # KLUDGE: Some of the elements of the array are repeated for different records
                                    $_SESSION['Blox']['select-lists'][$dat['rec']][$field] = [
                                        'tpl'=>$selectListTpl,
                                        'select-list-block-id'=>$selectListBlockIds[$field],
                                        'edit' => $params['edit'][0],
                                        'parent-list-block-id'=> $selectListBlockIds[$params['parentfield'][0]],
                                        'defaults-queries' => $defaultsQueries[$field],
                                        'controls'=>$params['controls'],
                                    ];
                                }
                            }
                        }
                    }
                    # Single mode and there are text data
                    elseif ($typeName == 'tinytext' || $typeName == 'text' || $typeName == 'mediumtext' || $typeName == 'longtext') {                        
                        unset($nullableFields[$field]); # TEXT does not support default values, it's implicitly DEFAULT NULL. So do not change anything.
                        if (isset($getRecId))
                            $useTextEditor = true;
                    }
                    $typeFieldsOrder[$field] = true; # Temporary array to calculate array $editingFields
                    $template->assign('idTypeExists', $idTypeExists);
                }
                $template->assign('editingFields', Admin::getEditingFields($tdd[$xprefix.'titles'], $typeFieldsOrder, $xprefix));
                $template->assign('pickValuesFields', $pickValuesFields); # It is passed only with the purpose to not remove the dependence from the table "dependentselectlistblocks" if list is used with pick-request
                $template->assign('selects', $selects);
                $template->assign('blocks', $blocks);    
                $template->assign('nullableFields', $nullableFields);
            }
            $template->assign('typesDetails', $typesDetails);
            $template->assign('dataTitles', $tdd[$xprefix.'titles']);
            $template->assign('captions', $captions);
            $template->assign('notes', $tdd[$xprefix.'notes']);
            $template->assign('styles', $tdd[$xprefix.'styles']);
            $template->assign('mstyles', $tdd['mstyles']);
            $template->assign('params', $tdd[$xprefix.'params']);
            $template->assign('fields', $tdd[$xprefix.'fields']);
            $template->assign('tooltips', $tdd[$xprefix.'tooltips']);
            # For the visitor that edits its own public rec
			if (Proposition::get('user-is-subscriber', 'any', $srcBlockId)) {
                $userIsSubscriber_anyUser_thisBlock = true;
                $template->assign('userIsSubscriber_anyUser_thisBlock', true);
            }
            
            if (Blox::info('user','user-is-admin') || $userIsSubscriber_anyUser_thisBlock) 
                if (Sql::select('SELECT * FROM '.$prefix.'groups LIMIT 1'))
                    $template->assign('groupsExist', true);

            # Redirects to avoid passing the edit window
            if (isset($_GET['import-rowwise']))
                Url::redirect(Blox::info('site','url').'/?import-rowwise&block='.$regularId.$pagehrefQuery,'exit');
        }

        if (!isset($_GET['add-new-rec']) && $useTextEditor && $getRecId) {
            # Is there alternative text editor?
            if (file_exists('text-editor/text-editor-link.php')) {
                $textEditorDir = 'text-editor';
                if (file_exists($textEditorDir.'/text-editor-link.php'))
                    $textEditorUrl = Blox::info('site','url').'/text-editor';
            } else {# Is there built in text (system) editor?
                # inner cross domain supporting editor
                $textEditorDir = Blox::info('cms','dir').'/includes';
                if (file_exists($textEditorDir.'/text-editor-link.php'))
                    $textEditorUrl = Blox::info('cms','url').'/includes';
            }
            $template->assign('textEditorDir', $textEditorDir);
            $template->assign('textEditorUrl', $textEditorUrl); # used in text editor include
        }
        $template->assign('filtersQuery', Request::convertToQuery(Request::get($regularId))); # This is necessary to create Request::set() in update.php, to use Request::get() in tdd-files.
        if ($blockSettings = unserialize(Blox::getBlockInfo($regularId, 'settings')))
            $template->assign('blockSettings', $blockSettings);
    }

    $template->assign('tab', $tab);
    if (!isset($_GET['add-new-rec'])) {
        $template->assign('dat', $tab[0]);
    }
    $template->assign('blockInfo', $blockInfo);
    # Edit select-data without leaving the edit window
    if ($getRecId) {
        if ($_GET['direction'] == 'right') {
            if ($aa = Str::getStringBeforeMark($_SERVER['HTTP_REFERER'], '&direction')) # Remove the tail to avoid duplication
                $_SESSION['Blox']['edit-referrers'][] = $aa;
            else
                $_SESSION['Blox']['edit-referrers'][] = $_SERVER['HTTP_REFERER'];
            $cancelUrl = $_SERVER['HTTP_REFERER'].'&direction=left';
            $direction = '&direction=left';
            if ($_GET['edit-field'])
                $direction .= '&edit-field='.$_GET['edit-field'];
        } elseif ($_GET['direction'] == 'left') {
            if ($aa = array_pop($_SESSION['Blox']['edit-referrers'])) {
                if ($aa = array_pop($_SESSION['Blox']['edit-referrers'])) {
                    $cancelUrl = $aa.'&direction=left';
                    $direction = '&direction=left';
                }
            }
        } else
            unset($_SESSION['Blox']['edit-referrers']);
        $template->assign('direction', $direction);
    }
    
    $template->assign('terms', $terms); # Do not replace to display.php
    $template->assign('description', Admin::getDescription($tpl));
    #
    $recEditUrl = Url::convertToRelative(preg_replace('~&pagehref=.*$~u', '', $_SERVER['REQUEST_URI']));
    $template->assign('recEditUrl', $recEditUrl);
    $template->assign('newEditUrl', preg_replace('~&rec=[^&]*~u', '&rec=new', $recEditUrl));
    $template->assign('multiEditUrl', preg_replace('~&rec=[^&]*~u', '', $recEditUrl));
    # EditBar
    if (!isset($_GET['add-new-rec'])) { # not ajax 
        if (Blox::info('user','id')) { # Do not show to visitor its own fresh public record
            if ($tdd[$xprefix.'params']['no-edit-bar'] && !Blox::info('user','user-is-admin')) # KLUDGE: Do by permission
                $noBar = true; # Do not show the bar
        }
    } else
        $noBar = true;

    if ($tdd[$xprefix.'types'])
        include Blox::info('cms','dir').'/includes/buttons-submit.php';
    else
        include Blox::info('cms','dir').'/includes/button-cancel.php';

    include Blox::info('cms','dir').'/includes/display.php';






    /**
     * @todo Optimize. Call it once in multirec mode
     */
    function getSelects($selectListTpl, $selectListBlockId, $selectListField, $templateparentidfield=null, $parentFieldValue=null, $xprefix=null) // , $blockInfo=null
    {   
        $selectListBlockInfo = Blox::getBlockInfo($selectListBlockId);
        $selectListTdd = Tdd::get($selectListBlockInfo);
        # Pick-navigation
        if ($templateparentidfield && $parentFieldValue)
            Request::add('block='.$selectListBlockId.'&p['.$templateparentidfield.']='.$parentFieldValue);
        $selectListTab = Request::getTab(['id'=>$selectListBlockId, 'src-block-id'=>$selectListBlockInfo['src-block-id'], 'tpl'=>$selectListTpl], $selectListTdd, $xprefix);
        if ($selectListTab) {
            foreach ($selectListTab as $dat)
                $selects[$dat['rec']] = $dat[$selectListField];
        }
        return $selects;
    }
