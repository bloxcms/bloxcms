<?php

    $refererPhref = Url::convertToRelative($_SERVER['HTTP_REFERER']);
    Request::set(); # This is necessary to use Request::get() in tdd-files.
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $recId = Sql::sanitizeInteger($_GET['rec']);
    # Transition from edit window in multi-record mode. "Select" list item is selected
    if (empty($recId) && $_POST['select-list-submit-rec']) {
        $recId = $_POST['select-list-submit-rec'];
        if (Request::get($regularId,'pick'))
            Blox::prompt($terms['multi-to-single']);
    }
        
    $pagehref = Blox::getPageHref();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
    $blockInfo = Blox::getBlockInfo($regularId);
    $srcBlockId = $blockInfo['src-block-id'];

    if ($_POST['xprefix']) {
        $xprefix = 'x';
        $xprefixQuery = '&xprefix='.$xprefix;
    } else
        $xprefix = '';
        
    $tpl = $blockInfo['tpl'];
    $tdd = Tdd::get($blockInfo);

    # Template Data Update Prehandler 
    $templateDataUpdatePrehandlerFile = Blox::info('templates', 'dir').'/'.$tpl.'.tuh'; # var name is deliberately lengthened 
    if (file_exists($templateDataUpdatePrehandlerFile)) {
        $udat = (function($templateDataUpdatePrehandlerFile) { # not $regularId. Same func is in Blox::getBlockHtm.php
            include $templateDataUpdatePrehandlerFile;
            return $udat;
        })($templateDataUpdatePrehandlerFile);
        //$udat = $getHandledData($templateDataUpdatePrehandlerFile); # Send to page.php
        if ($udat['unsave'])
            $GLOBALS['Blox']['save-udat-session']['unsave'] = true;
        $_SESSION['Blox']['udat'][$regularId] = $udat;
    }
    

  	Permission::addBlockPermits($srcBlockId, $tdd);
	if (Permission::get('record', [$srcBlockId]))
        ; # KLUDGE
    elseif ($tdd['params']['public']['editing-allowed']) {
        if ($_SESSION['Blox']['fresh-recs'][$srcBlockId][$_GET['rec']])
            ;
        elseif ($_GET['rec']=='new')
            ; # Holes
        else
        	Blox::execute('?error-document&code=403');
    } else
        Blox::execute('?error-document&code=403');
    # Cascading backward transition from one edit window to the previous one when editing a select-data
    if ($_GET['direction'] == 'left') {
        if ($aa = end($_SESSION['Blox']['edit-referrers'])) {
            $aa = str_replace(
                '&pagehref=', 
                '&direction=left&mode=add&edit-value='.$recId.'&edit-field='.$_GET['edit-field'].'&pagehref=', 
                $aa
            );
            Url::redirect($aa); # Redirection to the last link of the stack
        }
        else {
            Url::redirect($pagehref);
        }
    }
    # Select list option is selected. Return to edit window to select the dependent list
    elseif ($_POST['select-list-submit-field']) { 
        # Return to edit window
        Url::redirectToReferrer(['replacements' => ['~&rec=new~u' => '&rec='.$recId]]);
    }
    # The new record button is clicked in multi-record mode of edit window. Do not redirect, because this is ajax.
    elseif ($_POST['button-ok'] == 'add-new-rec') {
        # @todo Redirect to the page than again to edit window as in 'submit-and-return' mode. It is necessary to assign default templates in block data.
        # Consider requests pick[field][eq] as default data
        $defaults = [];
        if ($_GET['pick']) {
            foreach ($_GET['pick'] as $field => $aa)
                foreach ($aa as $comp=>$val) # breaks after one
                    if ($comp == 'eq')
                        $defaults[$field] = $val;
        }
        $dat = Dat::insert($blockInfo, $defaults, $xprefix, $tdd);
        if (!Blox::ajaxRequested()) {
            Url::redirectToReferrer();
            Blox::prompt($terms['no-ajax-in-multi'], true);
        }
    } else {
        Url::redirect($pagehref);
        if ($_POST['button-ok'] == 'submit-and-return')
            $_SESSION['Blox']['update']['submit-mode'] = 'submit-and-return';
    }

    # Update from the edit window
    if ('?edit&' == substr($refererPhref, 0, 6)) {
        $sendFromEditWindow = true;
    } elseif ('?import-columnwise&' == substr($refererPhref, 0, 19))
        ;
    else { 
        # Update from a page of the website
        if ($aa = getPublicRecId($blockInfo, $tdd, $xprefix, $terms)) # the user can also use a public form
            $recId = $aa;
        else {
            Url::redirect($pagehref,'exit');// for visitor forms
        }
    }

    # The data submitted by the visitor from the edit window
    if ($sendFromEditWindow && !Blox::info('user','id')) {
        if ($tdd['params']['public'] && ($tdd['params']['public']['editing-allowed'] || $_SESSION['Blox']['fresh-recs'][$srcBlockId][$recId]))
            ;
        else { # The visitor has not sent data from the edit window
            Url::redirect($pagehref,'exit');
        }
    }
    $GLOBALS['Blox']['types-details-f'] = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['file']);


    # Update from file batch
    if (isset($_GET['insert-data-from-file-batches']))
    {
        if ($blockInfo['src-block-id'])
        {
            $data = getDataFromFileBatches($blockInfo, $terms);
            if ($data) {
                foreach ($data as $ser => $dat) {
                    $aa = Dat::insert($blockInfo, [], $xprefix, $tdd);
                    $recId2 = $aa['rec'];
                    $dat['texts'] = copyFilenamesToFields($dat['files'], $dat['texts']);
                    if ($dat['texts'])
                        Dat::update(['src-block-id'=>$srcBlockId,'tpl'=>$tpl], $dat['texts'], ['rec'=>$recId2], $xprefix);

                    if ($dat['files']) {
                        # Replace the column numbers to the column names.
                        foreach ($dat['files'] as $field=>$aa)
                            $datafiles['dat'.$field] = $aa;
                        $typesDetails = Tdd::getTypesDetailsByColumns($GLOBALS['Blox']['types-details-f']);
                        $whereData = ['rec-id'=>$recId2,'block-id'=>$srcBlockId];
                        # There must be represented all file fields, even without data because some fields may use otherfields as a source
                        foreach ($typesDetails as $col=> $aa) {
                            if (!isset($datafiles[$col]['name'])) {# No file
                                $datafiles[$col]['name'] = ''; # key fileld
                                $datafiles[$col]['error'] = 4;
                            }
                        }
                        $tbl = Blox::getTbl($tpl, $xprefix);
                        Upload::updateFiles($tbl, $datafiles, $whereData, $typesDetails);
                    }
                }
            }
            else
                Blox::prompt($terms['no-files-to-import'], true);
        }
    }
    # standard editing (recordwise) by user or visitor, public data
    else
    {
        $updateDatAndFile = function($dat, $fdat, $nullDat, $deleteDat, $srcBlockId, $recId, $tpl, $tdd, $xprefix, $terms) # For single record editing. There is same non anonymous function
        {
            # Prehandler has not accepted the update
            if ($GLOBALS['Blox']['save-udat-session']['unsave']) {
                if ($_SESSION['Blox']['new-rec-id'] == $recId) { 
                    unset($_SESSION['Blox']['new-rec-id']); # for multi-record mode
                    $tbl = Blox::getTbl($tpl, $xprefix);
                    if (!Admin::deleteRec($tpl, $recId, $srcBlockId, $tbl))
                        Blox::error(sprintf($terms['failed-to-remove-rec'], $recId, $srcBlockId.'('.$tpl.')'));
                }
                return;
            }

            if ($tdd[$xprefix.'params']['sort']['editable'] && isset($dat['sort']))
                $sortNum = $dat['sort'];
                
            # KLUDGE: Now secret fields are no longer will be updated, but that data can be used in Permission class
            $dat2 = $dat;
            if ($secretFields = $tdd[$xprefix.'fields']['secret']) {
                $oldDat = Dat::get(['src-block-id'=>$srcBlockId, 'tpl'=>$tpl], ['rec'=>$recId], $xprefix);
                foreach ($secretFields as $secretField) {
                    $dat2[$secretField] = $oldDat[$secretField];
                    unset($dat[$secretField]); # against hacker updates of secret data
                }
            }

            if ($recId) {
                if (!Permission::ask('record', [$srcBlockId, $recId], ['dat'=>$dat2, 'tdd'=>$tdd])['edit'])//get
                    return;
            } elseif (!Permission::ask('record', [$srcBlockId])['']['edit'])//get
                return;

            ############### POST ###############
            # formatPostData
            # For data of "set" type: dat[3][element1] --> dat[3] = 'element1','element2','element3' 
            foreach ($dat as $field => $value) {
                if (is_array($value)) {
                    $aa = '';
                    foreach ($value as $setElement => $setValue) {
                        if ($setValue)
                            $aa .= ",{$setElement}";}
                    $aa = substr($aa, 1);
                    $dat[$field] = $aa;
                }
            }
            ksort($dat, SORT_NATURAL);
            # Check for number of fields
            $aa = array_keys($tdd[$xprefix.'types']);
            $lastTddField = max($aa);
            if ($dat && $srcBlockId) 
            {
                if ($_POST['select-list-submit-field']) {
                    if (
                        !isset($_POST['select-list-submit-rec']) || # single editing mode
                        $_POST['select-list-submit-rec'] == $recId # multi editing mode but treat only one record 
                    ) { # Clear Depended Select Fields
                        $typesDetailsS = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['select']);// $tddTypes, $addTypeParams, $separatedTypes
                        $dependedSelectFields = Admin::getDependedSelectFields($typesDetailsS, $_POST['select-list-submit-field']);
                        foreach ($dependedSelectFields as $field)
                            $dat[$field] = '';
                    }
                }

                # dont-convert-url
                # transform Texts
                if ($typesDetails_texts = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['tinytext', 'text', 'mediumtext', 'longtext'], 'only-name')) {     
                    if (!$_POST['data']) {  # single edit, not multi. Otherwise deletes text field data
                        # For $params['dont-convert-url']
                        $transformToPhref = function($text) {
                            $text2 =  preg_replace_callback(                             
                                '~(<a\s[^>]*href=)("??)([^"\s>]*?)\\2(\s|>)~siu',
                                function ($matches) {
                                    if ($href = Url::convertToRelative($matches[3])) {  # Inner link
                                        if (!Router::hrefIsParametric($href)) { 
                                            $href = Router::getPhrefByHhref($href); # Convert human url to parametric 
                                        }
                                    } else
                                        $href = $matches[3]; # Outer link
                                        
                                    if ($href)
                                        return $matches[1].$matches[2].$href.$matches[2].$matches[4]; 
                                    else
                                        return $matches[0]; # do not change
                                },
                                $text
                            );
                            return $text2 ?: $text;
                        };
                        # For $fields['reconvert-url']
                        $transformHrefs = function($text) {
                            $text2 =  preg_replace_callback(                             
                                '~(<a\s[^>]*href=)("??)([^"\s>]*?)\\2(\s|>)~siu',
                                function ($matches) {
                                    if ($href = Url::convertToRelative($matches[3])) {  # Inner link
                                        if (Blox::info('site','human-urls','convert')) { 
                                            if (Router::hrefIsParametric($href)) # Convert parametric url to human
                                                $href = Router::convert($href); 
                                        } elseif (!Router::hrefIsParametric($href)) { 
                                            $href = Router::getPhrefByHhref($href);# Convert human url to parametric 
                                        }
                                    } else # Outer link
                                        $href = $matches[3]; 
                                        
                                    if ($href)
                                        return $matches[1].$matches[2].$href.$matches[2].$matches[4]; 
                                    else
                                        return $matches[0]; # do not change
                                },
                                $text
                            );
                            return $text2 ?: $text;
                        };
                        # img
                        $transformSrcs = function($text) {
                            $text2 =  preg_replace_callback( 
                                '~(<img\s[^>]*src=)("??)([^"\s>]*?)\\2(\s|>)~siu', # Find "src" in images
                                function ($matches) {
                                    if ($href = Url::convertToRelative($matches[3]) ?: $matches[3])
                                        return $matches[1].$matches[2].$href.$matches[2].$matches[4]; 
                                    else
                                        return $matches[0]; # do not change
                                },
                                $text
                            );
                            return $text2 ?: $text;
                        };
                        foreach ($typesDetails_texts as $field => $aa) {
                            if ($dat[$field]) {
                                if ($tdd[$xprefix.'fields'][$field]['reconvert-url']) {
                                    $dat[$field] = $transformToPhref($dat[$field]); # Convert to relative URLs 
                                } elseif (!$tdd[$xprefix.'fields'][$field]['dont-convert-url'])
                                    $dat[$field] = $transformHrefs($dat[$field]);
                                #
                                if (!$tdd[$xprefix.'fields'][$field]['dont-convert-url'])
                                    $dat[$field] = $transformSrcs($dat[$field]);
                            }
                        }
                    }
                }
                # reconvert-url and convert-url
                # transform varchars
                if ($tdd[$xprefix.'fields']['reconvert-url'] || $tdd[$xprefix.'fields']['convert-url']) {
                    if ($typesDetails_varchars = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['varchar'], 'only-name')) {
                        # Convert URLs in Varchars 
                        if ($tdd[$xprefix.'fields']['reconvert-url']) {
                            $transformVarchars = function($text) {
                                if ($text2 = Url::convertToRelative($text)) {
                                    if (!Router::hrefIsParametric($text2))
                                        $text2 = Router::getPhrefByHhref($text2); # Convert human url to parametric
                                }
                                return $text2 ?: $text;
                            };                         
                            foreach ($tdd[$xprefix.'fields']['reconvert-url'] as $field) {
                                if ('varchar' == $typesDetails_varchars[$field]['name'])
                                    $dat[$field] = $transformVarchars($dat[$field]);
                            }
                        } 

                        if ($tdd[$xprefix.'fields']['convert-url']) {
                            $transformVarchars = function($text) {
                                if ($text2 = Url::convertToRelative($text)) {
                                    if (Blox::info('site','human-urls','convert')) { 
                                        # Convert parametric url to human
                                        if (Router::hrefIsParametric($text2))
                                            $text2 = Router::convert($text2);
                                    } elseif (!Router::hrefIsParametric($text2)) {
                                        # Convert human url to parametric
                                        $text2 = Router::getPhrefByHhref($text2); 
                                    }
                                }
                                return $text2 ?: $text;
                            };
                            foreach ($tdd[$xprefix.'fields']['convert-url'] as $field) {
                                if (in_array($field, $tdd[$xprefix.'fields']['reconvert-url']))
                                    Blox::prompt(sprintf($terms['convert-url3'], $field, $tpl.'.tdd'), true);
                                elseif ('varchar' == $typesDetails_varchars[$field]['name'])
                                    $dat[$field] = $transformVarchars($dat[$field]);
                                elseif ($typesDetails_texts[$field])
                                    Blox::prompt(sprintf($terms['convert-url1'], $field, $tpl.'.tdd'), true);
                                else
                                    Blox::prompt(sprintf($terms['convert-url2'], $field, $tpl.'.tdd'), true);
                            }
                        }
                    }
                }
                $dat = copyFilenamesToFields($fdat, $dat);
                foreach ($dat as $field => $d) {
                    
                    if ($dat[$field]) {
                        if ($tdd[$xprefix.'fields'][$field]['remove-elements'])
                            $dat[$field] = Text::removeElements($dat[$field], $tdd[$xprefix.'fields'][$field]['remove-elements']);
                    }
                    if ($dat[$field]) {
                        if (isset($tdd[$xprefix.'fields'][$field]['strip-tags']))
                            $dat[$field] = Text::stripTags($dat[$field], $tdd[$xprefix.'fields'][$field]['strip-tags']);
                    }
                    # When transferring data from multi-record edit window, plugin jquery.form.js passes in an array POST and a field for files. That differs from the normal post
                    if ($aa = $GLOBALS['Blox']['types-details-f'][$field]) {
                        # Move chunky file from temp dir to legal place
                        $dstFilename = '';
                        if ($aa['params']['chunk'][0] && $dat[$field]) {
                            if (file_exists($srcFile = Blox::info('site','dir').'/temp/fileupload/'.$dat[$field])) {
                                $options = [
                                    'move'=>true, 
                                    'dst-dir'=>$aa['params']['destination'][0] ?: 'datafiles', 
                                    'dst-file-name'=>$dat[$field]
                                ];
                                if ($dstFilename = Files::smartCopy($srcFile, '', $options)) {
                                    $dat[$field] = $dstFilename;
                                    deleteDat($tpl, $srcBlockId, $recId, [$field=>true], $xprefix); # KLUDGE: Delete old file                                    
                                    //Files::unLink($srcFile);
                                } 
                            } 
                        } 
                        if (!$dstFilename)
                            unset($dat[$field]); # Remove this data from the array POST, otherwise they will remove the names of the old files in the records.
                    }
                    
                    
                    # Check for number of fields
                    if ($field > $lastTddField) {
                        unset($dat[$field]);
                        Blox::error(sprintf($terms['excess-data'], '$'.$xprefix.'dat['.$field.']', $tpl, $lastTddField));
                    }

                    # Return the date to mysql format
                    if (Blox::info('site','date-time-formats', $tdd[$xprefix.'types'][$field])) {
                        $z = date_format(
                            date_create_from_format( # equivalent: DateTime::createFromFormat()
                                Blox::info(
                                    'site', 'date-time-formats', $tdd[$xprefix.'types'][$field]
                                ), $dat[$field]
                            ), 'Y-m-d H:i:s'
                        );
                        $dat[$field] = ($z[0] == '-') ? '' : $z; # '0000-00-00' is denied. It produces negative strtotime()
                    }
                    
                    if ($nullDat[$field])
                        $dat[$field] = null;
                    
                }

                # Edit the regular data
                if ($tdd[$xprefix.'params']['sort']['editable'] && isset($dat['sort']))
                    $dat['sort'] = $sortNum;


    
                Dat::update(['src-block-id'=>$srcBlockId, 'tpl'=>$tpl], $dat, ['rec'=>$recId], $xprefix);
            }

            ############### FILES ###############
            if ($fdat)
            {
                # Reduce the array of uploaded files to the "human" mind (the first key if field and replace the numbers of the columns in column names.
                foreach ($fdat as $field=>$aa) {
                    # Check for number of fields
                    if ($field > $lastTddField)
                        Blox::error(sprintf($terms['excess-data'], '$'.$xprefix.'dat['.$field.']', $tpl, $lastTddField));
                    else 
                        $uploadedFiles['dat'.$field] = $aa;                         
                }
                # Convert the field numbers in the names of the columns
                $typesDetails = Tdd::getTypesDetailsByColumns($GLOBALS['Blox']['types-details-f']);
                $whereData = ['rec-id'=>$recId,'block-id'=>$srcBlockId];
                $tbl = Blox::getTbl($tpl, $xprefix);
                if (!Upload::updateFiles($tbl, $uploadedFiles, $whereData, $typesDetails))
                    prompt('Upload::updateDataFiles returned false in update.php', true);
            }

            ############### deleteDat ###############
            # It applies to files
            if ($deleteDat) {
                do { # The completion of the $deleteDat with dependent files
                    $dependentsAreFound = false;
                    foreach ($GLOBALS['Blox']['types-details-f'] as $field => $typeDetail) {                        
                        if (!isset($deleteDat[$field]) && ($tdd[$xprefix.'fields'][$field]['secret'])) {
                            if ($sourcefield = $typeDetail['params']['sourcefield'][0]) {
                                if (isset($deleteDat[$sourcefield])) {# if the source is already subject to deletion
                                     $deleteDat[$field] = 1; # Include the dependent file in the list to delete 
                                     $dependentsAreFound = true;
                                }
                            }
                        }
                    }
                }
                while ($dependentsAreFound); # Do several passes, since there can be a chain of dependent files
            	deleteDat($tpl, $srcBlockId, $recId, $deleteDat, $xprefix);
            }
        };
        # multi-record edit window - $_POST['data']
        if (isset($_POST['data']) || isset($_FILES['data'])) {
            $normalizedFiles = Upload::format($_FILES);
            if ($_POST['data']) {
                foreach ($_POST['data'] as $r => $dat) {
                    $updateDatAndFile($dat, $normalizedFiles['data'][$r], $_POST['null-data'][$r], $_POST['delete-data'][$r], $srcBlockId, $r, $tpl, $tdd, $xprefix, $terms);
                }
            } elseif ($normalizedFiles) # No regular fields, but files only
                foreach ($normalizedFiles['data'] as $r => $fdat)
                    $updateDatAndFile('', $fdat, '', $_POST['delete-data'][$r], $srcBlockId, $r, $tpl, $tdd, $xprefix, $terms);
        }        
        else {  # singlerecord edit window - $_POST['dat']
            if (!$recId) {
                Blox::prompt('Record ID is not specified when updating single record of the block '.$srcBlockId.'('.$tpl.')', true);
            } else {
                $normalizedFiles = Upload::format($_FILES);
                $updateDatAndFile($_POST['dat'], $normalizedFiles['dat'], $_POST['null-dat'], $_POST['delete-dat'], $srcBlockId, $recId, $tpl, $tdd, $xprefix, $terms);
            }
        }
        
        # Save settings of edit window for this block
        if ($_POST['settings']) {            
            if (isset($_POST['data'])) { # Multi mode - the stamps are not configured and settings are not transferred
                $bb['stamps'] = Store::get($xprefix.'editSettings'.$srcBlockId)['stamps'];
                $aa = $bb + $_POST['settings'];}
            else
                $aa = $_POST['settings'];
            Store::set($xprefix.'editSettings'.$srcBlockId, $aa);
        }
    }

    # updates counting
    if ($srcBlockId)
        if (!Store::get('statisticsIsOff'))
    		Blox::statCount('updates', $srcBlockId);

    if (Blox::info('site', 'caching'))
        Admin::deleteByBlock($regularId); # Delete caches of updated pages
    Blox::updateBlockCache($regularId); #block-caching

    Router::updatePageInfoParamByUrl($pagehref, 'lastmod', date("Y-m-d H:i:s"));
    $_SESSION['Blox']['last-edited'] = ['src-block-id'=>$srcBlockId, 'rec-id'=>$recId];


    /**
     * This function is made to remove data of any type, but it Is used to remove only files
     */
    function deleteDat($tpl, $srcBlockId, $recId, $datToDel, $xprefix=null)  # if you have $recId, $blockId not needed
    {
        if ($datToDel) {
            foreach ($datToDel as $field => $aa) {
                Admin::deleteChilds($tpl, $recId, Sql::sanitizeInteger($field), $srcBlockId, $xprefix);
                $dat[$field] = '';
            }
        }
        # Insert empty values
        if ($dat) {
            Dat::update(['src-block-id'=>$srcBlockId, 'tpl'=>$tpl], $dat, ['rec'=>$recId], $xprefix);
        }
    }



	function getDirNodes($srcDir)
	{
    	$resource = opendir($srcDir);
    	while (false !== ($fname = readdir($resource)))
          	if ($fname != "." && $fname != "..")
           	    $fnames[] = $fname;

    	if (isset($fnames)) {
        	natcasesort($fnames); # Leaves old indices, though sorts, so it is better to touch them again. You can apply $fnames = array_values($fnames);
            $i = 0;
        	foreach ($fnames as  $fname) {
                $nodes[$i]['name'] = $fname;
                if (is_dir($srcDir."/".$fname))
                	$nodes[$i]['files'] = getDirNodes($srcDir."/".$fname);
                $i++;
            }
    	}
    	closedir($resource);
        return $nodes;
	}




    # NOT FOR EXTRADATA!
    function getBlockDataTypes($tpl)
    {
        if ($tdd = Tdd::get(['tpl'=>$tpl]))
            return $tdd['types'] ?: false;
    }




    function copyFilenamesToFields($fdat, $dat) {
        if ($fdat) {
            foreach ($fdat as $field => $fParams) {
                if ($aa = $GLOBALS['Blox']['types-details-f'][$field]['params']['copyfilenametofield']) {
                    if (empty($dat[$aa[0]])) {
                        if ($fileName = Str::getStringBeforeMark($fParams['name'], '.', true)) {
                            if ($aa[1] == 'capitalize')                                    
                                $fileName = mb_strtoupper(mb_substr($fileName, 0, 1)).mb_substr($fileName, 1);//$fileName = ucfirst($fileName);
                            $dat[$aa[0]] = $fileName;
                        }
                    }
                }
            }
        }
        return $dat;
    };
    


    /**
     * Unpack downloaded data packs and put them in folders in the temp directory.
     */
    function getDataFromFileBatches($blockInfo, $terms)
    {
        if (!arrangeBatches())
            return;
        $dataTypes = getBlockDataTypes($blockInfo['tpl']);
        $nodes = getDirNodes(Blox::info('site','dir')."/temp");
        foreach ($nodes as $node) {
        	if (isset($node['files'])) {# data in folder
                $field = (int)$node['name'];
                if ($field !=0) {
                    if ($usedFields[$field])
                        Blox::prompt(sprintf($terms['data-is-duplicated'], "<b>$field</b>"),  true);
                    else {
                        $usedFields[$field] = true;
                        if (isset($dataTypes[$field])) {
                            # data of file type
                            if ('file' == substr($dataTypes[$field], 0, 4)) {
                                foreach ($node['files'] as $ser => $aa) {
                                    //$siteDir = dirname($_SERVER['SCRIPT_FILENAME']);
                                    $data[$ser]['files'][$field]['name'] = $aa['name']; # Original name
                                    $data[$ser]['files'][$field]['tmp_name'] = Blox::info('site','dir')."/temp/".$field."/".$aa['name'];
                                    $data[$ser]['files'][$field]['error'] = 0;
                                }
                            } elseif ('page' == substr($dataTypes[$field], 0, 4))
                                Blox::prompt("{$terms['you-sent']} <b>$field</b>. {$terms['not-editable']} (page)",  true);
                            elseif ('block' == substr($dataTypes[$field], 0, 5))
                                Blox::prompt("{$terms['you-sent']} <b>$field</b>. {$terms['not-editable']} (block)",  true);
                            else { # to take the text from a file
                                foreach ($node['files'] as $ser => $aa)
                                    $data[$ser]['texts'][$field] = file_get_contents(Blox::info('site','dir')."/temp/".$field."/".$aa['name']); }
                        } else
                            Blox::prompt("{$terms['you-sent']} <b>$field</b>. {$terms['not-determined']}",  true);
                    }
                }
                else
                    Blox::prompt(sprintf($terms['uncorrect-filename'], '<b>'.$node['name'].'</b>'),  true);
            }
            else {# data are in txt file string by string
                list($name, $ext) = Str::splitByMark($node['name'], '.', true);
                $field = (int)$name;
                if ($field !=0) {
                    if ($usedFields[$field])
                        Blox::prompt(sprintf($terms['data-is-duplicated'], $field),  true);
                    else {
                        $usedFields[$field] = true;
                        if (isset($dataTypes[$field])) {
                            $lines = file(Blox::info('site','dir')."/temp/".$node['name']);
                            if ($lines)
                                foreach ($lines as $ser => $line)
                                    $data[$ser]['texts'][$field] = $line; }
                        else
                            Blox::prompt("{$terms['you-sent']} <b>$field</b>. {$terms['not-determined']}",  true);
                    }
                }
                else
                    Blox::prompt(sprintf($terms['uncorrect-filename'], '<b>'.$node['name'].'</b>'),  true);
            }
        }
        return $data;
    }
    function arrangeBatches()
    {
        if (empty($_FILES)){
            Blox::prompt('No file batches', true);
            return;}
        else {
            Files::cleanDir(Blox::info('site','dir').'/temp');
            foreach ($_FILES['batch']['name'] as $field => $srcFileName){
                if ($_FILES['batch']['name'][$field]) {
                    if ($_FILES['batch']['error'][$field]===0){                        
                        $parts = Str::splitByMark($_FILES['batch']['name'][$field], '.', true);
                        if (mb_strtolower($parts[1]) == 'zip') {
                            $zip = new ZipArchive;
                            if ($zip->open($_FILES['batch']['tmp_name'][$field]) === true){
                                if (!Files::makeDirIfNotExists(Blox::info('site','dir')."/temp/$field"))
                                    break;
                                if ($zip->extractTo(Blox::info('site','dir')."/temp/$field")) {
                                    # Packed file-data
                                    if ($GLOBALS['Blox']['types-details-f'][$field]['name']=='file') {                                        
                                        $success=true;
                                        # Russian file names are written incorrectly
                                        # Patch for Winrar  http://stackoverflow.com/questions/6163173/php-ziparchive-russian-language
                                        if ($files = glob(Blox::info('site','dir')."/temp/$field/*")) {
                                            foreach ($files as $f) {
                                                $fname = Str::getStringAfterMark($f, '/', true);
                                                $fname = iconv('cp866', 'utf-8', $fname); # WinRar uses old ms-dos charset 'cp866' for Cyrillic.
                                                rename($f, Blox::info('site','dir').'/temp/'.$field.'/'.$fname);
                                            }
                                            Blox::prompt('The patch for WinRar for cyrillic file names is applied');
                                        }
                                    }
                                    else {
                                        $files = glob(Blox::info('site','dir')."/temp/$field/*");
                                        # Packed text data in separate files
                                        if (isset($files[1])) # Unpacked more than one file
                                            $success=true;
                                        # Packed line-by-line data package. If in packaged file there is just one file, and this field is not file-data, then this package would be considered as line-by-line.
                                        else {
                                            if (!rename($files[0], Blox::info('site','dir')."/temp/$field.txt")) # Move the file higher and rename
                                                Blox::prompt("Could not rename file {$files[0]}", true);
                                            Files::deleteDir(Blox::info('site','dir')."/temp/$field");
                                            $success=true;
                                        }
                                    }
                                } else {
                                    Blox::prompt("Could not unpack file {$_FILES['batch']['type'][$field]}", true);
                                    break; }
                                $zip->close(); }
                            else
                                Blox::prompt("Could not unpack file $srcFileName", true);
                        }
                        # Move the file $_FILES["]['tmp_name'] in "temp" and rename
                        else {# Not packed
                            if (move_uploaded_file($_FILES['batch']['tmp_name'][$field], Blox::info('site','dir')."/temp/$field.txt")) {
                                chmod(Blox::info('site','dir')."/temp/$field.txt", 0644);
                                $success=true; }
                            else
                                Blox::prompt("Could not move file $srcFileName to temporary folder", true);
                        }
                    }
                    else
                        Blox::prompt(Upload::getUploadErrorDescription($_FILES['batch']['error'][$field]),  true);
                }
            }
            if ($success)
                return true;
        }
    }






    # $xprefix not used yet 
    function getPublicRecId($blockInfo, $tdd, $xprefix, $terms)
    {
        # is Public
       $regularId = $blockInfo['id'];
       $srcBlockId = $blockInfo['src-block-id'];
       $tpl = $blockInfo['tpl'];
       if (empty($regularId))# block
            return false;
        if (!Blox::info('user','id') && empty($tdd['params']['public'])) { # TODO: Check permissions of block
            Blox::prompt($terms['public-data-adoption'],  true);
            return false;
        }
        # rec
       	if (empty($_GET['rec'])){
            Blox::prompt("You must specify <b>rec</b> value (integer or 'new') in form action attribute of template<b> {$tpl}.tpl</b>!",  true);
            return false;
        }
        # for stamp
        $_POST['settings'] = Store::get('editSettings'.$srcBlockId); # Emulation for getConvertedFileName()
        if ('new' == $_GET['rec']) {
            $dat = Dat::insert($blockInfo, [], $xprefix, $tdd);
            $recId = $dat['rec']; } # for update
        else {
            if (
                Blox::info('user','id') || # TODO: Check permiddions om block
                ($tdd['params']['public'] && (
                    $tdd['params']['public']['editing-allowed'] || $_SESSION['Blox']['fresh-recs'][$srcBlockId][$recId]
                    )
                )
            ) {
                ;
            }
            else {
                return false;
            }
            $recId = $_GET['rec'];
        }
        return $recId; # Public data are allowed to insert in the database
    }