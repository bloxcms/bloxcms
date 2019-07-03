<?php
/**
 * Methods that are used in authorized mode and in rare cases of visitor's mode that does not affect the speed of site
 * Documentation is not yet available.
 */
class Admin
{
    /**
     * @param string $docFileName
     * @param string $htm If a plain text (without tags) the width of the content will be no less then parent box. If the text contains tags, the width of the content is infinite, that is, you must write breaks
     * @param string $xQuery Extra url-query
     * @return string Tooltip button
     */
    public static function tooltip($docFileName=null, $htm=null, $xQuery=null)
    {
        $styleParams = '';

        if ($docFileName)
        {
            $url = 'http://bloxcms.net/documentation/';
            $url .= $docFileName;
            $url .= '?version=';
            $url .= Blox::getVersion();
            $url .= '&naked';
            $url .= $xQuery;            
            $onclick = ' onclick="window.open(\''.$url.'\',\'blox-tooltip\',\'width=800,height=800,resizable=yes,scrollbars=yes,location=no\').focus(); return false"';
        }
        else {
            $onclick = ' onclick="return false"';
            $styleParams .= ';cursor:default';
        }

        if (!(empty($docFileName) && empty($htm))) 
        {
            if (preg_match('#<.*?>#', $htm)) # if there are tags then you wrap
                $styleParams .= ';white-space:nowrap';
             
             if ($styleParams)
                 $style = ' style="'.$styleParams.'"';
            return '<a class="blox-tooltip"'.$style.' href="'.$url.'" title=\''.$htm.'\''.$onclick.' rel="nofollow"><img src="'.Blox::info('cms','url').'/assets/blox.tooltip.png" /></a>';
        }
    }




    public static function getDependedSelectFields($typesDetails, $selectedField)
    {
        $dependedFields = [];
        foreach ($typesDetails as $field => $details) {
            $parentField = $details['params']['parentfield'][0];
            if (in_array($parentField, $dependedFields) || $parentField == $selectedField)
                $dependedFields[] = $field;
        }
        return $dependedFields;
    }
    


    /**
     * Get array of fields to be edited
     *
     * @param array $titles Only keys of the array will be used
     * @param array $types Only keys of the array will be used
     * @param string $xprefix Write "x" for extra data tables
     */
	public static function getEditingFields($titles, $types, $xprefix=null)
	{
        # Array $titles sets the order of the fields 
        if ($titles) {
            foreach ($titles as $field=>$aa) {
                if ($types[$field]) {
                    $editingFields[] = $field;
                    unset($types[$field]);                   
                }
            }
        }
        # Rest fields without title
        if ($types)
            foreach ($types as $field=>$aa)                    
                $editingFields[] = $field;
        
        return $editingFields;
	}




    public static function getLastDelegatedBlock($tpl)
    {
        # Find the recent delegated block with this tpl
        $sql = "SELECT `block-id` FROM ".Blox::info('db','prefix')."lastdelegated WHERE tpl=?"; 
        if ($result = Sql::query($sql, [$tpl])) {
            if ($row = $result->fetch_assoc()) { # if assigned manualy
                $result->free();
                return $row['block-id'];                        
            }
        }
    }

    private static function putDelegatedId($blockId, $delegatedId) {
        if ($delegatedId && $blockId) {
        	$sql = "UPDATE ".Blox::info('db','prefix')."blocks SET `delegated-id`=? WHERE id=?";
            $num = Sql::query($sql,[$delegatedId, $blockId]);
            if ($num < 1)
                Blox::error(sprintf(Blox::getTerms('not-updated'),$sql));
            return true; 
        }
        return false;
    }

    private static function getDelegatedId($blockId) {
        $sql = 'SELECT `delegated-id` FROM '.Blox::info('db','prefix').'blocks WHERE id=?';
        $result = Sql::query($sql,[$blockId]); 
        if ($result) {
            $row = $result->fetch_assoc();
            $result->free();
            return $row['delegated-id'];
        }
    }
    
    private static function replaceDelegated($oldDelegators, $newDelegatedId) {
        foreach ($oldDelegators as $delegatorId) {
            if (!self::resetBlock($delegatorId))
                return false;
            if (!self::putDelegatedId($delegatorId, $newDelegatedId))
                return false;
        }
        return true;
    }
        

	public static function delegate($regularId, $instanceId)
	{        
        if (!self::resetBlock($regularId))
            return false;

        # If $regularId is delegator
        $delegators = self::getDelegators($regularId);
        if ($delegators)
        {
            # If $instanceId is delegator
            if ($delegatedOfSubject = self::getDelegatedId($instanceId)) {
                if (!self::putDelegatedId($regularId, $delegatedOfSubject))
                    return false;
                if (!self::replaceDelegated($delegators, $delegatedOfSubject))
                    return false;
            }
            # If $instanceId is delegated or independent
            else {
                if (!self::replaceDelegated($delegators, $instanceId))
                    return false;
                if (!self::putDelegatedId($regularId, $instanceId))
                    return false;
            }
        }
        # If $regularId was delegator or independent
        else {
            if (!self::putDelegatedId($regularId, $instanceId))
                return false;
        }   
        
        return true;
	}




    /**
     * Get array of blocks that delegating the block
     */
    public static function getDelegators($delegatedId)
    {
        if ($delegatedId) {
            $delegators = [];
            $sql = 'SELECT id FROM '.Blox::info('db','prefix')."blocks WHERE `delegated-id`=? ORDER BY id";
            if ($result = Sql::query($sql, [$delegatedId])) {
                while ($row = $result->fetch_assoc())
                    $delegators[] = $row['id'];
                $result->free();
                return $delegators;
            }
        }
    }



    public static function getBlocksWithUserIdField()
    {
        $descriptors = self::getDescriptors();
        $blocks = [];
        foreach ($descriptors as $descriptor) {
            if (self::userIdFieldExistInDescriptor($descriptor)) {
                if ($instances = Blox::getInstancesOfTpl(['excluded-block'=>$descriptor]))
                    $blocks = array_merge($blocks, $instances);
            }
        }
        return $blocks;
    }



    # if block is not specified it will be checked for the presence of user id field
    public static function userIdFieldExists($tddParams=null)
    {   
        # does userIdField exists in this block?
        if ($tddParams) {
            if ($tddParams['user-id-field'])
                return true;
        } else { # does userIdField exists in this site?
            $descriptors = self::getDescriptors();
            foreach ($descriptors as $descriptor) {
                if (self::userIdFieldExistInDescriptor($descriptor))
                    return true;
            }
        }
    }
 
 
 
 
    public static function getDescriptors()
    {
        $descriptors = Files::readBaseNames('assigned', 'tdd');
        sort($descriptors, SORT_NATURAL);
        return $descriptors;
    }





    public static function userIdFieldExistInDescriptor($descriptor)
    {
        $tddText = file_get_contents('assigned/'.$descriptor.'.tdd');
        $position = mb_stripos($tddText, 'user-id-field');
        if ($position !== false)
            return true;
        else {
            $tddhText = file_get_contents(Blox::info('templates','dir').'/'.$descriptor.'.tddh');
            $position = mb_stripos($tddhText, 'user-id-field');
            if ($position !== false)
                return true;
        }
    }




    /**
     * Delete childs: blocks, pages, files
     */
    public static function deleteChilds($tpl, $recId, $field, $blockId=0, $xprefix=null) # $blockId is $delegatedId
    {
        # Instead of empty values $recId and $field, use 'all', because deleting is important operation

        if (empty($field) || empty($recId))
            return false;
        
        $refTypes = ['block', 'page', 'file'];

        # List of fields to retrieve data
        $tdd = Tdd::getDirectly('assigned/'.$tpl.'.tdd', Blox::getBlockInfo($blockId)); # infitite loop protection
        $typesDetails = Tdd::getTypesDetails($tdd[$xprefix.'types'], $refTypes, 'only-name');// $tddTypes, $addTypeParams, $separatedTypes
        if (empty($typesDetails))
            return true;# No special data types - do not delete the childs
        # checkRefSubjCountTables
        foreach (['pages', 'updates', 'downloads'] as $subj)
          if (Sql::tableExists(Blox::info('db','prefix').'count'.$subj))
              $refSubjCountTables[$subj] = true;
        $typesDetailsF = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['file']);
        foreach ($typesDetails as $fld => $typeDetails)
        {
            if ($field =='all' || $fld == $field)
            {
                $dst = $typesDetailsF[$fld][$xprefix.'params']['destination'][0] ? $typesDetailsF[$fld][$xprefix.'params']['destination'][0] : 'datafiles';
                $dstDir = Blox::info('site','dir').'/'.$dst;

                $deleteChildsInColumn = function($tpl, $recId, $field, $refType, $blockId, $dstDir, $xprefix='')
                {
                    $sqlValues = [];
                    # Conditions: a specific row, all rows of one block, or all the rows of the table.
                    if ($recId != 'all') {
                        $where = " WHERE `block-id`=? AND `rec-id`=?";
                        $sqlValues[] = $blockId;
                        $sqlValues[] = $recId;
                    } elseif ($blockId && $blockId != 'all') {
                        $where = " WHERE `block-id`=?";
                        $sqlValues[] = $blockId;
                    }
                    $tbl = Blox::getTbl($tpl, $xprefix);
                    $sql = "SELECT dat{$field} FROM $tbl".$where;
                    if ($result = Sql::query($sql, $sqlValues)) {
                        while ($row = $result->fetch_row()) {
                            if (empty($row[0]))
                                return true;
                            $datum = $row[0];
                            if ('block' == $refType) {
                                if (!self::removeBlock($datum))
                                    return false;
                            } elseif ('page' == $refType) {
                                $pageInfo = Router::getPageInfoById($datum);
                                $outerBlockId = $pageInfo['outer-block-id'];
                                if (!self::removeBlock($outerBlockId))
                                    return false;
                                $sql = 'DELETE FROM '.Blox::info('db','prefix').'pages WHERE id=?';
                                if (isEmpty(Sql::query($sql, [$datum])))
                                    return false;
                                if ($refSubjCountTables['pages'])
                                    Sql::query('DELETE FROM '.Blox::info('db','prefix')."countpages WHERE obj=?", [$datum]);
                                $sql = 'DELETE FROM '.Blox::info('db','prefix')."pseudopages WHERE phref LIKE ?";
                                Sql::query($sql, ['?page='.$datum.'&%']);
                            } elseif ('file' == $refType) {
                                $fl = $dstDir.'/'.$datum;
                                if (file_exists($fl)) {
                                    if (Files::unLink($fl, Blox::info('site','dir').'/datafiles')) # Delete the empty folder, if not datafiles
                                        Sql::query('DELETE FROM '.Blox::info('db','prefix')."countdownloads WHERE obj=?", [$datum]);
                                    elseif ($refSubjCountTables['downloads'])
                                        Blox::error('Error removing the file: '.$dstDir.'/'.$datum);
                                }
                            }
                            /*
                            elseif ('select' == $refType)
                            {
                                ; # TODO. См. 'Рассуждения/Select-данные/..'

                            }
                            */
                        }
                        $result->free();
                        return true;
                    }
                    else
                        return false;
                };
                if (!$deleteChildsInColumn($tpl, $recId, $fld, $typeDetails['name'], $blockId, $dstDir, $xprefix))
                    return false;
            }
        }
        return true;
    }


    /**
     * @todo  $tpl replace by $tbl
     */
    public static function createEmptyDataTable($tbl, $tdd, $xprefix=null)
	{
        if (empty($tdd[$xprefix.'types']))
            return false;

        $sql = "CREATE TABLE IF NOT EXISTS $tbl (
            `rec-id` ".self::reduceToSqlType('rec-id');        
            $aa = array_keys($tdd[$xprefix.'types']);
            $maxKey = max($aa);
            for ($field = 1; $field <= $maxKey; $field++) {
                $type = $tdd[$xprefix.'types'][$field];
                if ($type)
                    $sqlType = self::reduceToSqlType($type, true);
                else
                    $sqlType = "TINYINT(1) UNSIGNED NOT NULL DEFAULT 0";
                $sql .= ", dat{$field} {$sqlType}";
            }
            $sql .= ", `block-id` ".self::reduceToSqlType('block');
            $sql .= ", sort ".self::reduceToSqlType('rec-id');
            $sql .= ", PRIMARY KEY (`block-id`, `rec-id`)"; 
            # keys
            if ($tdd[$xprefix.'keys'])
                $sql .= ', '.$tdd[$xprefix.'keys'];
            if ($xprefix && mb_strpos($tdd[$xprefix.'keys'], 'block-id') === false) 
                $sql .= ', UNIQUE(`block-id`)'; # Only one record for extra data block
        $sql .= ")";


        # options
        if ($tdd[$xprefix.'options'])
        {
            # Search for ENGINE in $tdd['options']
            $posit = stripos($tdd[$xprefix.'options'], 'engine');
            if ($posit !== false)
                $engineDefined = true;

            $posit = stripos($tdd[$xprefix.'options'], 'charset');
            if ($posit !== false)
                $charsetDefined = true;

            $sql .= $tdd[$xprefix.'options'];
        }

        if (!$engineDefined)
            $sql .= ' ENGINE=MyISAM';

        if (!$charsetDefined)
            $sql .= ' DEFAULT CHARSET=utf8'; # Because sometimes appears another collations
        return Sql::query($sql);
	}
    
    

    public static function checkSortByColumnsFilters($regularId, $pickKeyFields)
    {       
        # Check for forbidden conditions
        foreach (['limit','part','single','search'] as $filter) {
            if (Request::get($regularId,$filter)) {
                Blox::prompt(Blox::getTerms('invalid-request').' '.$filter, true);
                return;
            }
        }

        # Validation of sort request
        if (Request::get($regularId,'sort')) {
            foreach (Request::get($regularId,'sort') as $k=>$order) {
                if (!$order) {
                    Blox::prompt(Blox::getTerms('undefined-sort'), true);
                    return;
                }
            }
        }

        # Validation of pick request
        if (!Request::get($regularId,'pick')) {
            if ($pickKeyFields) {
                Blox::prompt(Blox::getTerms('no-pick-request'), true);
                return;
            }
        } else {
            if (!$pickKeyFields) {
                Request::remove($regularId,'pick');
                Blox::prompt(Blox::getTerms('no-param').' params[pick][key-fields]', true);
                return;
            } else {
                foreach (Request::get($regularId,'pick') as $field => $aa) {
                    foreach ($aa as $k=>$val) {
                        if ($k=='eq') {
                            if (!in_array($field, $pickKeyFields)) {
                                Blox::prompt(Blox::getTerms('invalid-pick-request'), true);
                                return;
                            }
                        } else {
                            Blox::prompt(Blox::getTerms('invalid-operator').' '.$k, true);
                            return;
                        }
                    }
                }
                # if there are no request to some fields, then no data wil be retrieved
            }
        }

        return true;
    }






	public static function assignNewTpl($srcBlockId, $tpl)
    {
        $aa = Blox::info('templates', 'dir').'/'.$tpl.'.tpl';
        if (!file_exists($aa)) {

            Blox::prompt(Blox::getTerms('no-tpl-file').' '.$aa ,  true);
            return false;
        }
        # Assign template
        $tdd = Tdd::get(['tpl'=>$tpl, 'src-block-id'=>$srcBlockId]); # 'tpl' is yet unknown in Blox::getBlockInfo()
        if (empty($tdd['types']) && empty($tdd['xtypes'])) {# template without data
            $sql = "UPDATE ".Blox::info('db','prefix')."blocks SET tpl=?, `delegated-id`=0 WHERE id=?"; # parent-block-id remains the same
            Sql::query($sql,[$tpl,$srcBlockId]);
        } else  { # Editable template 
            # If no records,create the first record
            $createFirstRec = function ($srcBlockId, $tpl, $tdd, $xprefix='') {
                if (empty($tdd[$xprefix.'types']))
                    return;
                $tbl = Blox::getTbl($tpl, $xprefix);
                if (!Sql::tableExists($tbl)){ # No table
                    # Create a table for the first time
                    if ($tdd[$xprefix.'types'])
                        if (!self::createEmptyDataTable($tbl, $tdd))
                            return false;
                }
                Dat::insert(['src-block-id'=>$srcBlockId, 'tpl'=>$tpl], [], $xprefix, $tdd);
            };

            $createFirstRec($srcBlockId, $tpl, $tdd);
            $createFirstRec($srcBlockId, $tpl, $tdd, 'x');


            # two edit buttons protection: the first and the new rec button
            $_SESSION['Blox']['initial-rec-is-created'][$srcBlockId] = true;

            $sql = "UPDATE ".Blox::info('db','prefix')."blocks SET tpl=?, `delegated-id`=0 WHERE id=?";
            $num = Sql::query($sql,[$tpl,$srcBlockId]);
            if ($num < 1) {
                $sql2 = "REPLACE ".Blox::info('db','prefix')."blocks SET id=?, tpl=?";
                Sql::query($sql2, [$srcBlockId, $tpl]); # parent-block-id needs to recover himself?
                Blox::error('The request '.$sql.' has made no effect due to the lack of record. Applied '.$sql2);
            }
        }
        return true;
    }




    /**
     * Is the user an editor of any block located in this page
     * This method is used only in Blox::replaceBlockIdsByHtm()
     */
    public static function userIsEditorOfHiddenPage($pageId, $tddParams)
    {
        # Is he the editor at least one block on the website
        if (Proposition::get('user-is-editor-of-block', Blox::info('user','id'), 'any'))
        	$userIsEditorOfAnyBlock = true;
        if (self::userIdFieldExists())
            $userIdFieldExists = true;
        # Is it an editor or editor of own records
        if ($userIsEditorOfAnyBlock || $userIdFieldExists) {
            # All blocks of the page
            # SIMILAR: in check.php
            $pageInfo = Router::getPageInfoById($pageId);
            $blockTreeParams = Tree::get($pageInfo['outer-block-id'], ['block']);
            $allBlocks = Tree::getBlocks($blockTreeParams['ref-data']['blocks']);
            $allBlocks[] = $pageInfo['outer-block-id'];

            foreach ($allBlocks as $blockId) {
            	if ($userIsEditorOfAnyBlock)
	                if (Proposition::get('user-is-editor-of-block', Blox::info('user','id'), $blockId))
	                    return true;
                if ($userIdFieldExists) {
                    # userIdFieldExists
                    if (self::userIdFieldExists($tddParams)) {
                        return true;
                    }
                }
            }
        }
    }
    
    
    


    public static function genBlockId($parentBlockId=0, $parentRecId=0, $parentField=0, $xprefix='') # For outer block $parentBlockId=0
    {
        if (empty($parentBlockId) && $parentRecId) {
            Blox::prompt(Blox::getTerms('failed-block-id-gen'), true);
            return false;
        } else {
            $xprefix2 = $xprefix ? 1 : 0; 
            $sql = "INSERT INTO ".Blox::info('db','prefix')."blocks SET `parent-block-id`=?, `parent-rec-id`=?, `parent-field`=?, `is-xdat`=?";
            $num = Sql::query($sql, [$parentBlockId, $parentRecId, $parentField, $xprefix2]);
            $db = Sql::getDb();
            if ($num > 0) {
                $childBlockId = $db->insert_id;            
                $checkAndPermitAccessForChild = function ($parentBlockId, $childBlockId) {
                    if (empty($childBlockId)) # $parentBlockId=0 for the home page
                        return;
                    # permit Access To Child Blocks for user
                    $sql = "SELECT `subject-id` FROM ".Blox::info('db','prefix')."propositions  WHERE `formula`=? AND `object-id`=?"; # Works for $outerBlockId too
                    if ($result = Sql::query($sql, ['user-is-editor-of-block', $parentBlockId])) {
                        while ($row = $result->fetch_assoc())
                            Proposition::set('user-is-editor-of-block', $row['subject-id'], $childBlockId, true); # for each permitted user give permission to this block too
                        $result->free();
                    }
                };
                $checkAndPermitAccessForChild($parentBlockId, $childBlockId);
                
                return $childBlockId; 
            } else {
                Blox::prompt("Block ID was not generated by sql:<br>".$sql."<br>in Blox::getBlockHtm", true);
                return false; 
            }
        }   
    }
    



    /**
     * Prepare prompts
     */
    public static function getPromptsHtm()
    {
        # Is there log files?
        if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
            self::promptLog('---blox-errors.log', Blox::getTerms('error-log'));
            self::promptLog('---qq.log',         Blox::getTerms('debug-log'));
        }
        if (Blox::info('user','user-is-admin') && Blox::info('site', 'log-repeated-sql-queries'))
            Sql::logQueries();
        if ($_SESSION['Blox']['prompts']) {
            $counter=0;
            foreach ($_SESSION['Blox']['prompts'] as $errorType => $prompts) {
                if ($errorType == 1)
                    $itemStyle = " style='color:red'";
                else
                    $itemStyle = '';                
                $GLOBALS['Blox']['unique-prompts'] = [];

                foreach ($prompts as $value) {
                    # Output only unique prompts
                    if (!in_array($value, $GLOBALS['Blox']['unique-prompts'])) {
                        $GLOBALS['Blox']['unique-prompts'][] = $value;                        
                        $output .= "<li".$itemStyle.">$value</li>";
                        $counter++;
                    }
                }
            }
            unset($_SESSION['Blox']['prompts']);# Once displayed, we can remove. GLOBALS will not work because there may be update scripts
            if ($counter < 2)
                $listStyle = " style='list-style-type: none'";
            return '<div class="blox-prompt-bar"><div><ol'.$listStyle.'>'.$output.'</ol></div></div>'; # Inner div is for bordering
        }
    }
    private static function promptLog($url, $title)
    {
        if (file_exists($url)) {
            /*
            if (filesize($url) > 10485760) { # 10MB
                if (!unlink($url)) {
                    Blox::prompt('Failed deleting the file: '.$url, true);
                }
            } else
            */
            if (Blox::info('user','user-is-admin')) {
                $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
                Blox::prompt($title.': <a href="?log-display&file='.$url.'" onclick="window.open(\'?log-display&file='.$url.'\',\'log-display\',\'width=640,height=800,resizable=yes,scrollbars=yes,location=no\').focus(); return false;" target="_blank">'.Blox::getTerms('display-log').'</a>, <a href="?log-remove&file='.$url.$pagehrefQuery.'">'.Blox::getTerms('remove-log').'</a>');
            }
        }
    }




	public static function getTabFieldsInfo($tpl, $xprefix=null)
	{
        if (empty($tpl))
            return false;
        $aa = Blox::getTbl($tpl, $xprefix);
        return self::getTableColsInfo($aa);
    }
    # For any table
	public static function getTableColsInfo($tbl)
	{
        if (empty($tbl)) 
            return;

        
        $sql = 'DESCRIBE '.$tbl;
        //$sql = 'DESCRIBE `'.trim($tbl, '`').'`';

        if ($result = Sql::query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $fieldName = $row['Field'];
                # Null
                if ('YES' == $row['Null'])
                    $fields[$fieldName]['Null'] = 1;
                else // need?
                    $fields[$fieldName]['Null'] = 0;
                $type = $row['Type'];
                # type and params of type
                if (preg_match("/^(\w+?)\s*?\(\s*?(.+?)\s*?\)/", $type, $matches)) {
                    $type = mb_strtolower($matches[1]);
                    $pieces = explode(",", $matches[2]);//divide by ','
                    foreach ($pieces as $i => $piece) {
                        if ($type == 'set')
                            $piece = trim($piece, "'"); // For SET remove quotes
                        $fields[$fieldName]['params'][$i] = $piece;
                    }
                }
                $fields[$fieldName]['Type'] = $type;
            }
            $result->free();
            return $fields;
        }

        /*
        Null: 1
        Type: set
        params:
            0: aa
            1: bb
            2: nn
        */
    }



    /**
     * Convert special data type declarations (block, page, file) to mysql declarations
     */
    public static function reduceToSqlType($type, $isTab=false)
    {
        $typeName = '';
        $type = trim($type);
    	if ('page' == mb_strtolower(substr($type, 0, 4))) {
            $type = 'MEDIUMINT UNSIGNED'; # NOT NULL in instal.php 
            if ($isTab)
                $type .= ' NOT NULL DEFAULT 0';
        } elseif ('file' == mb_strtolower(substr($type, 0, 4))) {
    		$type = "VARCHAR(332) NOT NULL DEFAULT ''";
        } elseif ('block' == mb_strtolower(substr($type, 0, 5))) {
            $type = 'MEDIUMINT UNSIGNED NOT NULL'; # NOT NULL in install.php 
            if ($isTab)
                $type .= ' NOT NULL DEFAULT 0';
        } elseif ('select' == mb_strtolower(substr($type, 0, 6))) {
            $type = 'MEDIUMINT UNSIGNED';
            if ($isTab)
                $type .= ' NOT NULL DEFAULT 0';
        } elseif ('rec-id' == $type)
            $type = 'MEDIUMINT UNSIGNED NOT NULL DEFAULT 0';
    	return $type;
    }



	/**
     * Remove the block and all entities associated with him.
     * Attention! Most of resets better to do in resetBlock.php
     */ 
    public static function removeBlock($blockId)
    {
        if (empty($blockId)) {
            if ($blockId===0) # Outer block
                return true;
            else
                return false;
        }

        if (!self::resetBlock($blockId))
            return false;

        $sql = 'DELETE FROM '.Blox::info('db','prefix').'blocks WHERE id=?';
        if (isEmpty(Sql::query($sql, [$blockId])))
            return false;

        /**
         * Remove all entities associated with him
         * @todo The underlying code does not take into account whether the block is delegated. It's better to check before this fact.
         */
        if ($props = Proposition::get('user-is-editor-of-block', 'all', $blockId)) {
            foreach ($props as $prop) {
                Proposition::delete('user-is-editor-of-block', $prop['subject-id'], $blockId);}
        }
        $sql = 'DELETE FROM '.Blox::info('db','prefix').'lastdelegated WHERE `block-id`=?';
        if (isEmpty(Sql::query($sql, [$blockId])))
            return false;
        # Selects
        $sql = "DELETE FROM ".Blox::info('db','prefix')."selectlistblocks WHERE `edit-block-id`=? OR `select-list-block-id`=?";
        Sql::query($sql, [$blockId, $blockId]);
        $sql = "DELETE FROM ".Blox::info('db','prefix')."dependentselectlistblocks WHERE `parent-list-block-id`=? OR `select-list-block-id`=?";
        Sql::query($sql, [$blockId, $blockId]);
        /**
         * @todo Remove all data that used those selects. Solution #1: Run the reset process when a select list is assigned 
         */
        return true;
    }
    #
    public static function deleteRec($tpl, $recId, $srcBlockId, $tbl=null, $xprefix=null)
    {
        if (!self::deleteChilds($tpl, $recId, 'all', $srcBlockId, $xprefix))
            return false;
        if (!$tbl)
            $tbl = Blox::getTbl($tpl, $xprefix);
        $sql = 'DELETE FROM '.$tbl.' WHERE `block-id`=? AND `rec-id`=?';
    	if (isEmpty(Sql::query($sql, [$srcBlockId, $recId])))
            return false;
        $sql = 'DELETE FROM '.Blox::info('db','prefix').'pseudopages WHERE `key`=?'; # KLUDGE
        if (isEmpty(Sql::query($sql, [$srcBlockId.'-'.$recId])))
            Blox::error(sprintf(Blox::getTerms('cannot-remove-rec-of-pseudopages'), $srcBlockId.'-'.$recId));
        # Selects
        if (empty($_GET['is-pick-values-field'])) {
            $sql = "DELETE FROM ".Blox::info('db','prefix')."dependentselectlistblocks WHERE `parent-list-block-id`=? AND `parent-list-rec-id`=?";
            Sql::query($sql, [$srcBlockId, $recId]);
        }
        return true;
    }
    # Remove all data of the block. Only his id remains.
    public static function resetBlock($blockId)
    {
        if ($blockId)
        {
            $blockInfo = Blox::getBlockInfo($blockId);
            $srcBlockId = $blockInfo['src-block-id'];
            if ($srcBlockId == $blockId)
            {
                $tpl  = $blockInfo['tpl'];
                if (!self::redelegate($srcBlockId, $tpl, $newSrcBlockId))
                    return false;
                # Editable block
                if (file_exists('assigned/'.$tpl.'.tdd'))
                {
                    $noNeedForTable = function($tpl, $blockId, $xprefix='')
                    {
                        # KLUDGE
                        if (empty($tpl) && empty($blockId))
                            return;
                        # Is this table used by other blocks
                        $sql = 'SELECT id FROM '.Blox::info('db','prefix')."blocks WHERE tpl=? AND id != ? LIMIT 1";
                        if ($result = Sql::query($sql, [$tpl, $blockId])) {
                            $num = $result->num_rows;
                            $result->free();
                        }

                        if (empty($num)) 
                        {   # if it was the last record, then delete the entire table
                            $tbl = Blox::getTbl($tpl, $xprefix);
                            $sql = 'SELECT * FROM '.$tbl.' LIMIT 1';
                            if ($result = Sql::query($sql)) {
                                $num2 = $result->num_rows;
                                $result->free();
                            }
                            if (empty($num2))
                                return true;
                        }
                    };
                    
                    $dropTable = function($tpl, $xprefix='') {
                        $tbl = Blox::getTbl($tpl, $xprefix);
                        $sql = 'DROP TABLE IF EXISTS '.$tbl;
                        if (Sql::query($sql) === false)
                            return false;
                        else
                            return true;
                    };

                    $noNeedForTable_tab = false;
                    $tbl = Blox::getTbl($tpl);
                    if (Sql::tableExists($tbl)) {
                        if (!self::deleteChilds($tpl, 'all', 'all', $srcBlockId))
                            return false;
                        $sql = "DELETE FROM $tbl WHERE `block-id`=?";
                        if (isEmpty(Sql::query($sql, [$srcBlockId])))
                            return false;
                        $sql = 'DELETE FROM '.Blox::info('db','prefix').'pseudopages WHERE `key` LIKE ?'; # KLUDGE
                        if (isEmpty(Sql::query($sql, [$srcBlockId.'-%'])))
                            Blox::error(sprintf(Blox::getTerms('cannot-remove-rec-of-pseudopages-2'), $srcBlockId.'-...'));                       
                        
                        if ($noNeedForTable($tpl, $srcBlockId)) {
                            $noNeedForTable_tab = true;
                            if (!$dropTable($tpl))
                                return false;
                        }
                    } else
                        $noNeedForTable_tab = true;

                    $noNeedForTable_xtab = false;
                    $xprefix = 'x';
                    $tbl = Blox::getTbl($tpl, $xprefix);
                    if (Sql::tableExists($tbl)) {
                        if (!self::deleteChilds($tpl, 'all', 'all', $srcBlockId, $xprefix))
                            return false;
                        $sql = "DELETE FROM $tbl WHERE `block-id`=?";
                        if (isEmpty(Sql::query($sql, [$srcBlockId])))
                            return false;
                        if ($noNeedForTable($tpl, $srcBlockId, $xprefix)) {
                            $noNeedForTable_xtab = true;
                            if (!$dropTable($tpl, 'x'))
                                return false; 
                        }
                    } else
                        $noNeedForTable_xtab = true;
                    # Delete tdd file
                    # TODO: Tdd file was not deleted when removing parent blocks or pages. On some conditions tdd file was deleted, but table was nor deleted (see functions/Tdd.php > 'no-main-tpl2'.)
                    if ($noNeedForTable_tab && $noNeedForTable_xtab) {
                        Files::unLink('assigned/'.$tpl.'.tdd');
                    }
                }
                
                if (Sql::tableExists(Blox::info('db','prefix').'countupdates')) {
                    $sql = 'DELETE FROM '.Blox::info('db','prefix')."countupdates WHERE obj=?";
                    Sql::query($sql, [$srcBlockId]);
                }
                
                # Subscriptions
                if (Sql::tableExists(Blox::info('db','prefix').'subscriptions')) {
                    $sql = "SELECT * FROM ".Blox::info('db','prefix')."subscriptions WHERE `block-id`=?";
                    if ($result = Sql::query($sql, [$srcBlockId])) {
                        if ($row = $result->fetch_assoc()) {
                            $result->free();
                            # Renew subscriptions
                            if ($newSrcBlockId) {
                                # Replace the subscribed blocks of all subscribers
                                if ($props = Proposition::get('user-is-subscriber', 'all', $srcBlockId)) {
                                    foreach ($props as $subjobj)
                                        Proposition::set('user-is-subscriber', $subjobj['subject-id'], $srcBlockId, true);
                                    $sql = "INSERT ".Blox::info('db','prefix')."subscriptions (`block-id`, `last-mailed-rec`, `activated`) VALUES (?,?,?)";
                                    Sql::query($sql, [$newSrcBlockId, $row['last-mailed-rec'], $row['activated']]); 
                                }
                            }

                            # Remove old subscriptions 
                            $props = Proposition::delete();
                            $sql = "DELETE FROM ".Blox::info('db','prefix')."subscriptions WHERE `block-id`=?";
                            Sql::query($sql, [$srcBlockId]);
                        }
                    }
                }
                # Reset Settings related to blocks                
                foreach (['editSettings','xeditSettings','blockletter-params','import-rowwise-settings'] as $name)  
                    Store::delete($name.$blockId);
            }
            $sql = 'UPDATE '.Blox::info('db','prefix')."blocks SET tpl='', `delegated-id`=0 WHERE id=?";
            if (isEmpty(Sql::query($sql, [$blockId])))
                return false;
        }
        else {
            Blox::error(Blox::getTerms('no-block-id'));
            return false;
        }
        return true;
    }

    # Pass all relations of the source block to be removed to the next oldest block
    public static function redelegate($regularId, $tpl, &$newSrcBlockId=null)
    {
        # sorted
        if ($delegators = self::getDelegators($regularId)) {
            # Change delegated block
            $newDelegatedId = 0;
            # Find oldest delegating block (with smallest id)
            foreach ($delegators as $delegatorId) {
                //$delegatorId = Sql::sanitizeInteger($delegatorId);
                if ($delegatorId) { # Need?
                    if (empty($newDelegatedId)) { # Do this once for new delegated (former delegating) block
                        $newDelegatedId = $delegatorId;
                        # Mark the new delegated block as not delegating
                        $sql = "UPDATE ".Blox::info('db','prefix')."blocks SET tpl=?, `delegated-id`=0 WHERE id=?";
                        if (Sql::query($sql, [$tpl, $newDelegatedId])===false)
                            return false;
                        # Change `block-id` for all delegating blocks
                        $tbl = Blox::getTbl($tpl);
                        if (Sql::tableExists($tbl)) {
                            $sql = "UPDATE ".$tbl." SET `block-id`=? WHERE `block-id`=?";
                            if (Sql::query($sql, [$newDelegatedId, $regularId])===false)
                                return false;
                        }
                        $xprefix = 'x';
                        $tbl = Blox::getTbl($tpl, $xprefix); 
                        if (Sql::tableExists($tbl)) { 
                            $sql = "UPDATE ".$tbl." SET `block-id`=? WHERE `block-id`=?";
                            if (Sql::query($sql, [$newDelegatedId, $regularId])===false)
                                return false;
                        }
                    } else { # For the rest of delegating blocks
                        # Redelegate
                        $sql = "UPDATE ".Blox::info('db','prefix')."blocks SET `delegated-id`=? WHERE id=?";
                        if (Sql::query($sql, [$newDelegatedId, $delegatorId])===false) //13.06.2018
                            return false;
                    }
                }
            }            
            $newSrcBlockId = $newDelegatedId;
            # Change parent-block-id for all childrens
            $sql = "UPDATE ".Blox::info('db','prefix')."blocks SET `parent-block-id`=? WHERE `parent-block-id`=?";
            if (Sql::query($sql, [$newSrcBlockId, $regularId])===false)
                return false;
        }
        return true;
    }
 



    /**
     * Edit button html code of a block (multi-record)
     *
     * @param int $blockId Regular block id
     * @param array $options
     *     @var array 'block-info' Optional
     *     @var array 'tdd' Optional
     *     @var string 'pagehref-query' ($pagehrefQuery = '&pagehref='.Blox::getPageHref(true);)
     *     @var bool 'return-href' Returns href instead of html code of button
     *     @var mixed 'rec' Number or 'new'
     * }
     * @return string HTML for multi-record edit button
     * 
     */
    public static function getEditButton($blockId, $options=[]) 
    {
        if ($blockId)
        {       
            if ($options)
                Arr::formatOptions($options);
            $blockInfo = $options['block-info'] ?: self::getBlockInfo($blockId);
            $tdd = $options['tdd'] ?: Tdd::get($blockInfo);
            if (!$tdd['params']['multi-record'] && !$options['rec'])
                $options['rec'] = 1;
            $href = '?edit&block='.$blockId;
            if ($options['rec'])
                $href.= '&rec='.$options['rec'];
            if (isset($_GET['edit']))
                $href .= Request::convertToQuery(Request::get($blockId));
                //$href .= urldecode(Request::convertToQuery(Request::get($blockId)));  #497436375
            $href .= $options['pagehref-query'] ?: '&pagehref='.Blox::getPageHref(true);
            #
            if ($options['return-href']) 
                return $href;
            else {
                $clas = 'blox-edit-button';# This could be taken out of the condition
                $title = Blox::getTerms('edit-block');
                $src = Blox::info('cms','url').'/assets/';  
                if ($blockInfo['tpl']) {
                    # no-edit-buttons
                    if ($tdd['params']['no-edit-buttons']) {
                        if (Blox::info('user','user-is-admin')) {
                            $clas .= ' blox-no-edit-buttons';
                            $title .= '. '.Blox::getTerms('no-edit-buttons');
                        } else
                            return;
                    }
                    # img src
                    if ($tdd['types']) {
                        if ($options['rec'])
                            $src .= 'edit-button-edit-rec.png';
                        else
                            $src .= 'edit-button-multi-rec.png';
                        $alt = '&equiv;';
                    } else {
                        $src .= 'edit-button-no-data.png';
                        $alt = '&#160; &#160;'; 
                    }
                } else {
                    if (Blox::info('user','user-is-admin') && !Blox::info('user','user-as-visitor')) {
                        $clas .= ' blox-no-tpl';
                        $recQuery = '&rec=new';
                        $title = Blox::getTerms('assign-tpl');
                        $src .= 'edit-button-no-tpl.png';
                        $alt = '?';
                    } else
                        return;
                }
                $clas .= ' blox-maintain-scroll';
                $buttElem = ($tdd['params']['span-edit-buttons']) ? 'span' : 'a';
                return '<'.$buttElem.' class="'.$clas.'" href="'.$href.'" title="'.$title.'"><img class="blox-edit-button-img" src="'.$src.'" alt="'.$alt.'" /></'.$buttElem.'>';
            }
            /** 
            else
                TODO: If no block ID, generate it as in replaceBlockIdsByHtm.php 
            */
        }
        else
            return false;
    }


    /** 
     * RESERVED
     * Transpose params of fields to fields of params, i.e. $params['fields'][$field][$param] = true  to $params[$param]['fields'] = $fields
     * If value of an array $params['fields'][1][aaa] is not boolean and not empty, we consider it as true.
     *
     * @param array $params Params of fields (as $params['fields'][...])
     * @return array Fields of params
     
    public static function flipParamsOfFields($params)
    {
        if ($params) {
            foreach ($params as $field => $z) {
                foreach ($z as $param=>$val)
                    if ($val)
                        $params2[$param]['fields'][] = $field;
            }
            return $params2;
        }
    }
    */
    /* RESERVED
    public static function flipFieldsOfParams($fields)
    {
    }
    */
    
    
        
    /**
     * Inspection of pairs of tags. Log will be output in prompt bar.
     *
     * @param string $text HTML code
     * @param string $tpl     For building an edit button of nonvalid block
     * @param int $srcBlockId For building an edit button of nonvalid block 
     * @param int $recId      For building an edit button of nonvalid block
     * @param int $field      For building an edit button of nonvalid block
     *
     * DEPRECATE? Admin::isHtmValid() -->	Text::getUnbalancedTags(). Do before $tagsList?.    
     */
    public static function isHtmValid($text, $tpl=null, $srcBlockId=null, $recId=null, $field=null) 
    {
        if (preg_match_all('~<([a-z]+[0-9]*)~iu',$text, $matches)) {
            $voidTags = ['area','base','br','col','embed','hr','img','input','link','menuitem','meta','param','source','track','wbr'];
            foreach ($matches[1] as $openTag) {
                $openTag = mb_strtolower($openTag);
                if (!in_array($openTag, $voidTags))
                    $counter[$openTag]['open']++;
            }
        }
        if (preg_match_all('~</([a-z]+[0-9]*)~iu',$text, $matches)) {
            $closeTags = $matches[1];
            foreach ($closeTags as $closeTag) {
                $closeTag = mb_strtolower($closeTag);
                $counter[$closeTag]['close']++;
            }
        }
        if ($counter) {
            $piecesTerm = Blox::getTerms('pieces');
            foreach ($counter as $tg => $pair) {
                $openVal = $pair['open'] ?: 0;
                $closeVal = $pair['close'] ?: 0;
                if ($diff = $openVal - $closeVal) {
                    if ($diff > 0) {
                        $tagsList .= ', &lt;'.$tg.'&gt';
                        if ($diff > 1) 
                            $tagsList .= ' '.$diff.' '.$piecesTerm;
                    } else {
                        $tagsList .= ', &lt;/'.$tg.'&gt;';
                        if ($diff < -1) 
                            $tagsList .= ' '.abs($diff).' '.$piecesTerm;
                    }
                }
            }
            if ($tagsList) {
                $prompt1 = sprintf(Blox::getTerms('extra-tags-1'), substr($tagsList, 1));
                if ($tpl && $srcBlockId && $recId && $field) {
                    $prompt2 = sprintf(
                        Blox::getTerms('extra-tags-2'), 
                        $srcBlockId.'('.$tpl.')', 
                        $recId, 
                        $field, 
                        '<a href="?edit&block='.$srcBlockId.'&rec='.$recId.'&pagehref='.Blox::getPageHref(true).'">', 
                        '</a>'
                    );
                }
                Blox::prompt($prompt1.$prompt2, true);
            }
        }
    }
    
    /**
     * Get description of a template or folder with templates. Accepts .md files
     * @param string $path Normal (full) tpl name or path to a folder
     * @param bool $isFolder If true we search for the file "description.md"
     */
    public static function getDescription($path, $isFolder=false) 
    {
        $tplname = Str::getStringAfterMark($path, '/', true);
        if (false === $tplname)
            $tplname = $path;
        $text = '';
        $tplnames = ($isFolder)
            ? ['/description', '/!description']
            : [ '', '/'.$tplname]
        ;
        foreach ($tplnames as $n) {
    		$fl = Blox::info('templates', 'dir').'/'.$path.$n.'.md';
    		if (file_exists($fl)) {
                if ($isFolder && file_exists(Blox::info('templates', 'dir').'/'.$path.$n.'.tpl')) # This is a template description, not a folder description
                    continue;
                if ($zz = file_get_contents($fl)) {
                    require_once Blox::info('cms','dir').'/vendor/erusev/parsedown/Parsedown.php';
                    $p = new Parsedown();
                    $text .= $p->text($zz);
                    break; # Output only one of two files
                }
            }
		}
        return $text;
    }



    
    
}