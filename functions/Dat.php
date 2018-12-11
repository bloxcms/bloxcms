<?php   

/**
 * Class for inserting, updating and getting regular editable data from tables like "$..."
 */
class Dat
{           
    /**
     * Method for record updating
     * 
     * @param array $blockInfo  This is an array with two required elements ['src-block-id'=>..., 'tpl'=>...]
     * @param array $dat        The values that you want to update. Format [field=>value, ..., 'sort'=>]    
     * @param array $wdat       The array to build a WHERE clause in a sql expression ['rec'=>12, 1=>'apple']
     * @param array $xprefix    Write "x" for extra data
     * @return true|false
     */
    public static function update($blockInfo, $dat, $wdat, $xprefix=null)
    {
        return self::treat('update', $blockInfo, $dat, $wdat, $xprefix);        
    }

    /**
     * Method for record data getting
     * 
     * @param array $blockInfo  This is an array with two required elements ['src-block-id'=>..., 'tpl'=>...]
     * @param array $wdat       The array to build a WHERE clause in a sql expression ['rec'=>12, 1=>'apple']
     * @param array $xprefix    Write "x" for extra data
     * @return array            Array similar to template variable $dat
     */
    public static function get($blockInfo, $wdat, $xprefix=null)
    {
        return self::treat('get', $blockInfo, '', $wdat, $xprefix);        
    }

    /**
     * Method for deleting a record
     * 
     * @param array $blockInfo  This is an array with two required elements ['src-block-id'=>..., 'tpl'=>...]
     * @param array $wdat       The array to build a WHERE clause in a sql expression ['rec'=>12, 1=>'apple']
     * @param array $xprefix    Write "x" for extra data
     * @return true|false
     */
    public static function delete($blockInfo, $wdat, $xprefix=null)
    {
        return self::treat('delete', $blockInfo, '', $wdat, $xprefix);        
    }
    
  
    /**
     * Method for new record creation. 
     * 
     * @param array $blockInfo  This is an array with two required elements ['src-block-id'=>..., 'tpl'=>...]
     * @param array $dat        The values that you want to insert. Format [field=>value, ..., 'sort'=>]. These data overwrite the defaults given in the descriptor 
     * @param array $xprefix    Write "x" for extra data
     * @param array $tdd        Optional but advisable
     * @return array            Array similar to template variable $dat.  Even if you did not use the argument $dat, method returns data of new record.
     *
     * @todo Parameterize this method, check type inside
     */
    public static function insert($blockInfo, $dat=null, $xprefix=null, $tdd=null)
    {
        $terms = Blox::getTerms();
        $tbl = Blox::getTbl($blockInfo['tpl'], $xprefix);
        # Get $blockInfo['src-block-id']
        if (!$blockInfo['src-block-id']) {
            $sql = 'SELECT id FROM '.Blox::info('db','prefix').'blocks WHERE tpl=?';
            if ($result = Sql::query($sql,[$blockInfo['tpl']])) {
                $counter = 0;
                while ($row = $result->fetch_assoc()) {
                    if ($counter) { # More than one block
                        Blox::prompt($terms['no-src-block-id'],true);
                        return false;
                    }
                    $blockInfo['src-block-id'] = $row['id'];
                    $counter++;
                }
                $result->free();
            }
            if (!$blockInfo['src-block-id'])
                return;
            /* Способ 2,  Method 2, requires a non-empty data table
            $sql = 'SELECT DISTINCT `block-id` FROM '.$tbl.' LIMIT 2';
            $dtab = Sql::select($sql);
            if ($dtab[1])
                return false;
            else ($dtab[0]['block-id']) {
                $blockInfo['src-block-id'] = $dtab[0]['block-id'];                
            }*/
        }
        $sql = "SELECT MAX(`rec-id`) AS maxId FROM $tbl WHERE `block-id`=? GROUP BY `block-id`";
        if ($result = Sql::query($sql,[$blockInfo['src-block-id']]))
        {
            if ($row = $result->fetch_assoc())
                $newRecId = $row['maxId'] + 1;
            else # there were no records yet
                $newRecId = 1;
            $result->free();

            if ($tdd == null)
                $tdd = Tdd::get($blockInfo);

            if ($tdd)
            {
                $data = [];
                # defaults
                # Add elements from $tdd[$xprefix.'defaults'] to $dat 
                if ($tdd[$xprefix.'defaults']){
                    foreach ($tdd[$xprefix.'defaults'] as $k => $v)
                        if (!isset($dat[$k]))
                            $dat[$k] = $v;
                }
                if ($dat) {
                    foreach ($dat as $field => $value) {
                        if ($field == 'sort')
                            $data['sort'] = $value;
                        else
                            $data['dat'.$field] = $value;
                        $defaultFields[$field] = true;# To optimize. See below
                    }
                }
	            # Remember editor of record
                if ($tdd[$xprefix.'params']['user-id-field']) {
    				$userIdField = $tdd[$xprefix.'params']['user-id-field'];
                    if (!$defaultFields[$userIdField]) # Added to be able to change user-id by $defaults
                    {
                        if ($tdd[$xprefix.'params']['editor-of-records']['only-one-rec-allowed']) {
                            # To check whether user has at least one record 
                            $sql = "SELECT * FROM $tbl WHERE `block-id`=".$blockInfo['src-block-id']." AND dat{$userIdField}=".Blox::info('user','id')." LIMIT 1";
                            if (Sql::select($sql))
                                return false;
                        }
        	            if ($userIdField && Blox::info('user','id'))
                            $data['dat'.$userIdField] = Blox::info('user','id');
                        
                        # There is a forbid for authors to edit their old records
                        if ($tdd[$xprefix.'params']['editor-of-records']['forbid-old-recs-editing'])
                            $_SESSION[$xprefix.'fresh-recs'][$blockInfo['src-block-id']][$newRecId] = true;# this record will be editable for the session
                    }
                }
                if ($tdd[$xprefix.'params']['public']) # for limit for all
                    $_SESSION[$xprefix.'fresh-recs'][$blockInfo['src-block-id']][$newRecId] = true;# this record will be editable for the session

                # newSortId
                if (!$data['sort']) {
                    $sql = 'SELECT MAX(sort) AS maxSortId FROM '.$tbl.' WHERE `block-id`=? GROUP BY `block-id`';
                    $sqlValues = [$blockInfo['src-block-id']];
                    if ($result = Sql::query($sql,$sqlValues)) {
                        if ($row = $result->fetch_assoc())
                            $data['sort'] = $row['maxSortId'] + 1;
                        else
                            $data['sort'] = 1;
                        $result->free();
                    }
                }

                # Generate block id
                if ($typesDetailsB = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['block'], 'only-name')) {
                    foreach ($typesDetailsB as $field => $aa) {
                        $generatedBlockId = Admin::genBlockId($blockInfo['src-block-id'], $newRecId, $field, $xprefix); # extra data block generated here
                        $data['dat'.$field] = $generatedBlockId;
                    }
                }

                $data['block-id'] = $blockInfo['src-block-id'];
                $data['rec-id']   = $newRecId;
                Data::insert($tbl, $data);
                # Retrive all data again since there can be defaults
                $dat2 = self::get($blockInfo, ['rec'=>$newRecId], $xprefix);
                                
                # Convert time format. SIMILAR
                                
                if ($typesDetails_datetime  = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['date','datetime', 'time'], 'only-name')) {
                    foreach($typesDetails_datetime as $field => $aa) {
                        if ($dat2[$field]) {
                            if (Blox::info('site', 'date-time-formats',$aa['name']))
                                $dat2[$field] = date(Blox::info('site', 'date-time-formats',$aa['name']), strtotime($dat2[$field]));                            
                        }
                    }
                }
            
                $_SESSION['Blox']['new-rec-id'] = $newRecId; # Will be DEPRECATED: To delete a new record, if data will not be accepted in update.php 
                return $dat2;
            }
        }
        else {
            Blox::error($terms['max-rec-id-error'].' '.$sql,1);
            return false;
        }
    }


    
    
    
    # For Blox tab tables
    private static function treat($method, $blockInfo, $dat, $wdat=null, $xprefix='')
    {
        if ($blockInfo['src-block-id'])
            $wdata['block-id'] = $blockInfo['src-block-id'];
        if ($wdat['rec'])
            $wdata['rec-id'] = Sql::sanitizeInteger($wdat['rec']);
       
        foreach ($dat as $field => $value) {
            if ('sort'==$field)
                $data['sort'] = $value;
            else { 
                $field = Sql::sanitizeInteger($field);
                if ($field > 0)
                    $data['dat'.$field] = $value;
            }
        }
        if ($wdat) {
            foreach ($wdat as $field => $value) {
                if ($field == 'rec')
                    $wdata['rec-id'] = Sql::sanitizeInteger($wdat['rec']);
                else {
                    $field = Sql::sanitizeInteger($field);
                    if ($field > 0)
                        $wdata["dat$field"] = $value;
                }
            }
        }
        $tbl = Blox::getTbl($blockInfo['tpl'], $xprefix);
        if ($method == 'delete') {
            if (Data::delete($tbl, $wdata))
                return true;
        } elseif ($method == 'update') {
            if (Data::update($tbl, $data, $wdata))
                return true;
        } elseif ($method == 'insert') { # Not need?
            if (Data::insert($tbl, $data))
                return true;
        } elseif ($method == 'replace') { # NOT IN USE
            if (Data::replace($tbl, $data))
                return true;
        } elseif ($method == 'get') {
            if ($row = Data::getRow($tbl, $wdata)) {   
                $recId = $row[0];
                unset($row[0]); # when use array_shift($row) - all keys will shift
                if ($row) { # If there area data and at least: `rec-id`, `block-id`, sort columns
                    $sortNum = array_pop($row);
                    $blockId = array_pop($row);
                    $row = ['rec'=>$recId] + $row;
                    $row['block'] = $blockId; # array_push - does not support associative arrays
                    $row['sort'] = $sortNum;
                } else # is it necessary?
                    $row['rec'] = $recId;
                return $row;
            } else {
                return false;
            }
        }
    }
    
    
}