<?php

/** 
 * @todo 
 * @todo How to prepare a statement to execute it in loop with Sql:: methods?. Use the regular ->prepare() not Sql::
 * @todo bind_result vs get_result - http://stackoverflow.com/questions/18753262/example-of-how-to-use-bind-result-vs-get-result
 */


class Sql
{

    private static 
        $db,
        $sqlParams = [],
        $sqlParamsCounter = 0,
        $repeatedParamsCounts = [],
        $oneByOneQueriesCounts = [],
        $prevQuery = ''
    ;
    
    
            
    /**
     * Connect to the database
     */
    public static function setDb($dbhost, $dbuser, $dbpass, $dbname)
    {
        if (self::$db) 
            Blox::prompt(Blox::getTerms('already-connected-to-db'), true);
        else {
            self::$db = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
            if (!self::$db) {
                echo PHP_EOL.'<br>MySQL error: '.mysqli_connect_errno();
                echo PHP_EOL.'<br>'.mysqli_connect_error();
                exit;
            }
            @mysqli_set_charset(self::$db, 'utf8');# mysql_set_charset(utf-8); Works too
        }
    }
    
    public static function getDb()
    {
        return self::$db;
    }
    


   /**
    * Run sql query of any kind
    *
    * @param string $sql Usual SQL statement or prepared SQL statement (parameterized with "?").
    * @param array $params Data for a prepared SQL statement.
    * @param string $types A string that contains one or more characters which specify the types for the corresponding bind variables. Available characters: i(integer), d(double), s(string), b(blob). Use this parameter if data types in $params are not declared explicitly
    * @return object|int|false
    *   object: for SELECT, SHOW, DESCRIBE, EXPLAIN
    *   int: for INSERT, UPDATE, DELETE (the number of affected rows)
    * 
    * @example
    *     $result = Sql::query(
    *         'SELECT * FROM '.Blox::getTbl('media_nav').' WHERE `block-id`=? AND dat3=?', 
    *         [200,'photo.jpg']
    *     );
    *     while ($row = $result->fetch_assoc()) {
    *       ...
    *     }
    *     $numOfRows = $result->num_rows;
    *     $result->free();
    *
    * @todo Use try{}
    */
    public static function query($sql, $params=[], $types='')
    {
        $sql = trim($sql);
        # Params prepared by Sql::
        if (!$params)
            $params = self::getParamsAndRewriteSql($sql); # &$sql
        elseif (Blox::info('user','user-is-admin') && self::getParamsAndRewriteSql($sql))
            Blox::prompt(sprintf(Blox::getTerms('double-parameterization'), $sql), true);

        if (Blox::info('user','user-is-admin') && Blox::info('site', 'log-repeated-sql-queries'))
            self::countQueries($sql, $params);

        if ($params) 
        { # create a prepared statement with parameterized query 
            $stmt = self::$db->prepare($sql);
            if (false === $stmt) { # because of syntax errors, missing privileges,...
                # @see MySQLi prepared statements error reporting http://stackoverflow.com/questions/2552545/mysqli-prepared-statements-error-reporting
                $erReport = self::$db->error; # prepare() failed
                $result = false;
            } else {
                foreach ($params as $i => $param) {  
                    if (!$types) {         
                        $type = gettype($param);
                        if ('integer' == $type) {
                            $types2 .= 'i';
                        } elseif ('double' == $type) {
                            $types2 .= 'd';
                        } elseif ('boolean' == $type) {
                            $params[$i] = $param ? 1 : 0 ; 
                            $types2 .= 'i';
                        /* 
                        } elseif ('NULL' == $type) {
                            $params[$i] = null; 
                            $types2 .= 's';
                        */
                        } elseif (in_array($type, ['array','object','resource','unknown type'])) {# removed from this array: 'NULL' # But null value doesnt work when comparing, i.e "WHERE dat1=?". In this case use:  "WHERE dat1 IS NULL" 
                            Blox::prompt(sprintf(Blox::getTerms('not-allowed-type-of-param'), $sql, $i, $type) , true);
                        } else
                            $types2 .= 's';
                    }
                    $params2[$i] = &$params[$i]; # Must be by reference
                }
                if ($types)
                    $types2 = $types;
                
                /**
                 * @todo Error report for bind_param()
                 *     $rc = $stmt->bind_param('iii', $x, $y, $z); // bind_param() can fail because the number of parameter doesn't match the placeholders in the statement or there's a type conflict(?), or ....
                 *     if ( false===$rc )
                 *         die('bind_param() failed: ' .$stmt->error);
                 *     0r try this KLUDGE (not works!)
                 *         if ($bb = $stmt->error)
                 *             $erReport = $bb; # bind_param() failed
                 *
                 * @todo Anoter approach
                 *     Reflection for build: bind_param('...', $x, $y, $z, ...) from array of params
                 *     Works in PHP5.3, but not in 5.5. Works again in 5.6 http://stackoverflow.com/questions/26090692/php-invokeargs-parameters-changed-how-to-return-them
                 *        $reflex = new ReflectionClass('mysqli_stmt'); 
                 *        $method = $reflex->getMethod('bind_param'); 
                 *        $method->invokeArgs($stmt, $params2); 
                 */
                call_user_func_array(
                    [$stmt, 'bind_param'], 
                    array_merge([$types2], $params2)
                );
                $exeResult = $stmt->execute(); # $exeResult is always boolean (false on error)
                if (false === $exeResult) {
                    $erReport = $stmt->error; # execute() failed
                    $result = false;
                } else {
                    # KLUDGE: Detect type of statement
                    preg_match('~^\w+~u', $sql, $matches);
                    $command = mb_strtolower($matches[0]);
                    if (in_array($command, ['select','show','describe','explain'])) {
                        $commandType = 'select';
                    } elseif (in_array($command, ['update','insert','replace','delete'])) {
                        $commandType = 'update';
                    } else { # safety code
                        if (-1 == $stmt->affected_rows) # KLUDGE: Detect SELECT like statement
                            $commandType = 'select';
                        else
                            $commandType = 'update';
                    }
                    
                    if ('select' == $commandType) {
                        /** 
                         * For SELECT, SHOW, DESCRIBE, EXPLAIN, HELP   # instead of bind_result.  If it does not work, take the function by Darren from http://php.net/manual/en/mysqli.prepare.php   
                         * $result is object "if ('object' === gettype($result))"
                         * @todo get_result() does not work without mysqlnd extension. # mysqlnd http://www.pvsm.ru/mysql/68059
                         * @see Here is analog of get_result() http://php.net/manual/ru/mysqli-stmt.get-result.php See: Anonymous
                         */
                        $result = $stmt->get_result();
                        if (false === $result) {
                            $erReport = $stmt->error; # get_result() failed
                        }
                    } else { # 'update' == $commandType
                        $result = self::$db->affected_rows; # For INSERT, UPDATE, DELETE
                        if (false === $result) {
                            $erReport = self::$db->error; # affected_rows failed
                        }
                    }
                }
                $stmt->close();
            }
            
            if ($erReport) {
                $z = 'Sql: '.$sql."\nParams: ".print_r($params, true).'Error: '.$erReport;
                if (Blox::info('user','user-is-admin'))
                    $z.= "\nBacktrace: ".print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 9), true);
                Blox::error($z);
            }
        } 
        else # Not parameterized (not prepared)
        { 
            # $result is object       or boolean false for SELECT
            # $result is boolean true or boolean false for INSERT
            $result = self::$db->query($sql);
            if ('object' === gettype($result)) { # mysqlnd http://www.pvsm.ru/mysql/68059
                # For SELECT, SHOW, DESCRIBE, EXPLAIN
                # Check for unsafe unparameterized SELECT statements
                if (Blox::info('user','user-is-admin')) {
                    if (mb_strpos($sql, 'SELECT') !== false) { # Only for "SELECT" statements 
                        $sql2 = str_replace("''", '', $sql); # temporarily remove empty string data 
                        if (mb_strpos($sql2, "'") !== false) # statements with single quotes
                            Blox::prompt(sprintf(Blox::getTerms('no-params'), $sql), true);
                    }
                }
            } elseif (true === $result) {
                $result = self::$db->affected_rows; # For INSERT, UPDATE, DELETE    # "$result===0" i.e. UPDATE affected_rows
            } else {
                $z = "Sql: ".$sql."\nError: ".self::$db->error;
                if (Blox::info('user','user-is-admin'))
                    $z.= "\nBacktrace: ".print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 9), true);
                Blox::error($z);
                $result = false;
            }
        }
        return $result;
    }



    
    
    
        
   /**
    * Get data from a table by executing a query "SELECT * FROM"
    *
    * @param string $sql Usual SQL statement "SELECT * FROM" or prepared SQL statement (parameterized with "?").
    * @param bool $isTab Return data in template variable format $tab, i.e. numeric keys instead of column names as keys (dat9)
    * @param array $params Data for a prepared SQL statement.
    * @param string $types Explicit declaration of data types of $params. For examle: 'iis'. All types: integer, double, string, blob.
    * @return array
    *    
    * @example
    *     $tab2 = Sql::select(
    *         'SELECT * FROM '.Blox::getTbl('media_nav').'  WHERE `block-id`=?  ORDER BY sort', 
    *         [200]
    *         '',
    *         true,
    *     );
    */
    public static function select($sql, $params=[], $types='', $isTab=false)
    {
        if ($result = Sql::query($sql, $params, $types)) {
            if ($isTab) {
                while ($row = $result->fetch_row()) {
                    $recId = $row[0];
                    unset($row[0]);
                    if ($row) { # If there area data and at least: `rec-id`, `block-id`, sort columns
                        $sortNum = array_pop($row);
                        $blockId = array_pop($row);
                        $row = ['rec'=>$recId] + $row;
                        $row['block'] = $blockId; # array_push - does not support associative arrays
                        $row['sort'] = $sortNum;
                    } else { # is it necessary?
                        $row['rec'] = $recId;
                    }   
                    $tab[] = $row;
                }
            } else {
                while ($row = $result->fetch_assoc())
                    $tab[] = $row;
            }
            $result->free();
            return $tab;
        }
    }
    
    
    


    /**
     * DEPRECATE?
     * Safe sql string
     * 
     * @todo http://habrahabr.ru/post/148701/
     */
    public static function sanitize($value)
    {
        if (get_magic_quotes_gpc())
            $value = stripslashes($value);
        $value = mysqli_real_escape_string(self::$db, $value);//appear slashes: \'1'\
        return $value;
    }



    /** 
     * Premare integer data for sql statement, i.e. convert data to integer and return it as a string
     *
     * @param int|string $x 
     * @param string|array $options
     *     'zero' — method accepts 0 and '0' as valid data
     *     'negative' — method accepts negative integers (-9 and '-9') as valid data
     * @return int as a string 
     *
     * @todo Replace this method by (int)$x wherever it is possible
     *
     * @example $x2 = Sql::sanitizeInteger($x, ['zero','negative']);
     */
    public static function sanitizeInteger($x, $options=[])
    {
        if ($options)
            Arr::formatOptions($options);
        
        if (Str::isInteger($x, $options)) {
            $x2 = (int)$x; # '09' --> 9
            return "$x2"; # --> '9'
        }
    }
    
    
    /**
     * Check if a table exists.
     *
     * @param string $table You can apply two wildcard characters in the pattern: 
     *     % matches 0 or more characters, 
     *     _ matches exactly one character.
     *
     * @example if (tableExists('%users')) ...
     */
    public static function tableExists($table)
    {
        $table = trim($table, '`');
        $sql = "SHOW TABLES LIKE '".$table."'"; # not "`"
        if ($result = Sql::query($sql)) {
            $aa = $result->num_rows;
            $result->free();
            return $aa;
        }
    }
    
    
    
   /**
    * Sanitize SQL identifier`s names: database, table, index, column, alias, view, stored procedure, partition, tablespace, and other object names
    *
    * @param string $str
    * @return string|false
    */
    public static function sanitizeName($str)
    {
        if (preg_match('[^0-9a-zA-Z$_-/!.]~u', $str)) {
            Blox::prompt(sprintf(Blox::getTerms('incorrect-identificator'), '<b>'.$str.'</b>'), true);
            return false;
        }
        return $str;
    }


    /**
     * Returns the same string $tpl, if it is the correct template name otherwise returns false.
     */
    public static function sanitizeTpl($tpl)
    {
        if (preg_match_all('~[^0-9a-zA-Z_-!.]~', $tpl, $matches)) {
            $signs = array_unique($matches[0]);
            $signsList = '';
            foreach ($signs as $sign)
                $signsList .= $sign;
            Blox::prompt(sprintf(Blox::getTerms('incorrect-tpl-name'), '<b>'.$tpl.'</b>', '<b>'.$signsList.'</b>'),  true);
            return false;
        }
        return $tpl;
    }
    
    

        
   /**
    * Register named parameter for prepared statement. Later it will be converted to anonymous positional placeholder "?" 
    * 
    */
    public static function parameterize($val) //addParam
    {
        $counter = self::$sqlParamsCounter = self::$sqlParamsCounter + 1;
        self::$sqlParams[$counter] = $val;
        return '{{'.$counter.'}}';
    }
    private static function getParamsAndRewriteSql(&$sql)
    {
        if (preg_match_all('~\{\{(\d+)\}\}~', $sql, $matches)) {
            if ($matches[1]) {
                foreach ($matches[1] as $ser) {
                    if (isset(self::$sqlParams[$ser]))
                        $params[] = self::$sqlParams[$ser];
                    else
                        $lostParams[] = $ser;
                }
                if ($lostParams)
                    Blox::prompt(sprintf(Blox::getTerms('no-placeholders-data'), '<b>'.implode(', ', $lostParams).'</b>', $sql), true);  
                if ($params)  {
                    $sql = preg_replace('~\{\{\d+\}\}~', '?', $sql); 
                    return $params;
                }
            }
        }
    }
    

    /**
     * Check for repeated SQL-queries.
     *
     * @todo Maybe to save repeated results of Sql::query in var?
     */
    private static function countQueries($sql, $params=[])
    {
        # One by one queries - for prepared stmts
        if ($sql == self::$prevQuery) {
            if (isset(self::$oneByOneQueriesCounts[$sql]))
                self::$oneByOneQueriesCounts[$sql]++;
            else
                self::$oneByOneQueriesCounts[$sql] = 0;   
        }   
        self::$prevQuery = $sql;

        # Equal queries - fore reuse the results
        $found = false;
        if (isset(self::$repeatedParamsCounts[$sql])) {
            foreach (self::$repeatedParamsCounts[$sql] as $i=>$aa) {
                if ($aa['params'] == $params) {
                    self::$repeatedParamsCounts[$sql][$i]['count']++;
                    $found = true;
                    break;
                }
            }
        }
        if (!$found)
            self::$repeatedParamsCounts[$sql][]['params'] = $params;
    }
    public static function logQueries()
    {
        # One by one queries - for prepared stmts
        if (self::$oneByOneQueriesCounts) {
            arsort(self::$oneByOneQueriesCounts);
            $htm = '';
            foreach (self::$oneByOneQueriesCounts as $sql=>$num)    {
                if ($num)
                    $htm.="\n".$num.' :'.$sql;
            }
            if ($htm)
                Blox::error(Blox::getTerms('one-by-one-queries').$htm); 
        }
        # Equal queries - fore reuse the results
        if (self::$repeatedParamsCounts) {
            $htm = '';
            foreach (self::$repeatedParamsCounts as $sql=>$aa) {
                foreach ($aa as $i => $bb) {
                    if ($bb['count']) {
                        $htm.="\n".$bb['count'].': '.$sql;
                        if ($bb['params']) {
                            $p = '';
                            foreach ($bb['params'] as $param)
                                $p.=', '.$param;
                            $p = substr($p, 2);
                            $htm.=';  ['.$p.']';
                        }
                    }
                }
            }
            if ($htm)
                Blox::error(Blox::getTerms('repeated-params').$htm); 
        }
    }


    /**
     * Build a piece of sql statement based on the array obtained from Request::get($blockId, $filter) or Query::get($filter)
     * @param string $filter 'pick|p'. To do 'search',,,
     * @param array $filterRequest Array with structure as obtained by Request::get($regularId,'pick') or Query::get('pick')
     * @param array $options {
     *   @var bool $parameterize Use only Sql::parameterize() for other parts of sql
     *   @var bool $case-sensitive 
     *   @var string $tbl Table name as prefix for column names
     * }
     *
     */
    public static function build($filter, $filterRequest, $options=[])
    {
        if (!$filterRequest)
            return false;
        if ($options)
            Arr::formatOptions($options);
        $options += ['parameterize'=>false, 'case-sensitive'=>false, 'tbl'=>''];
        if ($options['tbl'])
            $tbl=$options['tbl'].'.';
        if ($filter=='p') {
            $filter = 'pick';
            $r2 = $filterRequest;
            $filterRequest = [];
            foreach ($r2 as $field => $v)
                $filterRequest[$field]['eq'] = $v;
        }
        if ($filter=='pick') {
            $signs = ['lt'=>'<', 'le'=>'<=', 'eq'=>'=', 'ge'=>'>=', 'gt'=>'>', 'ne'=>'!='];
            foreach ($filterRequest as $field => $aa) {
                if ($field == 'rec')
                    $col = $tbl.'.`rec-id`';
                else
                    $col = $tbl.'dat'.(int)$field;
                foreach ($aa as $k=>$val) {
                    if ($k && $signs[$k]) {
                        if ($options['parameterize'])
                            $val = Sql::parameterize($val);
                        else
                            $val = "'".$val."'";
                        $psql = $col.' '.$signs[$k].' '.$val;
                        if ($options['case-sensitive']) # Case sensitive comparision
                            $psql .= ' COLLATE utf8_bin';
                        $sql .= ' AND '.$psql;
                    }
                }
            }
            if ($sql)
                $sql = substr($sql, 4);  # remove initial ' AND '
        }
        return $sql;
    }    

    
}
