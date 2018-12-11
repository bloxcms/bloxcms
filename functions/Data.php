<?php       

/**
 * Class for inserting, updating and getting data from any table of site
 */
class Data
{   
    /**
     * Method for new record creation. 
     * 
     * @param string    $table    Full table name, including db-prefix
     * @param array     $data   Data to insert, in format: columnName => value
     * 
     * @return true|false
     */
    public static function insert($table, $data)
    {
        return self::treat('insert', $table, $data);        
    }

    public static function replace($table, $data)
    {
        return self::treat('replace', $table, $data);        
    }


    
    public static function update($table, $data, $wdata)
    {
        if ($data)
            return self::treat('update', $table, $data, $wdata);
    }


    public static function get($table, $wdata)
    {
        return self::treat('get', $table, '', $wdata);        
    }

    public static function delete($table, $wdata)
    {
        return self::treat('delete', $table, '', $wdata);        
    }
    

    public static function getRow($table, $wdata)
    {
        return self::treat('getRow', $table, '', $wdata);        
    }


    private static function treat($method, $table, $data, $wdata=null)
    {
        $table = Sql::sanitizeName($table);
        if ($method == 'update' || $method == 'get' || $method == 'delete' || $method == 'getRow') {
            /* Produces garbage reports foe extradata with $wdata=[]
            if (!$wdata) {
                Blox::prompt(sprintf(Blox::getTerms('no-wdata'), ' Data::'.$method.'('.$table.', ...)'), true);
                return;
            }
            */
        } else {
            if (!$data) {
                Blox::prompt(sprintf(Blox::getTerms('no-data'), ' Data::'.$method.'('.$table.', ...)'), true);
                return;            
            }
        }
        
        $setSql = '';
        $sqlValues = [];
        
        if ($data) { # INSERT, REPLACE, UPDATE
            $tableColsInfo = Admin::getTableColsInfo($table);
            foreach ($data as $col => $value) {
                $value = self::reduceValue($table, $col, $value, $tableColsInfo[$col]);
                # If data is not compatible with the field type, then change data
                if (is_null($value))
                    $setSql .= ', `'.$col.'`=NULL';
                else {
                    $setSql .= ', `'.$col.'`=?';
                    $sqlValues[] = $value;
                    
                }
            }
            if ($setSql)         
            {
                # Allow invalid date and time
                if ($GLOBALS['Blox']['allow-invalid-dates'])
                    if (isEmpty(Sql::query("SET @@session.sql_mode = 'ALLOW_INVALID_DATES'")))
                        Blox::error("Error 001 in function: ".__FUNCTION__);

                # Unique column data should have value
                if ($method === 'insert' || $method === 'replace')  { # 'replace' NOT IN USE 
                    if ($result = Sql::query('SHOW KEYS FROM '.$table)) # Get unique keys from table
                        while ($row = $result->fetch_assoc())
                            if (empty($row['Non_unique']))
                                if (isEmpty($data[$row['Column_name']]))
                                    Blox::prompt(sprintf(Blox::getTerms('no-col-unique-val'), '<b>Data::'.$method.'</b>()', '<b>'.$row['Column_name'].'</b>'));
                }               
                $setSql2 = $table.' SET '.substr($setSql, 2); # remove initial ', '
                
                if ($method === 'insert')
                    $sql = 'INSERT ';
                elseif ($method === 'replace')# NOT IN USE
                    $sql = 'REPLACE ';
                $sql .= $setSql2;
            }
        }

        $whereSql = '';
        if ($wdata) {
            foreach ($wdata as $col => $value) {
                $value = self::reduceValue($table, $col, $value, $tableColsInfo[$col]);
                # If data is not compatible with the field type, then change data
                if (is_null($value))
                    $whereSql .= ' AND `'.$col.'` IS NULL'; # NOTTESTED 2016-05-01 //$value = 'NULL';
                else {
                    $whereSql .= ' AND `'.$col.'`=?'; # NOTTESTED 2016-05-01
                    $sqlValues[] = $value;
                }
            }
            if ($zz = substr($whereSql, 4))
                $whereSql = ' WHERE '.$zz;
        }
        
        if ($method === 'get' || $method == 'getRow') {
            $sql = 'SELECT * FROM '.$table.$whereSql.' LIMIT 2';
            if ($result = Sql::query($sql, $sqlValues)) {
                if ($result->num_rows > 1) {
                    $result->free();
                    Blox::prompt(sprintf(Blox::getTerms('more-then-one-record')), true); 
                    return false;
                } else {
                    $fetchFunc = ($method == 'getRow') ? 'fetch_row' : 'fetch_assoc';
                    $row = $result->$fetchFunc();
                    $result->free();
                    return $row;
                }
            } else
                return false;
        } elseif ($method === 'delete' || $method === 'update') {
            if ($whereSql) {
                if ($method === 'delete')
                    $sql = 'DELETE FROM '.$table.$whereSql;
                elseif ($method === 'update')
                    $sql = 'UPDATE '.$setSql2.$whereSql;
            } else {
                Blox::prompt(sprintf(Blox::getTerms('no-wdata'), ' Data::'.$method.'('.$table.', ...)'), true);
                return;
            }
        }
//qq($sql);
        /** For INSERT, REPLACE, UPDATE, DELETE */
        if (isEmpty(Sql::query($sql, $sqlValues))) {
            Blox::error('Error 002 in method: Data::'.__FUNCTION__.'()');
            return false; 
        } else
            return true;
    }
 
 
 
   /**
    * Provides data in compliance with the types of columns
    * Attention! Not cover all cases of incorrect data.
    */
    private static function reduceValue($table, $field, $value, $fieldInfo)    
    {
        $prompt = '';
        ##$value = trim($value); # Do not trim!
        $type = &$fieldInfo['Type'];

        # All types
        $l['string']  = ['char', 'varchar', 'tinyblob', 'tinytext', 'blob', 'mediumblob', 'mediumtext', 'longblob', 'longtext'];//'text',
        $l['integer'] = ['tinyint', 'bit', 'smallint', 'mediumint', 'int', 'integer', 'bigint'];
        $l['real']    = ['float', 'double','double precision', 'real', 'decimal', 'dec'];
        $l['time']    = ['date','datetime','timestamp','time','year'];

        
        if (null === $value && $fieldInfo['Null']) # KLUDGE
        {
            $value = null;
        }
        elseif ((in_array($type, $l['string'])))
        {
            $valueLength = mb_strlen($value);
            # If the string is too long
            if ($fieldInfo['params'] && $fieldInfo['params'][0] < $valueLength)
                $value = mb_substr($value, 0, $fieldInfo['params'][0]);
        }
        elseif ((in_array($type, $l['integer']))) {
            $value = str_replace(' ', '', $value);
            if ($value === '' && $fieldInfo['Null'])
                $value = null;
            elseif ($fieldInfo['params'][0] < mb_strlen($value)) {
                if (!$GLOBALS['Blox']['dat-is-truncated'][$field]) {
                    $prompt .= ' '.sprintf(Blox::getTerms('more-then-n-digits'), '<b>'.$fieldInfo['params'][0].'</b>', $value);
                    $GLOBALS['Blox']['dat-is-truncated'][$field] = 1;
                }
                $value = ($fieldInfo['Null']) ? null : 0;
            } else {
                $value = (int)$value;
            }
        }
        elseif ((in_array($type, $l['real']))) // Tested on float and double
        {
            if ('' === $value) {
                $value = ($fieldInfo['Null']) ? null : 0;
            } elseif ($fieldInfo['params'][0] && $fieldInfo['params'][1]) { # the total number of characters && number of digits after zapatos
                $value = str_replace(' ', '', $value);
                $aa = explode('.', $value);
                $symbolsBeforeDot = mb_strlen($aa[0]);
                $symbolsAfterDot = mb_strlen($aa[1]);
                # Not numeric
                if (($symbolsBeforeDot && preg_match('/\D/', $aa[0])) || ($symbolsAfterDot && preg_match('/\D/', $aa[1]))) {
                    $prompt .= ' '.sprintf(Blox::getTerms('non-digits'));
                    $GLOBALS['Blox']['dat-is-truncated'][$field] = 1;
                    $value = 0;
                } else {# number of characters
                    $bb = $fieldInfo['params'][0] - $fieldInfo['params'][1]; # signs before the dot in database
                    if ($bb < $symbolsBeforeDot) {
                        if (!$GLOBALS['Blox']['dat-is-truncated'][$field]) {
                            $prompt .= ' '.sprintf(Blox::getTerms('more-then-n-digits-before'), '<b>'.$bb.'</b>');
                            $GLOBALS['Blox']['dat-is-truncated'][$field] = 1;
                        }
                        $value = 0;
                    }
                    /*
                    # Do not check the excess of decimals after the dot, because MySQL automatically rounds
                    else {
                        if ($fieldInfo['params'][1] < $symbolsAfterDot) {
                            if (!$GLOBALS['Blox']['dat-is-truncated'][$field])
                            {
                                $prompt .= "В данных поля <b>$field</b> не может быть более, чем <b>{$fieldInfo['params'][1]}</b> цифр после запятой ";
                                $GLOBALS['Blox']['dat-is-truncated'][$field] = 1;
                            }
                            $value = "'0'";
                        } else
                            $value = "'$value'";
                    }
                    */
                }
            }
        } # real
        elseif ((in_array($type, $l['time']))) {
            if (($fieldInfo['Null'])) {
                if (!$value) {
                    $value = null;
                } elseif ( # NOTTESTED. These conditions are already taken into account in scripts/update.php. See #0000-00-00. 
                    ($type == 'datetime' && $value == '0000-00-00 00:00:00') ||
                    ($type == 'date' && $value == '0000-00-00') ||
                    ($type == 'year' && $value == '0000')
                  ) { 
                    $value = null;//$value = date('Y-m-d'); #0000-00-00 is denied. It produces negative strtotime(). Put current date instead
                }
            } else {
                ;#"not null" is not solved yet
            }
            $GLOBALS['Blox']['allow-invalid-dates'] = true; # To allow wrong date and time. Instead, it is better to check right here.
        }
        elseif ('set' == $type) {
            # If there is forbidden data in the list, exclude it
            # MySQL writes set-data in lower case!
            $arr = explode(',', $value);
            $included = '';
            $excluded = '';
            foreach ($arr as $a) {
                if (in_array(str_replace("'", '', $a), $fieldInfo['params']))
                    $included .= ','.$a;
                else {
                    if ($a)
                       $excluded .= ','.$a;
                }
            }
            if ($excluded) {
                $excluded = substr($excluded, 1);  # remove initial ','
                if (!$GLOBALS['Blox']['dat-is-truncated'][$field]) {
                    $prompt .= ' '.sprintf(Blox::getTerms('not-accepted'), '<b>'.$excluded.'</b>');
                    $GLOBALS['Blox']['dat-is-truncated'][$field] = 1;
                }
                if ($included)
                    $value = substr($included, 1);  # remove initial ','
            }
        }
        elseif ('enum' == $type) {
            # If there is forbidden data in the list, exclude it. 
            # MySQL writes set-data in lower case!
            if (!in_array("'".$value."'", $fieldInfo['params'])) {
                if (!$GLOBALS['Blox']['dat-is-truncated'][$field]) {
                    $prompt .= ' '.sprintf(Blox::getTerms('not-allowed'), '<b>'.$value.'</b>');
                    $GLOBALS['Blox']['dat-is-truncated'][$field] = 1;
                }
                $value = '';
            }
        }

        if ($prompt)
            Blox::prompt($prompt.' ('.sprintf(Blox::getTerms('props-list'), '<b>'.$field.'</b>', '<b>'.$type.'</b>', '<b>'.$table.'</b>').').',  true);
        return $value;

        /*
        string
            DECIMAL[(M[,D])]
            CHAR(M)
            VARCHAR(M)
            TINYBLOB , TINYTEXT
            BLOB , TEXT
            MEDIUMBLOB , MEDIUMTEXT
            LONGBLOB , LONGTEXT

        integer
            TINYINT[(M)]
            SMALLINT[(M)]
            MEDIUMINT[(M)]
            INT[(M)]
            BIGINT[(M)]
        real
            FLOAT()
            FLOAT[(M,D)]
            DOUBLE[(M,D)]
        time
            DATE DATETIME TIMESTAMP[(M)] TIME YEAR[(2|4)]
        select
            ENUM('a1','a2',...)
            --SET('a1','a2',...)
        */

    }
    
    
}