<?php

class Tdd 
{
    private static 
        $delegatedBlocks = [],
        $childrenOfDelegatedBlocks = []
    ;
    
    /** 
     * Get vars of tdd file.
     *
     * @param array $blockInfo full array obtained by method Blox::getBlockInfo($regularId). It is acceptable to pass only the element ['tpl'=>...]
     * @return array of tdd-file variables
     */
    public static function get($blockInfo)
    {
        if (!is_array($blockInfo)) {
            Blox::prompt(Blox::getTerms('arg-is-not-arr'), true);
            return []; ##return false;
        }
        if ($blockInfo['tpl']) {
            $tpl = $blockInfo['tpl'];
        } else {
            Blox::error(sprintf(Blox::getTerms('no-tpl'), $blockInfo['src-block-id'])); //prompt
            ##return false;
        }

        #blockHasDelegatedAncestor
        if ($blockInfo['id'] && $blockInfo['parent-block-id']) { # i.e. $blockInfo is not created manualy
            $zz = false;
            if (self::$childrenOfDelegatedBlocks[$blockInfo['parent-block-id']])
                $zz = true;
            if (!$zz && self::$delegatedBlocks[$blockInfo['parent-block-id']])
                $zz = true;
            self::$childrenOfDelegatedBlocks[$blockInfo['id']] = $zz;
            self::$delegatedBlocks[$blockInfo['delegated-id']] = ($blockInfo['delegated-id'])
                ? true
                : false
            ;
        }
        
        #EXPERIMENTAL
        if ($GLOBALS['Blox']['tdd'][$tpl]) {
            return $GLOBALS['Blox']['tdd'][$tpl];
        }               
        # For admin and editor
        if (Blox::info('user','user-is-admin') && !isset($_GET['templates'])) {
            $tpl = Sql::sanitizeTpl($tpl);
            if (!$tpl)
                return []; ##return false;
            $tbl = Blox::getTbl($tpl);
            $xtbl = Blox::getTbl($tpl,'x');
            # Check whether tdd file was modified
            $newTddFile = Blox::info('templates', 'dir').'/'.$tpl.'.tdd';
            if (file_exists($newTddFile)) {
                $newTddTime = filemtime($newTddFile);
                $oldTddFile = 'assigned/'.$tpl.'.tdd';
                if (Sql::tableExists($tbl))
                    $tblExists = true;
                if (Sql::tableExists($xtbl))
                    $xtblExists = true;
                # Old file exists
                if (file_exists($oldTddFile)) {
                    $oldTddTime = filemtime($oldTddFile); # Unix timestamp
                    $tdd = self::getDirectly($oldTddFile, $blockInfo);
                    if ($newTddTime > $oldTddTime)
                        $newTdd = self::getDirectly($newTddFile, $blockInfo);
                    # tab 
                    if ($tblExists) {
                        if ($newTddTime > $oldTddTime) { # Tdd edited. Do not use '!='
                            if (self::verifyTable($tpl, $newTddFile, $oldTddFile, '', $blockInfo)) # Modify table and delete dependent objects
                                $replaceTddFile = true;
                        }
                    } else { # No table
                        if ($tdd['types']) {
                            $createEmptyDataTable = true;
                            $replaceTddFile = true;
                        } elseif ($newTddTime > $oldTddTime) { # Tdd edited. Do not use '!='
                            if ($newTdd['types']) # 2016-11-23
                                $createEmptyDataTable = true;
                            $replaceTddFile = true;
                        }
                    }
                    # xtab 
                    if ($xtblExists) {
                        if ($newTddTime > $oldTddTime) # Tdd edited. Do not use '!='
                            if (self::verifyTable($tpl, $newTddFile, $oldTddFile, 'x', $blockInfo)) # Modify table and delete dependent objects
                                $replaceTddFile = true;
                    } else { # No table
                        if ($tdd['xtypes']) {
                            $createEmptyXdataTable = true;
                            $replaceTddFile = true;
                        } elseif ($newTddTime > $oldTddTime) { # Types could be appeared in newTddFile
                            if ($newTdd['xtypes']) # 2016-11-23
                                $createEmptyXdataTable = true;
                            $replaceTddFile = true;
                        }
                    }
                }
                # Old file does not exist
                else {       
                    if ($tblExists || $xtblExists) { # But table exists 
                        # It means the file was deleted incorrectly
                        Blox::prompt(sprintf(Blox::getTerms('no-old-tdd'), '<b>templates/'.$tpl.'.tdd</b>', '<b>'.$tbl.'</b>'), 1);
                        return []; ##return false;
                    }
                    else { # No table 
                        if (!$tblExists)                
                            $createEmptyDataTable = true;
                        if (!$xtblExists)                
                            $createEmptyXdataTable = true;
                        $replaceTddFile = true;
                    }                                                     
                }
                
                if ($replaceTddFile) {
                    self::replaceTddFile($newTddFile, $oldTddFile, $newTddTime);
                    $tdd = self::getDirectly($oldTddFile, $blockInfo);
                }   
                if ($createEmptyDataTable) {                    
                    if ($tdd['types'])
                        Admin::createEmptyDataTable($tbl, $tdd);
                }
                if ($createEmptyXdataTable) {
                    if ($tdd['xtypes'])
                        Admin::createEmptyDataTable($xtbl, $tdd, 'x');
                }
            }
            #file $newTddFile not exists
            else {
                if (file_exists($oldTddFile)) {
                    if ($tblExists || $xtblExists)
                        Blox::prompt(sprintf(Blox::getTerms('no-main-tpl2'), '<b>templates/'.$tpl.'.tdd</b>'), true);
                    else {  
                        # Delete old file, because the main file does not exist
                        if (!Files::unLink($oldTddFile))
                            Blox::prompt(sprintf(Blox::getTerms('no-main-tpl'), '<b>templates/'.$tpl.'.tdd</b>'), true);
                    }
                } else { # file $oldTddFile does not exist
                    if (Sql::tableExists($tbl)) # TODO: function to remove these tables and their dependent data
                        Blox::prompt(sprintf(Blox::getTerms('no-both-tdds'), '<b>'.$tpl.'</b>'), true);
                }
            }
        }        
        else# For visitors and editors of blocks
        {
            $oldTddFile = 'assigned/'.$tpl.'.tdd';
            if (file_exists($oldTddFile)) {
                $tdd = self::getDirectly($oldTddFile, $blockInfo);
                if (Permission::ask('record', [$blockInfo['src-block-id']])['']['edit']) {//get
                    $oldTddTime = filemtime($oldTddFile);//Unix timestamp
                    $newTddFile = Blox::info('templates', 'dir').'/'.$tpl.'.tdd';
                    if (file_exists($newTddFile)) {
                        $newTddTime = filemtime($newTddFile);
                        if ($newTddTime > $oldTddTime)
                            Blox::prompt(sprintf(Blox::getTerms('tdd-edited'), '<b>'.$tpl.'.tdd</b>'), true);
                    }
                }
            }
        }

        #EXPERIMENTAL
        # Run tdd-file only once
        if ($tdd['params']['run-tdd-once'] && !$GLOBALS['Blox']['tdd'][$tpl])
            $GLOBALS['Blox']['tdd'][$tpl] = $tdd;
        # DEPRECATED since v-14
        if ($tdd['params']['binding-phref-field']) {
            self::bind(
                [
                    'block-id'=>$blockInfo['id'], 
                    'field'=>$tdd['params']['binding-phref-field'], 
                    'value'=>Url::encode(Router::getPhref(Blox::getPageHref())),
                ], 
                $tdd['keys'], $tdd['defaults']
            );
        }
        return $tdd;
    }




    # Remove parameters of types
    private static function truncateSpecTypesParams($s)
    {
        # Remove leading spaces and tabs
        $s1= ltrim($s);
        $s2 = mb_strtolower($s1);
        if ('block' == substr($s2, 0, 5))
            $s2 = 'block';
        elseif ('file' == substr($s2, 0, 4))
            $s2 = 'file';
        elseif ('page' == substr($s2, 0, 4))
            $s2 = 'page';
        elseif ('select' == substr($s2, 0, 6))
            $s2 = 'select';
        elseif ('set' == substr($s2, 0, 3)) # In declaration of "set" register is important
            
            return $s1;
        elseif ('enum' == substr($s2, 0, 4)) # In declaration of "enum" register is important
            return $s1;
        # MySQL types remains unchanged
        return $s2;
    }





    # Protects against invinite loop but does not check the updates
    public static function getDirectly($tddFile, $blockInfo)
    {
        if ($_SESSION['Blox']['udat'][$blockInfo['id']])
            $udat = $_SESSION['Blox']['udat'][$blockInfo['id']];
        if ($_SESSION['Blox']['dpdat'][$blockInfo['id']])
            $dpdat = $_SESSION['Blox']['dpdat'][$blockInfo['id']];
        if ($_SESSION['Blox']['drdat'][$blockInfo['id']])
            $drdat = $_SESSION['Blox']['drdat'][$blockInfo['id']];
        # Descriptor's Prehandler
        if (file_exists(Blox::info('templates', 'dir').'/'.$blockInfo['tpl'].'.tddh')) {
            (function(&$blockInfo, &$ddat, &$udat, &$dpdat, &$drdat) {
                include Blox::info('templates', 'dir').'/'.$blockInfo['tpl'].'.tddh';
            })($blockInfo, $ddat, $udat, $dpdat, $drdat);
            //$aa($blockInfo, $ddat, $udat, $dpdat);
        }
        # Long unique name: xxxTddFile()
        return (function($xxxTddFile, &$blockInfo, &$ddat, &$udat, &$dpdat, &$drdat) {
            include $xxxTddFile;
            if ($params['part']['numbering'])
                $params['part']['numbering'] = mb_strtolower($params['part']['numbering']);
            foreach (['titles', 'types', 'defaults', 'params', 'captions', 'notes', 'styles', 'mstyles', 'tooltips', 'keys', 'options'] as $k) {
                if (isset($$k))
                    $tdd[$k] = $$k;
                $bk = 'x'.$k;
                if (isset($$bk))
                    $tdd[$bk] = $$bk;
            }
            # Format fields
            if (isset($fields) || isset($xfields)) {
                $xxxFormatFields = function($ar) {
                    foreach ($ar as $k1 => $v1) {
                        if ($v1) {
                            if (is_int($k1)) { # By field key
                                foreach ($v1 as $k2 => $v2) {
                                    if (is_int($k2)) { # simple array
                                        $farr[$k1][$v2] = true;     # [field][param]=value
                                        $parr[$v2][] = $k1;         # [param]=[fields]
                                    } elseif (is_string($k2)) { # normal array
                                        $farr[$k1][$k2] = $v2;      # [field][param]=value
                                        //if (is_bool($v2))
                                            $parr[$k2][] = $k1;     # [param]=[fields]
                                    }   
                                }
                            } elseif (is_string($k1)) { # By param assoc key
                                foreach ($v1 as $k2 => $v2) {
                                    if (is_int($k2)) {
                                        $farr[$v2][$k1] = true; # [field][param]=value
                                        $parr[$k1][] = $v2;     # [param]=[fields]
                                    }
                                }
                            }
                        }
                    }
                    # sort
                    foreach ($farr as $f => $p) {
                        ksort($p);
                        $arr[$f] = $p;
                    }
                    foreach ($parr as $p => $enum) {
                        sort($enum);
                        $arr[$p] = $enum;
                    }
                    ksort($arr);
                    return $arr;
                };
                if (isset($fields))
                    $tdd['fields'] = $xxxFormatFields($fields);
                if (isset($xfields))
                    $tdd['xfields'] = $xxxFormatFields($xfields);
            }
            return $tdd;
        })($tddFile, $blockInfo, $ddat, $udat, $dpdat, $drdat);
    }





    private static function replaceTddFile($src, $dst, $newTddTime)
    {
        try { # Try tdd file for fatal error. 
            (function($src){include($src);})($src);
        } catch (Exception $e) {
            return false; # Do not copy wrong tdd file to "assigned" folder
        }
        # Remove BOM
        $str = file_get_contents($src);        
        # Strips the UTF-8 mark: (hex value: EF BB BF)
        if (substr($str, 0, 3) == pack("CCC",0xef,0xbb,0xbf)) { # pack('CCC', 239, 187, 191)) 
            $str =  substr($str, 3);
            $isWithBom = true;
            Blox::prompt(sprintf(Blox::getTerms('with-bom'), '<b>'.str_replace(Blox::info('site','dir').'/', '', $src).'</b>'),  true);
        }
        #
        if (file_put_contents($dst, $str))
            $isCopied = true;
        else {
            $isCopied = false;            
            # Second attempt
            if (Files::smartCopy($src, $dst)) {
                $isCopied = true;
                if ($isWithBom)
                    Blox::prompt(sprintf(Blox::getTerms('remove-bom'), '<b>'.str_replace(Blox::info('site','dir').'/', '', $dst).'</b>'),  true);
            }
        }
        #
        if ($isCopied) {
            clearstatcache(); # procedure - no return
            if (!touch($dst, $newTddTime)) 
                Blox::error(sprintf(Blox::getTerms('not-touched'), $dst));
        } else
            Blox::error(sprintf(Blox::getTerms('not-copied'), $src, $dst));
    }




    private static function verifyTable($tpl, $newTddFile, $oldTddFile, $xprefix=null, $blockInfo=null)
    {
        $oldTdd = self::getDirectly($oldTddFile, $blockInfo);
        //$oldTdd = [];
        $newTdd = self::getDirectly($newTddFile, $blockInfo);
        # KLUDGE: Now first are changed the columns, then changed the table itself
        # TODO: move the old data in the updated table at once
        if (self::verifyTableColumns($tpl, $newTdd, $oldTdd, $xprefix)) {
            if (self::verifyKeysAndOptions($tpl, $newTdd, $oldTdd, $xprefix)) {
                return true;
            }
        }
    }



    private static function verifyKeysAndOptions($tpl, $newTdd, $oldTdd, $xprefix=null)
    {
        # KLUDGE: If index or option are changed, create new table without any analysis 
        $changed = false;
        # db-refresh. The same procedure for tdd['types'] will be done by verifying the real table structure. See Admin::getTabFieldsInfo()
        if ($refreshTpls = Store::get('blox-refresh-db-tpls', $refreshTpls))
            if ($refreshTpls[$tpl]) {
                $changed = true;
                unset($refreshTpls[$tpl]);
                if ($refreshTpls)
                    Store::set('blox-refresh-db-tpls', $refreshTpls);
                else
                    Store::delete('blox-refresh-db-tpls');
        }
        # 
        if (!$changed) {
            # Remove double spaces, breaks, blank lines
            $oldKeys = mb_strtolower(preg_replace( "/\s{2,}/u", ' ', $oldTdd[$xprefix.'keys']));
            $newKeys = mb_strtolower(preg_replace( "/\s{2,}/u", ' ', $newTdd[$xprefix.'keys']));
            if ($newKeys != $oldKeys)
                $changed = true;
            else {
                $oldOptions = mb_strtolower(preg_replace( "/\s{2,}/u", ' ', $oldTdd[$xprefix.'options']));
                $newOptions = mb_strtolower(preg_replace( "/\s{2,}/u", ' ', $newTdd[$xprefix.'options']));
                if ($newOptions != $oldOptions)
                    $changed = true;
            }
        }   
        #
        if ($changed) {
            # Create temp table 
            if ($newTdd[$xprefix.'types']) {
                $tb = Blox::getTbl($tpl, $xprefix, true);
                $tbl = '`'.$tb.'`';
                $tempTbl = '`'.$tb.'_temporary`';
                if (Sql::query('DROP TABLE IF EXISTS '.$tempTbl) !== false) {
                    if (Admin::createEmptyDataTable($tempTbl, $newTdd, $xprefix) !== false) {
                        # Copy data to new table
                        if (Sql::query('INSERT IGNORE INTO '.$tempTbl.' SELECT * FROM '.$tbl) !== false) { # I recommend INSERT...ON DUPLICATE KEY UPDATE http://stackoverflow.com/questions/548541/insert-ignore-vs-insert-on-duplicate-key-update
                            # Delete old table
                            if (Sql::query('DROP TABLE IF EXISTS '.$tbl) !== false) {
                                # Rename new table
                                if (Sql::query('RENAME TABLE '.$tempTbl.' TO '.$tbl) !== false) {
                                    return true;
                                }
                            }
                        }
                    } else {
                        Blox::error("Failed on new table {$tpl}_temporary being created");
                    }
                }
            }
        } else
            return true;
    }






    private static function verifyTableColumns($tpl, $newTdd, $oldTdd, $xprefix=null)
    {
        $typesAlterations = self::findTypesAlterations($tpl, $oldTdd, $newTdd, $lastOldField, $xprefix); #&$lastOldField
        if (empty($typesAlterations))
            return true;
        $refTypes = ['block', 'page', 'file'];
        $tbl = Blox::getTbl($tpl, $xprefix);
	    foreach ($typesAlterations as $field => $alteration) {
            # Sql snippets
	        if ($field == 1)
	            $prevFieldName = 'rec-id';
	        else {
	            $aa = $field - 1;
	            $prevFieldName = "dat$aa" ;
	        }
            $newSqlType = Admin::reduceToSqlType($alteration['new-type']);
            $sqlAdd = "ALTER TABLE $tbl ADD `dat{$field}` $newSqlType AFTER `$prevFieldName`";
            $sqlAddDesert = "ALTER TABLE $tbl ADD `dat{$field}` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `$prevFieldName`";
            $sqlDrop = "ALTER TABLE $tbl DROP `dat{$field}`";            
            $sqlModify = "ALTER TABLE $tbl MODIFY `dat{$field}` $newSqlType";// otherwise occurs error when changing text to varchar 
            if (isset($alteration['new-type'])) # declared new type, even empty
            {
                if ($alteration['new-type']) { # new type is given
                    if ($alteration['old-type']) { # old field was used
                        if (in_array($alteration['old-type'], $refTypes)) { # old field was special
                            if (!self::deleteChildsAndColumn($tpl, $tbl, $field, $xprefix))
                                continue;
                            if (Sql::query($sqlAdd)===false) {
                                $error = true; continue;
                            } else
                                $success = true;
                        } else { # old field was of mysql type
                            if (in_array($alteration['new-type'], $refTypes)) {  # new field is special
                                $sql = 'SELECT `rec-id`, ?, `block-id` FROM '.$tbl;
                                $result = Sql::query($sql, ['dat'.$field]);
                                if (!$result)
                                    continue;
                                while ($row = $result->fetch_row()) { # not assoc!
                                    $tabb[] = [
                                        'dat'=>['rec' => $row[0], $field => $row[1]],
                                        'block-id'=>$row[2]
                                    ];
                                }
                                $result->free();
                                # I replaced $sqlModify by three-step query
                                if (Sql::query($sqlDrop)===false) {
                                    $error = true; continue;
                                } else
                                    $success = true;
                                if (Sql::query($sqlAdd)===false) {
                                    $error = true; continue;
                                } else
                                    $success = true;
                                foreach ($tabb as $aa) {
                                    $dat = $aa['dat'];
                                    if (!Dat::update(['src-block-id'=>$aa['block-id'],'tpl'=>$tpl], $dat, ['rec'=>$dat['rec']], $xprefix))
                                        continue;
                                }
                            } else {
                                if (Sql::query($sqlModify)===false) {
                                    # Mysql "nulled to not nulled" bug fix via UPDATE
                                    if (mb_strpos(strtolower($alteration['new-type']), 'not null') !== false) { 
                                        if (preg_match('~default\s+(.*?)(\s|$)~iu', $alteration['new-type'], $matches)) {
                                            if (Sql::query('UPDATE '.$tbl.' SET dat'.$field.'=? WHERE dat'.$field.' IS NULL', [trim($matches[1], "'")])===false) {
                                                $error = true; continue;
                                            } else {
                                                if (Sql::query($sqlModify)===false) {
                                                    $error = true; continue;
                                                } else
                                                    $success = true;
                                            }
                                        }
                                    } else {   
                                        $error = true; continue;
                                    }
                                } else
                                    $success = true;
                            }
                        }
                    }
                    else { # old field was absent
                        if ($field > $lastOldField) { # create new field
                            if (Sql::query($sqlAdd)===false) {
                                $error = true; continue;
                            } else
                                $success = true;
                        } else { # The field was temporarily not used, now it will be used
                            if (Sql::query($sqlDrop)===false) {
                                $error = true; continue;
                            } else
                                $success = true;
                            if (Sql::query($sqlAdd)===false) {
                                $error = true; continue;
                            } else
                                $success = true;
                        }
                    }
                }
                else { # New type is not given, that means preserved field
                    if (!self::deleteChildsAndColumn($tpl, $tbl, $field, $xprefix))
                        continue;
                    if (Sql::query($sqlAddDesert)===false) {
                        $error = true; continue;
                    } else
                        $success = true;
                }
            } else { # Remove empty tail (no new type)
                if (!self::deleteChildsAndColumn($tpl, $tbl, $field, $xprefix))
                    continue;
            }

	    }
        #
        if ($error) {
            Blox::prompt(sprintf(Blox::getTerms('table-altering-error'), $tbl), true);
            if ($success)
                return true; # Because some columns have already been updated
        } else
            return true;
    }




    private static function deleteChildsAndColumn($tpl, $tbl, $field, $xprefix=null)
    {
        # If the field realy exists in the table
        if (isset(Admin::getTabFieldsInfo($tpl, $xprefix)['dat'.$field])) {
            if (!Admin::deleteChilds($tpl, 'all', $field, 'all', $xprefix))
                return false;
            $sqlDrop = "ALTER TABLE $tbl DROP dat{$field}"; # Remove child blocks of the field
            if (isEmpty(Sql::query($sqlDrop)))
                return false;
        }
        return true;
    }



    private static function findTypesAlterations($tpl, $oldTdd, $newTdd, &$lastOldField=null, $xprefix='')
    {   
        $oldTypes = $oldTdd[$xprefix.'types']; # Compare tdd
        $newTypes = $newTdd[$xprefix.'types'];
        $aa = array_keys($newTypes);
	    $lastNewField = max($aa);
        $aa = array_keys($oldTypes);
        $lastOldField = max($aa);
        # Analize table
        $tabFieldsInfo = Admin::getTabFieldsInfo($tpl, $xprefix);
        unset($tabFieldsInfo['rec-id']);
        unset($tabFieldsInfo['block-id']);
        foreach ($tabFieldsInfo as $fieldName => $aa) {
            $field = (int)substr($fieldName, 3);  # remove initial  'dat'
            $tabFieldsExistences[$field] = true;
        }
        ksort($tabFieldsExistences);
        # Conform $oldTypes with db table 
        $aa = array_keys($tabFieldsExistences);
	    $lastTabField = max($aa);
        if ($lastTabField > $lastOldField)
            $last = $lastTabField;
        else
            $last = $lastOldField;

	    for ($field = 1; $field <= $last; $field++) {
            if (empty($oldTypes)) {# Missing column in oldTdd 
                if ($tabFieldsExistences[$field])# But such column exists in the table
                    $oldTypes[$field] = 'aaa'; # in oldTdd assign fake field type that certainly does not match newTdd type to start the update
            } else { # if oldTdd have an unnecessary column
                if (!$tabFieldsExistences[$field]) # But in the table there is no such column
                    unset($oldTypes[$field]);}# Formally remove this field in oldTdd 
	    }
        $aa = array_keys($oldTypes);
        $lastOldField = max($aa);
        $newTypesDetails = self::getTypesDetails($newTypes); # To do: self::getTypesDetails($newTdd['types'], [], 'only-name')  ?
	    for ($field = 1; $field <= $lastNewField; $field++) {
            $newTruncType = self::truncateSpecTypesParams($newTypes[$field]);
            $oldTruncType = self::truncateSpecTypesParams($oldTypes[$field]);
            if ($newTruncType) { # New field is given 
                if ($newTruncType != $oldTruncType) { # I\Old and new fields are different
                    $typesAlterations[$field] = ['old-type' => $oldTruncType, 'new-type'=>$newTruncType];
                # Types are equal, but for insurance we examine types in the database
                } elseif (!in_array($newTypesDetails[$field]['name'], ['block', 'page', 'file', 'select'])) { # for special types
                    # Compare only names of types and in its params in braces
                    if (
                        $newTypesDetails[$field]['name'] != $tabFieldsInfo['dat'.$field]['Type'] || # Type's names not matched
                        $newTypesDetails[$field]['params'][$newTypesDetails[$field]['name']] != $tabFieldsInfo['dat'.$field]['params'] # # Array of params not matched 
                    ) {
                        $typesAlterations[$field] = ['old-type' => 'aaa', 'new-type'=>$newTruncType];# fake field type that certainly does not match newTdd type to start the update
                    }
                }
            } else { # new field is not given
                if ($oldTruncType) # old field exists
                    $typesAlterations[$field] = ['old-type' => $oldTruncType, 'new-type'=>''];
                else # old field does not exist (missing keys in old and new tdd file)
                    $typesAlterations[$field] = ['old-type' => '', 'new-type'=>''];
            }
	    }
        # Remove garbage tail (do not declare new types)
        if ($lastNewField < $lastOldField) { # garbage tail 
            $oldTruncType = self::truncateSpecTypesParams($oldTypes[$field]);
	        for ($field=$lastNewField+1; $field <= $lastOldField; $field++)
                $typesAlterations[$field] = ['old-type' => $oldTruncType]; # if new type was not declared, the column will be deleted.
        }
        return $typesAlterations;
    }






    /**
     * Get names and params of data types declared in tdd-files. Params will be converted from string format to array.
     * 
     * @param array $tddTypes The array "$types" from a tdd file.
     * @param bool $onlyName Get only names of data types, not params of the types. 
     * @param $certainTypes Treat only certain data types, otherwise all data.
     * @return array 
     * 
     * @example $typesNames = Tdd::getTypesDetails($tdd['types'], ['page','block','file','select'], 'only-name');
     * @todo Add Files::normalizeTpl() for params: template(), but in many places of CMS templates are normalized 
     * 
     */
    public static function getTypesDetails($tddTypes, $certainTypes=[], $onlyName=false)
    {
        if (empty($tddTypes))
            return false;
        foreach ($tddTypes as $field => $type) {
            if ($type) {
                # Full retrieve (fast)
                if (empty($certainTypes) && !$onlyName) {
                    $params = self::getTypeParams($type);
                    $typesDetails[$field]['name'] = key($params);
                    $typesDetails[$field]['params'] = $params; 
                # Not full retrieve 
                } elseif (preg_match("/^\s*?(\w+?)(?=\s|[(]|$)/", $type, $matches)) { # Get the name of the type (the first member), i.e. find the word up to the first parenthesis or the first space
                    $name = mb_strtolower($matches[1]);
                    $toTreat = false;
                    if ($certainTypes) {
                        if (in_array($name, $certainTypes))
                            $toTreat = true;
                    } else # All types
                        $toTreat = true;

                    if ($toTreat) {
                        $typesDetails[$field]['name'] = $name;
                        if (!$onlyName) 
                            $typesDetails[$field]['params'] = self::getTypeParams($type); 
                    }
                }
            }
        }
        return $typesDetails;
    }

    /**
     * Get name and params of data type
     * For alternative form of types: block, page, select, restores full names of params.
     */
    public static function getTypeParams($type)
    {
        # Determine parameters as is (without considering short form)
        $params = (function($type) {
            $groups = explode(')', $type);
            foreach ($groups as $group)  {
                if ($group) { # dd bb ss ( 33, 44
                    # There is param with parentheses
                    if (strpos($group, '(')) {
                        # WordSpaceLetter or WordParenthesisAll
                        if (preg_match_all("/(?:(\w+)\s+(?=\w))|(\w+\s?[(].*)/", $group, $shreds)) {
                            foreach ($shreds[0] as $shred) { # ss ( 33, 44
                                $name = '';
                                $values = [];
                                $element = explode('(', $shred);
                                if ($element[0]) {
                                    $name = mb_strtolower(trim($element[0]));
                                    if ($element[1]) { # Param with parentheses
                                        $inParenthesis = $element[1]; # 33, 44
                                        $arguments = explode(',', $inParenthesis);
                                        foreach ($arguments as $argument) {
                                            $argument = trim($argument);
                                            $argument = preg_replace("/(^')|('$)|(^\")|(\"$)/u", '', $argument);# remove the quotation marks at the beginning and in the end
                                            $values[] = $argument;
                                        }
                                    }
                                    if ($values)
                                        $params[$name] = $values;
                                    else # No arguments (in parentheses)
                                        $params[$name] = '';
                                }
                            }
                        }
                    } else { # Params without arguments 
                        $names = explode(' ', $group);
                        foreach ($names as $name) {
                            $name = mb_strtolower(trim($name));
                            if ($name)
                                $params[$name] = '';
                        }
                    }
                }
            }
            return $params;
        })($type);
        //$params = $getRawTypeParams($type);
        
        $typeName = key($params); # The first parameter is name
        # Restore full names of params for alternative form of types: block, page, select
        if ($typeName == 'block') {
            if ($params['block'][0] && empty($params['template'][0]))
                $params['template'][0] = $params['block'][0];
            if ($params['block'][1] && empty($params['option'][0]))
                $params['option'][0] = $params['block'][1];
        } elseif ($typeName == 'page') {
            if ($params['page'][0] && empty($params['template'][0]))
                $params['template'][0] = $params['page'][0];
            if ($params['page'][1] && empty($params['option'][0]))
                $params['option'][0] = $params['page'][1];
        } elseif ($typeName == 'select') { # select template('cities')  edit(1) parentField(2)
            # Short form
            if ($params['select'][0] && empty($params['template'][0]))
                $params['template'][0] = $params['select'][0];     
            if ($params['select'][1] && empty($params['edit'][0]))
                $params['edit'][0] = $params['select'][1];  
            if ($params['select'][2] && empty($params['parentfield'][0]))
                $params['parentfield'][0] = $params['select'][2];
            if ($params['select'][3] && empty($params['templateparentidfield'][0]))
                $params['templateparentidfield'][0] = $params['select'][3];
            
            # Defaults \\\\\\\\\\\\\\\\\\\\\\
            # edit
            if (empty($params['edit'][0]))
                $params['edit'][0] = 'rec';
            # output
            if (empty($params['output'][0]))
                $params['output'][0] = $params['edit'][0];            
            # pick
            if (empty($params['pick'][0]))
                $params['pick'][0] = 'rec';
            # search
            if (empty($params['search'][0]))
                $params['search'][0] = $params['edit'][0];
            # sort
            if (empty($params['sort'][0]))
                $params['sort'][0] = $params['edit'][0];
            #////////////////////
        }
        return $params;
    }


    
    

    /**
     * Replace field name keys by column name in array $typesDetails
     */
    public static function getTypesDetailsByColumns($typesDetails)
    {
        $fieldTermParams = ['sourcefield','heightfield','widthfield', 'captionfield'];# LOWER CASE!
        foreach ($typesDetails as $field=>$aa) {
            foreach ($aa['params'] as $paramName => $arr) {
                if (in_array($paramName, $fieldTermParams))
                    $typesDetails2['dat'.$field]['params'][$paramName][0] = 'dat'.$arr[0];
                else
                    $typesDetails2['dat'.$field]['params'][$paramName] = $arr;
            }
        }
        return $typesDetails2;
    }




    /**
     * Check if a block has at least one delegated ancestor block
     */
    public static function blockHasDelegatedAncestor($regularId)
    {
        if (null === self::$childrenOfDelegatedBlocks[$regularId]) {
            # Forced expensive verification
            if (Blox::getBlockPageId($regularId) == Blox::getPageId())
                $zz = false;
            else
                $zz = true;
            self::$childrenOfDelegatedBlocks[$regularId] = $zz;
        } 
        return self::$childrenOfDelegatedBlocks[$regularId];
    }



 
    /**
     * Bind block's records to url, session, domain and so on
     * This method must be fired in tdd-files of multi-record templates.
     *
     * @param array $data {
     *   @var int $blockId BlockId of current template
     *   @var mixed $field Field's number (of varchar type) where binding value will be stored. For composite keys use simple array of fields.
     *   @var mixed $value Binding value that will be saved in the $field. For composite keys use simple array.
     *   @var bool $unbind Disallow binding on some conditions
     * }
     * @param string $keys Pointer to $keys variable in tdd-file.
     * @param array $defaults  Pointer to $defaults variable in tdd-file.
     * @return true|false
     */
    public static function bind($data, &$keys, &$defaults)
    {
        if ($data['block-id'] && $data['field']) {
            if (!$data['unbind']) {
                if ($data['block-id'] && $data['field']) { // && isset($data['value'])
                    if (is_array($data['field']))
                        $fields = $data['field'];
                    else
                        $fields[] = $data['field'];
                    #
                    if (isset($data['value'])) { # For empty string
                        if (is_array($data['value']))
                            $values = $data['value'];
                        else
                            $values[] = $data['value'];
                    }
                    $script = Blox::getScriptName();
                    if ($script == 'edit') {
                        /*
                        if ($values) {
                            foreach ($fields as $i=>$field)
                                $defaults[$field] = $values[$i];
                        } else
                        */if ($pick = Request::get($data['block-id'], 'pick')) { # Request comes with edit button automaticaly
                            foreach ($fields as $i=>$field)
                                $defaults[$field] = $pick[$field]['eq'];
                        }
                    } elseif (in_array($script, ['page','block','change'])) {
                        foreach ($fields as $i=>$field)
                            $p[$field] = $values[$i];
                        Request::add([$data['block-id']=>['p'=>$p]]);
                    }
                }
            }
            #
            if ($keys)
                $keys.=' ';
            foreach ($fields as $field)
                $s.= ',dat'.$field;
            $keys.= 'INDEX('.substr($s, 1).')';
        } else {
            Blox::prompt(Blox::getTerms('no-params'), true);
            return false;
        }
        return true;
    }



    /**
     * Get some stadard global data by JSON array of key data
     *
     * @param string $json
     * @return mixed
     * 
     * @example Examples of $json as JSON array 
     *   {"type":"session","keys":["map","city"]} keys: Chain of associative keys of $_SESSION array. Use for type:session. Value of session must be alfanumeric
     *   {"type":"phref"}
     */
    public static function getByJson($json)
    {
        if ($json==='')
            return;

        if (function_exists('json_decode')) {
            $options = json_decode($json, true);
            $jsonError = trim(Str::getJsonError());
            if ($jsonError === '' || $jsonError === 'No error') {
                if (!$options['type'])
                    return false;
                #
                if ($options['type'] == 'phref') {
                    $z = Router::getPhref(Blox::getPageHref());
                    if ($options['encode'])
                        return Url::encode($z); # We should encode this value because it will be used in url: ?edit&block=592&pick[9][eq]=P3BhZ2U9MzUmYmxvY2. The function urlencode() will not work here.
                    else
                        return $z;
                } elseif ($options['type'] == 'pick') {
                    if (is_array($options['field'])) {
                        foreach($options['field'] as $field)
                            $values[] = $_GET['p'][$field];
                        return $values;
                    } else
                        return $_GET['p'][$options['field']];
                } elseif ($options['type'] == 'session') {
                    if ($options['keys'])
                        return Arr::getByKeys($_SESSION, $options['keys']);
                }
            } else {
                if (Blox::info('user'))
                    Blox::prompt(Blox::getTerms('json-error').' '.$jsonError, true);
                return false;                        
            }
        } else {
            if (Blox::info('user'))
                Blox::prompt(Blox::getTerms('json-decode-not-exists'), true);
            return false;
        }   
    }
    


}
