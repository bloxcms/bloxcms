<?php
/**
 * @todo Apply Sql::parameterize() in Request::getPartSql(), Request::getPickSqls(), Request::getSearchSqls(), Request::getSortSql(), Request::getWhereAndExtraSql(), 	
 */
 
class Request
{
    private static $requests = [];
    
    /**
     * Get all requests to the block
     *
     * @param First param is ID of the block
     * @param Second param is the name of request (one of: 'backward', 'limit', 'part', 'pick', 'search', 'single', 'sort')
     * @param Third and other params are keys of the request
     * @return array
     * 
     * @example
     *     $request = Request::get()
     *     $request = Request::get($regularId, 'search')
     *     $request = Request::get($regularId, 'pick', $field, 'eq')
     *
     * @todo Use Arr::getByKeys()
     */
    public static function get() # params: regularId, filter, x1, x2
    {
        $args = func_get_args();        
        $size = Arr::getUnbrokenSize($args); # Higher arguments should not be empty
        if ($size==0) # No arguments
            return self::$requests;
        elseif ($size==1) # One argument
            return self::$requests[$args[0]];
        elseif ($size==2)
            return self::$requests[$args[0]][$args[1]];
        elseif ($size==3)
            return self::$requests[$args[0]][$args[1]][$args[2]];
        elseif ($size==4) 
            return self::$requests[$args[0]][$args[1]][$args[2]][$args[3]];
        elseif ($size==5)
            return self::$requests[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]];
    }
    
    
   
    /**    
     * The fewer arguments, the more will be removed
     */
    public static function remove()
    {
        $args = func_get_args();        
        $size = Arr::getUnbrokenSize($args); # Higher arguments should not be empty
        if ($size==0) # No arguments
            self::$requests = [];
        elseif ($size==1) # One argument
            unset(self::$requests[$args[0]]);
        elseif ($size==2)
            unset(self::$requests[$args[0]][$args[1]]);
        elseif ($size==3)
            unset(self::$requests[$args[0]][$args[1]][$args[2]]);
        elseif ($size==4)
            unset(self::$requests[$args[0]][$args[1]][$args[2]][$args[3]]);
        elseif ($size==5)
            unset(self::$requests[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]]);
    }
    

    /** 
     * Reset and build a request to the blocks. Call before calling the functions: getTab, getTable, getTableSql.
     * Request::set() - by default (without arguments) will be processed global vars GET and POST
     * Request::set('') - Use this for resetting requests (all records will be retrieved).
     * Put this method above Tdd::get() in order to apply $defaults = Request::get() in tdd file.
    */
    public static function set($query=null)
    {
        self::$requests = [];
        # By default (without arguments) will be processed global vars GET and POST
        if (is_null($query))
            $query = self::formatUrlParams($_REQUEST);
        if ($query)
            self::add($query);
    }


    private static function formatUrlParams($urlParams)
    {
        # Part 1
        $filters = ['block'=>true,'backward'=>true,'limit'=>true,'part'=>true,'p'=>true,'pick'=>true,'charfield'=>true, 'charblock'=>true,'char'=>true, 's'=>true,'search'=>true,'single'=>true, 'sort'=>true, 'script'=>true,'src'=>true]; # The order is important for: 'p', 'pick', 's', 'search'
            
            
        foreach ($urlParams as $k=>$v) {
            if ($filters[$k])
                $queries[$k] = $urlParams[$k];
        }
        /** #SETS POSTPONED 2017-08-11
         * Experimental url queries. 
         * Same type OR logic in one set (a|b|c). No nested sets
         * @example block=2&(p[1]=98&$p[2]=99)&limit=10
        $getArrValue = function($arr, &$keys) {
            while (is_array($arr))
                foreach ($arr as $k=>$v) { # One loop
                    $keys[] = $k;
                    $arr = $v;
            }
            return $arr;
        };
        $elements = [];
        $setIsOpen = false;
        foreach ($urlParams as $k=>$v) {
            # Open a set
            if ('('==$k[0]) {
                $setIsOpen = true;
                $k = substr($k,1);
            }
            if ($setIsOpen) {

                if ('$'==$k[0]) {
                    $type = '||';
                    $k = substr($k,1);
                } else
                    $type = '&&';
                $keys = [];
                $v2 = $getArrValue($v, $keys);
                if (')'==substr($v2, -1)) {
                    $v2 = substr($v2, 0, -1);
                    $setIsOpen = false;
                    if (is_array($v))
                        $v = Arr::addByKeys([], $keys, $v2);
                    else
                        $v = $v2;
                }
                   
                if ($filters[$k])
                    $elements[] = [$k=>$v]; 
                # Close a set
                if (!$setIsOpen) {
                    $queries['sets'][][$type] = $elements; # Put every param (even multidimensional) in separate element
                    $elements = [];
                }
            } else { # Normal url-params
                if ($filters[$k])
                    $queries[$k] = $urlParams[$k];
            }
         
        }
        */

        # Part 2 - put 'block' as key
        if ($queries){              
            if ($queries['src']) {
                if ($queries['script']) # For "script" request
                    $blockId = $queries['src']; 
                else { # ?src=...&block=... or ?src=...&
                    if ($queries['block'])
                        $blockId = $queries['block'];
                    else
                        $blockId = $queries['src'];
                }
                unset($queries['src']);
            } elseif ($queries['block']) {
                $blockId = $queries['block'];
                unset($queries['block']);
            }
            
            if ($blockId) {
                if ($queries)
                    $query[$blockId] = $queries;
                else 
                    return false;
            } else 
                return false;
        }
        return $query;
    }




  
    /**
     * @example
     *     $query ='block=1&part=2'; //or $query[1]['part'] = 2; 
     *     Request::add($query);
     * @todo Use Arr::addByKeys($arr, $keys, $value) and Nav::add()
     */ 
    public static function add($query)
    {
        /**
         * $query - input data
         * $queries - temporary array to convert the url into an array
         * $params - the parameters related to one specific block
         */ 
        if (empty($query))
            return;
        # Argument in string form. Format it to the array.
        if (!is_array($query)) {
            $query2 = $query;
            $query = [];        
            if ($query2) { 
                $urlParams = Url::queryToArray($query2); # Not the final format
                $query = self::formatUrlParams($urlParams);
            }
        }

        if (empty($query))
            return;

        # Build array of Requests separately for each block
        foreach ($query as $regularId=>$params)
        {
            if (empty($regularId))
                continue;
            # Request by column name of a table. Url param "block" is arbitrary ID for binding of requests
            $isArbitraryRequestId = (Str::isInteger($regularId)) ? false : true;
            $requests = [];
            $pickRequests = [];
            $partRequests = [];
            $searchRequests = [];
            foreach ($params as $pKey=>$pVal) {
                if ('s'===$pKey) {
                    if (is_array($pVal)) {
                        foreach ($pVal as $field => $value)
                            $searchRequests[$field] = $value;
                    } else
                        $searchRequests = $pVal;
                } elseif ('search'===$pKey) {
                    if (is_array($pVal)) {
                        foreach ($pVal as $field => $value)
                            $searchRequests[$field] = $value;
                    } else
                        $searchRequests = $pVal;
                } elseif ('p'===$pKey) {
                    if (is_array($pVal)) {
                        foreach ($pVal as $field => $value)
                            $pickRequests2[$field]['eq'] = $value;
                        if ($pickRequests2)
                            $pickRequests = Arr::mergeByKey($pickRequests, $pickRequests2);
                    }
                } elseif ('pick'===$pKey) {
                    if (is_array($pVal))
                        $pickRequests = Arr::mergeByKey($pickRequests, $pVal);
                } elseif ('single'===$pKey) {
                    $requests['single'] = $pVal;
                } elseif ('backward'===$pKey) {
                    $requests['backward'] = $pVal;
                } elseif ('limit'===$pKey) {
                    $partRequests['limit'] = $pVal;
                } elseif ('part'===$pKey) {
                    if (is_array($pVal)) {
                        $partRequests = Arr::mergeByKey($partRequests, $pVal);
                    } else {
                        $partRequests['current'] = $pVal;
                    }
                } elseif ('script'===$pKey) {
                    $requests['script'] = $pVal;
                } elseif ('sort'===$pKey) {
            	    foreach ($pVal as $field => $order){
                        if ($order = mb_strtolower($order))
                            $requests['sort'][$field] = $order;
                    }
                } 
                /** #SETS POSTPONED 2017-08-11
                elseif ('sets'===$pKey) { # set of url-params (in parenthesis)
                    foreach ($pVal as $j => $set) {
                        foreach ($set as $type => $elements) { # one loop
                            foreach ($elements as $i=>$element) {
                                foreach ($element as $filter=>$v) { # one loop
                                    if ('p'===$filter) {
                                        foreach ($v as $field=>$v2) # one loop
                                            $requests['sets'][$j][$type][$i]['pick'][$field] = $v2;
                                    }
                                }
                            }
                        }
                    }
                }
                */
            }

            /**
             * SEARCH
             * Prepare a draft array ['texts'], not the final array, because you need to consider $tdd['params']['search']['fields']
             * @todo Make the priority of the url-parameters on tdd-parameters
             *      Array of search request
             *        1d
             *        ['what'] RESERVED
             *        ['where']
             *        ['highlight']
             *        2d, 3d
             *        ['fields-of-words'][]
             *        ['fields'][]
             *        ['patterns'][]
             *        ['replacements'][]
             *        ['texts'][]
             *        ['words'][]
             */
            if ($searchRequests)
            {
                if (is_array($searchRequests))
                {
                    foreach ($searchRequests as $k => $val) {
                        if ($k === 0) { # What is it? the query without specifying the field ? NOT!
                            $requests['search']['texts'][$k] = $val;
                        }
                        # Elements with field number in key. $val is search text
                        if (Str::isInteger($k)) {
                            $requests['search']['texts'][$k] = $val;
                        # KLUDGE: Request by column name of a table   2016-07-10
                        } elseif ($isArbitraryRequestId && !in_array($k, ['fields-of-words','highlight','patterns','replacements','fields','texts','words','what','where'])) { 
                            $requests['search']['texts'][$k] = $val;
                        } else {
                            if (is_array($val)) { # multidimensional elements, for instance: $searchRequests[patterns][6][0] => /(^|\W)(blabla)/iu
                                if (empty($requests['search'][$k]))
                                    $requests['search'][$k] = $val;
                                else
                                    $requests['search'][$k] = Arr::mergeByKey($requests['search'][$k], $val);
                            } else {
                                $requests['search'][$k] = $val;
                                # Examples:
                                # $searchRequests[where] => 'beginnings'
                                # $searchRequests[highlight] => 1
                                # $searchRequests[fields] => 1
                            }
                        }
                    }
                } else
                    $requests['search']['texts'] = $searchRequests;
            }
            #
            if ($pickRequests)
                $requests['pick'] = $pickRequests;
            #
            if ($partRequests)
                $requests['part'] = $partRequests;
            # RETURN
            if ($requests) {
                if (is_array(self::$requests[$regularId]))
                    self::$requests[$regularId] = Arr::mergeByKey(self::$requests[$regularId], $requests);
                else
                    self::$requests[$regularId] = $requests;
            }
        }
    }


    /**
     * Build an outer url query to the block from system request
     *
     * @param array $request Array without block key
     * @return string URL query without block param 
     */
    public static function convertToQuery($request)
    {
        if ($request) {
            foreach ($request as $filter => $rqst) {
                switch ($filter) {
                    case 'part':
                        $queries[$filter] = $rqst['current']; break;
                    case 'search':
                        $queries[$filter] = $rqst[$filter]['texts']; break;
                    case 'sort': # The order should be specified in the array
                        $sorts[$filter] = $rqst; break;
                    default:
                        $queries[$filter] = $rqst;
                }
            }
            Arr::orderByKey($queries);
            $query = '';
            if ($query1 = Url::arrayToQuery($queries)) #497436375
                $query = $query1;
            if ($sorts)
                if ($query2 = Url::arrayToQuery($sorts)) #497436375
                    $query .= $query2;
            return $query;         
        }
    }





    
   /**
    *
    * @param array $blockInfo Array of ['id'=>..., 'src-block-id'=>..., 'tpl'=>...]
    * @param array $tdd
    * @param string $xprefix
    * @return array
    *
    * @todo 
    *   Build parameterized (prepared) sql-statements and correspondening values (global array?) within Request::getTableSql(), countNumOfParts(), fetchSingleRow()
    *   For column-names: use a white-list of column-names and controlled string interpolation.
    * @todo Move getTab() to Request class. Request::getTab(). Request::getTable()      Request::fetch($blockInfo) || Request::fetchTable() || Request::execute() ||        fetch=row
    * @todo Change params Request::getTab($blockInfo, $tdd, $xprefix)
    * @todo Request::get(...,'backward') fell out of the general style of other queries. It is converted to the variable $backward and then gets inserted into all functions. Meat this problem in in sort-update.php. Generally backward does not affect the data retrieve  - it  just re-sort records
    *
    */
    public static function getTab($blockInfo, $tdd, $xprefix=null)
    {
        if ($srcBlockId = $blockInfo['src-block-id']) {
            $tbl = Blox::getTbl($blockInfo['tpl'], $xprefix);
            $tab = self::getTable($tbl, $blockInfo['id'], $tbl.'.`rec-id`', 'AND '.$tbl.'.`block-id`='.(int)$srcBlockId, $tdd, $srcBlockId, $xprefix);
            return $tab;
        } else
            return false;
    }





    /**
     *
     * @param string $recIdColumn For "single" request
     * @param int $srcBlockId For "single" select type. If not set - will be calculated
     *
     * @todo
     *     
     * @example 
     *     Simple usage:  Request::getTable($tbl);
     *     For any table: Request::getTable($tbl, $sampleName, $recordIdColumn, $xSql);
     */    
    public static function getTable($tbl, $regularId=null, $recIdColumn=null, $xSql=null, $tdd=null, $srcBlockId=null, $xprefix=null)
    {
        if (isset($tdd) || isset($srcBlockId))
            $isTab = true;
        # Sort
        $tbl = Sql::sanitizeName($tbl); # Only for separate usage of self::getTable()
        if (!$xprefix)
        {
            # If is set the default sort in tdd, add it to Request::
            if (self::get($regularId,'sort') === null && $tdd['params']['sort'])
                foreach ($tdd['params']['sort'] as $k=>$v) 
                    if (Str::isInteger($k) && $v) # Digits                
                        self::add([$regularId=>['sort'=>[$k=>$v]]]);
                  
            # backward
            if ($tdd['params']['backward'])
                self::add([$regularId=>['backward'=>true]]);
            # unbackward    
            if (self::get($regularId,'sort'))
                self::add([$regularId=>['backward'=>false]]); # Don't unset backward at search request (for faq.tpl at esperto.su)
            # limit
            if (!isEmpty($tdd['params']['part']['limit']) && isEmpty(self::get($regularId,'part','limit'))) # Use limit=0
                self::add([$regularId=>['part'=>['limit'=>$tdd['params']['part']['limit']]]]);
            # hidingCol
            $whereSqls = [];
            if ($isTab) {
                Permission::addBlockPermits($srcBlockId); 
                # hideRecord
                if (isset($tdd[$xprefix.'params']['hiding-field'])) {
                    $hidingField = (int)$tdd[$xprefix.'params']['hiding-field'];
                    if ($hidingField && !Permission::ask('record', $srcBlockId)['']['edit']) { //get
                        $hidingCol = $tbl.'.dat'.$hidingField;
                        $whereSqls[] = "($hidingCol = 0 OR $hidingCol IS NULL)";
                    }
                }   
                
            }
            
            /**
             * Build Request::get($regularId,'search')
             * Prepare variables: what, where, words, texts. Update: patterns, replacements.
             * In the original array Request::get($regularId,'search','texts') loop is carried by fields. To build sql query in Request::getTab() prepare an array that loops by search words and then by fields
             */ 
            $formatSearchRequest = function($regularId, $tddSearchParams)
            {
                if ($searchTexts = self::get($regularId,'search','texts'))
                {
                    self::remove($regularId, 'search','texts');
                    /**
                     * "what"
                     *     word (default) "word" characters, i.e. letters, digits and underscores
                     *
                     *     Reserved:
                     *     regexp
                     *     digits
                     *     numbers
                     *     letters	
                     *     any	(UTF8)
                     */
                    if ($_REQUEST['what'])
                        $what = $_REQUEST['what'];
                    elseif ($tddSearchParams['what'])
                        $what = $tddSearchParams['what'];
                    else
                        $what = 'word';
                    self::add([$regularId=>['search'=>['what'=>$what]]]);
                    /**
                     * "where"
                     *     beginnings (default) In the beginnings of words
                     *     start	From start of the entire search text
                     *     anywhere
                     */
                    if ($_REQUEST['where'])
                        $where = $_REQUEST['where'];
                    elseif ($tddSearchParams['where'])
                        $where = $tddSearchParams['where'];
                    else
                        $where = 'beginnings';
                    self::add([$regularId=>['search'=>['where'=>$where]]]);
                    
                    # highlight 
                    if ($_REQUEST['highlight'])
                        $highlight = $_REQUEST['highlight'];
                    elseif ($tddSearchParams['highlight'])
                        $highlight = $tddSearchParams['highlight'];
                    #
                    if ($highlight)
                        self::add([$regularId=>['search'=>['highlight'=>true]]]);
                    # searchFields
                    if ($_REQUEST['fields'])
                        $searchFields = explode(',', $_REQUEST['fields']);
                    elseif ($tddSearchParams['fields'])
                        $searchFields = $tddSearchParams['fields'];
                    #
                    if ($searchFields)
                        self::add([$regularId=>['search'=>['fields'=>true]]]);
                    # stripSearchText
                    if ($what == 'word' && $where != 'start') {
                    	$stripSearchText = function($text) {
                            $text = trim($text);
                            $text = Text::stripTags($text);
                            $text = preg_replace('~(\w)-~u', '$1 ', $text); # Replace hyphens except minus in the beginning of words (minus words)
                            $text = preg_replace('~-$~u', '', $text); # Remove minus in the end
                            //$text = preg_replace('~\W+[-]+~u', ' ', $text); # Replace not markup minus
                            $text = preg_replace('~[^\w-]+~u', ' ', $text); # Replace all characters except letters, numbers, underscores, and minus (hyphen). TODO This replaces "№" too!
                            $text = preg_replace('~\s+~u', ' ', $text);  # Do single spaces

                            return $text;
                        }; 
                        if (is_array($searchTexts)) {
                            foreach ($searchTexts as $field => $v)
                                $searchTexts[$field] = $stripSearchText($v);
                        } else 
                            $searchTexts = $stripSearchText($searchTexts);
                    }
                    # The formation of standard array $searchTexts
                    $searchTexts = (function($searchTexts, $searchFields=null)
                    {
                        if (empty($searchTexts))
                            return;
                        $mergedSearchText = '';
                        if ($searchFields) {
                            # All fields have the same search text. If you set this array, you can use the input form of any specified fields.
                            if (is_array($searchTexts)) {
                                foreach ($searchTexts as $field => $aa) {
                                    if (in_array($field, $searchFields) && $aa)
                                        $mergedSearchText .= " $aa"; # If words were entered in several fields, all search text will be united.
                                    else
                                        Blox::prompt(sprintf(Blox::getTerms('request-not-to-search-fields'), $field), true); 
                                }
                                $mergedSearchText = substr($mergedSearchText, 1); 
                            } else # Request with no index (search=...)
                                $mergedSearchText = $searchTexts;
                            foreach ($searchFields as $searchField)
                                $searchTexts2[$searchField] = $mergedSearchText;
                                # KLUDGE: All fields have the same search text. Optimize it
                        } else {
                            if (is_array($searchTexts))
                                $searchTexts2 = $searchTexts;
                            else
                                Blox::prompt(Blox::getTerms('no-search-fields'), true);
                        }
                        return $searchTexts2;
                    })($searchTexts, $searchFields); # Each field now has a search text
                    //$searchTexts = $reduceSearchTexts($searchTexts, $searchFields); 
                    #
                    $patterns = [];
                    $replacements = [];
                    # search atTextBeginning
                    if ($where == 'start') {
                        self::add([$regularId=>['search'=>['texts'=>$searchTexts]]]);
                        foreach ($searchTexts as $field => $searchText) {
                            if ($searchText) {
                                # Do not highlight if there are tags
                                if (mb_strpos($searchText, '<') === false) {
                                    $patterns[$field][] = '~^('.$searchText.')~iu';
                                    $replacements[$field][] = '<span class="blox-searchword">$1</span>';
                                }
                                /*
                                $forbidden = false;
                                # Do not highlight these forbidden words. i.e. highlighting tag and its attributes
                                foreach (['span','class','blox','searchword'] as $forbiddenWord) {
                                    if (mb_strpos($forbiddenWord, $searchText) !== false) {
                                        $forbidden = true;
                                        break;
                                    }
                                }
                                if (!$forbidden) {
                                    $patterns[$field][] = "~^($searchText)~iu";
                                    $replacements[$field][] = '<span class="blox-searchword">$1</span>';
                                }
                                */
                            }
                        }
                        if ($patterns) {
                            self::add([$regularId=>['search'=>['patterns'=>$patterns]]]);
                            self::add([$regularId=>['search'=>['replacements'=>$replacements]]]);
                        }
                    }
                    # search anywhere including "beginnings" of words
                    else {
                        foreach ($searchTexts as $field => $searchText) # Each field now has a search text
                        {
                            if ($searchText) {   
                                $words = [];
                                $words = explode(' ', $searchText);
                                $words = array_unique($words); # In $mergedSearchText were combined words from different fields, and could be duplicates
                                $freshSearchText = '';
                                # the table of correspondence word--field (with Id)
                                foreach ($words as $word){
                                    if (!$word)
                                        continue;
                                    $cc = ['field'=> $field];
                                    if ('-' == substr($word, 0, 1)) { # minus-word
                                        $cc['word'] = substr($word, 1);  # remove initial '-'
                                        $cc['minus'] = true;
                                    } else {
                                        $cc['word'] = $word;
                                        /**/
                                        if (($where != 'start')) {
                                            # Do not highlight if there are tags
                                            if (mb_strpos($word, '<') === false) {
                                                if ($where == 'beginnings') {
                                                    $patterns[$field][] = '~(^|\W)('.$word.')~iu';
                                                    $replacements[$field][] = '$1<span class="blox-searchword">$2</span>';
                                                } else {
                                                    $patterns[$field][] = '~('.$word.')~iu';
                                                    $replacements[$field][] = '<span class="blox-searchword">$1</span>'; 
                                                }
                                            }
                                            /*  
                                            $forbidden = false;
                                            # Do not highlight these forbidden words (highlight tag and its attributes)
                                            foreach (['span','class','blox','searchword'] as $forbiddenWord) {
                                                if ($where == 'beginnings') {  
                                                    if (mb_strpos($forbiddenWord, $word) === 0) {
                                                        $forbidden = true;
                                                        break;
                                                    }
                                                } else {
                                                    if (mb_strpos($forbiddenWord, $word) !== false) {
                                                        $forbidden = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$forbidden) {
                                                if ($where == 'beginnings') {
                                                    $patterns[$field][] = "~(^|\W)($word)~iu";
                                                    $replacements[$field][] = '$1<span class="blox-searchword">$2</span>';
                                                } else {
                                                    $patterns[$field][] = "~($word)~iu";
                                                    $replacements[$field][] = '<span class="blox-searchword">$1</span>'; 
                                                }
                                            }
                                            */
                                        }
                                    }
                                    $words2[] = $cc['word'];
                                    $freshSearchText .= ' '.$word;
                                    $tab_word_field_minus[] = $cc;
                                }
                                self::add([$regularId=>['search'=>['words'=>[$field=>$words]]]]); # To highlight search words when displaying in Blox::getBlockHtm()
                                if (empty($allWords))
                                    $allWords = $words2;
                                else
                                    $allWords = array_merge($allWords, $words2);
                                #
                                if ($freshSearchText)
                                    self::add([$regularId=>['search'=>['texts'=>[$field=>substr($freshSearchText, 1)]]]]);
                            }
                        }
                        #
                        if ($patterns) {
                            self::add([$regularId=>['search'=>['patterns'=>$patterns]]]);
                            self::add([$regularId=>['search'=>['replacements'=>$replacements]]]);
                        }
                        #
                        if (is_array($allWords)) {
                            $allWords = array_unique($allWords);
                            $allWords = array_merge($allWords); # arrange id # id=>word
                            foreach ($tab_word_field_minus as $aa) {
                                $wordKey = array_search($aa['word'], $allWords);
                                $bb[$wordKey]['word'] = $aa['word'];
                                if ($aa['minus'])
                                    $bb[$wordKey]['minus'] = true;
                                $bb[$wordKey]['fields'][] = $aa['field'];
                            }
                            self::add([$regularId=>['search'=>['fields-of-words'=>$bb]]]);
                        }
                    }   
                }
            };
            $formatSearchRequest($regularId, $tdd[$xprefix.'params']['search']);
        }
        #
        $selectDataParams = self::getSelectDataParams($tdd[$xprefix.'types'], $regularId, $srcBlockId);
        $selectFromSqls = self::getSelectFromSqls($tbl, $tdd[$xprefix.'types'], $selectDataParams, $isTab);
        if ($datetimeTransformations = Blox::info('site', 'date-time-formats'))
            $typesDetails_datetime  = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['date','datetime','time'], 'only-name');
        $typesDetails_spec = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['block'], 'only-name'); # ['block','selects'] KLUDGE: We do not search 'selects' because of sql substitutions
        # Single Rec 
        if (self::get($regularId,'single') && !$xprefix)    # Extradata needs no filtering
        {   
            if ($tdd['params']['single']['no-adjacents']) {
                $singleDat = self::getCurrentSingleRow($regularId, $tbl, $whereSqls, $xSql, $isTab, $tdd[$xprefix.'params'], $selectFromSqls, $selectDataParams, $recIdColumn);
                $tab[0] = $singleDat;
            }
            # With no-adjacents
            else {
                /*
                TODO: Make getAdjacentsTab() with few adjacent recs.
                    IN (positionjj-3, positionjj-2, ..., positionjj+2, positionjj+3)
                    $tab[-3] 
                    $tab[-2] 
                    $tab[-1] = $tab['prev'] alias
                    $tab[0] 
                    $tab[1] = $tab['next']
                    $tab[2] 
                    $tab[3]
                */
                $getAdjacentsTab = function($regularId, $tbl, $whereSqls, $xSql, $isTab, $tddParams, $selectFromSqls, $selectDataParams, $recIdColumn)
                {   
                    if ($baseSql = self::getTableSql($regularId, $tbl, $whereSqls, $xSql, $isTab, $tddParams, $selectFromSqls, $selectDataParams))
                    {       
                        $singleId = (int)self::get($regularId,'single');
                        $baseSqljj = preg_replace('~(^SELECT.*?)(FROM)~u', '$1, @jj:=@jj+1 AS positionjj $2', $baseSql);
                        $baseSqlii = preg_replace('~(^SELECT).*?(FROM)~u', '$1 '.$tbl.'.`rec-id` AS recId2, @ii:=@ii+1 AS positionii $2', $baseSql);
                        # You may use *ii instead of @jj, tablejj, positionjj 
                        $sql = '';
                        #$sql .=  'SET @ii = 0, @jj = 0;';
                        $sql .= ' SELECT *'; # otherwise use AS for all cols
                        $sql .= ' FROM ('.$baseSqljj.') AS tablejj';
                        $sql .= ' WHERE (';
                        $sql .=     ' SELECT positionii';
                        $sql .=     ' FROM ('.$baseSqlii.') AS tableii';
                        $sql .=     ' WHERE recId2='.Sql::parameterize($singleId);
                        $sql .= ' )';
                        $sql .= ' IN (positionjj-1, positionjj, positionjj+1);';
                        Sql::query('SET @ii = 0, @jj = 0'); # KLUDGE. global. Do not work within next sql.
                        # SIMILAR: in fetchSingleRow()
                        if ($result = Sql::query($sql)) { # Sql::parameterize() applied!
                            $currRowFound = false;                    
                            $fetchFunc = ($isTab) ? 'fetch_row' : 'fetch_assoc';
                            while ($row = $result->$fetchFunc()) 
                            {
                                # Replace initial key '0' with associative key 'rec' and put it at the beginning of the array
                                if ($fetchFunc == 'fetch_row') {
                                    array_pop($row); # remove dat associated with positionjj 
                                    $blockId = array_pop($row);
                                    $sortNum = array_pop($row);
                                    # Replace initial key '0' with assoc. 'rec' and put it in the beginning
                                    $row = ['rec'=>$row[0]] + $row;
                                    unset($row[0]);
                                    # Create $dat['selects'][] and remove additional data associated with selects from row 
                                    if ($selectDataParams) {
                                        $selectKeys = array_keys($selectDataParams);
                                        rsort($selectKeys);
                                        foreach ($selectKeys as $field)
                                            $aa[$field] = array_pop($row);
                                        ksort($aa);
                                        $row['selects'] = $aa;
                                    }
                                    $row['sort'] = $sortNum;
                                    $row['block'] = $blockId;
                                }
                                if (self::get($regularId, 'search','highlight'))
                                    $row = self::highlightSearchWords($regularId, $row);
                                $backward = self::get($regularId,'backward');
                                
                                if ($row['rec'] == $singleId) {
                                    $tab[0] = $row;
                                    $currRowFound = true;
                                } elseif (!$currRowFound) {
                                    if ($backward)
                                        $tab['next'] = $row;
                                    else
                                        $tab['prev'] = $row;
                                } elseif ($currRowFound) {
                                    if ($backward)
                                        $tab['prev'] = $row;
                                    else
                                        $tab['next'] = $row;
                                } else
                                    Blox::error('Error in $getAdjacentsTab()');
                            }
                            $result->free();
                            return $tab;
                        }
                    }
                    /* Lesson for this function. Selecting arbitrarily ordered rows before and after a specific id
                        SET @j = 0;
                        SET @i = 0;
                        SELECT *
                        FROM ( 
                            SELECT id, col1, col2, ..., @j:=@j+1 AS pos
                        	FROM `table`
                            WHERE  col1=... ORDER BY col1 DESC, col2 ASC
                        ) AS zz
                        WHERE (    
                            SELECT position
                            FROM ( 
                                SELECT id AS id2, @i:=@i+1 AS position
                            	FROM `table`
                                WHERE  col1=... ORDER BY col1 DESC, col2 ASC
                            ) AS zz
                            WHERE id2=$currId
                        )
                        IN (pos-1, pos, pos+1)
                    */
                };
                $tab = $getAdjacentsTab($regularId, $tbl, $whereSqls, $xSql, $isTab, $tdd[$xprefix.'params'], $selectFromSqls, $selectDataParams, $recIdColumn);
                # This patch is done in purpose of insurance, because current request to the rec is most reliable way to retrieve single rec. In the future (if there will be no prompts) remove this patch
                if ($singleDat = self::getCurrentSingleRow($regularId, $tbl, $whereSqls, $xSql, $isTab, $tdd[$xprefix.'params'], $selectFromSqls, $selectDataParams, $recIdColumn)) {
                    if ($singleDat['rec'] != $tab[0]['rec']) { # This means that some requests to the block were lost.
                        # For fault tolerance
                        $singleId = self::get($regularId,'single');
                        if ($singleId == $singleDat['rec'])
                            $tab[0] = $singleDat; 
                        elseif ($singleId == $tab[0]['rec'])
                            ;
                        elseif (Blox::info('user','user-is-admin'))
                            Blox::prompt(sprintf(Blox::getTerms('single-rec-error'), $tbl), true); 
                        
                        if (Blox::info('user','user-is-admin'))
                            Blox::prompt(sprintf(Blox::getTerms('put-all-requests'), '<b>'.$regularId.'('.preg_replace(['~^'.Blox::info('db','prefix').'$~u', '~\$~u'], ['','/'], $tbl).')</b>'), true);
                    } else
                        $tab[0] = $singleDat;
                }
            }
            
            self::genPartNumOfSingle($regularId, $srcBlockId, $tbl, $whereSqls, $xSql, $isTab, $tdd[$xprefix.'params'], $selectFromSqls, $selectDataParams, $recIdColumn);
            # SIMILAR: Convert date-time format 
            if ($typesDetails_datetime) {
                foreach($typesDetails_datetime as $field => $aa) {
                    foreach([0, 'next', 'prev'] as $bb) {
                        if ($tab[$bb][$field]) {
                            if ($format = $datetimeTransformations[$aa['name']]) {
                                $s = strtotime($tab[$bb][$field]); #integer # '0000-00-00' is denied. It produces negative strtotime()
                                $tab[$bb][$field] = ($s < 0) ? '' : date($format, $s);
                            }
                        }
                    }
                }
            }
            # $dat['blocks'], $dat['selects']
            if ($typesDetails_spec) {
                foreach($typesDetails_spec as $field => $aa)
                    foreach([0, 'next', 'prev'] as $bb)
                        if ($tab[$bb][$field])
                            $tab[$bb][$aa['name'].'s'][$field] = $tab[$bb][$field];
            }
        }
        # Multi Rec 
        else
        {
            ############## part ##############
            $GLOBALS['Blox']['limit-is-increased'] = false;
            
            $sql = self::getTableSql($regularId, $tbl, $whereSqls, $xSql, $isTab, $tdd[$xprefix.'params'], $selectFromSqls, $selectDataParams, $xprefix); # , returns $GLOBALS['Blox']['limit-is-increased']
            if (empty($sql))
                return false;

            ### NOT DEPRECATED:
            ############# For Borrowed Recs (redistribution = -1) ############
            if (!isEmpty(self::get($regularId,'part','limit')) && self::get($regularId,'backward') && $tdd[$xprefix.'params']['part']['redistribution'] == -1 && !$xprefix)
            {
                # part we entered 
                if (!self::get($regularId,'part','priorPart')) {
                    if (self::get($regularId,'part','current') == self::get($regularId,'part','num-of-parts'))
                        self::add([$regularId=>['part'=>['priorPart'=>'last']]]);
                    elseif (self::get($regularId,'part','current') == (self::get($regularId,'part','num-of-parts') - 1))
                        self::add([$regularId=>['part'=>['priorPart'=>'nextToLast']]]);
                }

                # This is the last part. The penultimate part is visited
                if (self::get($regularId,'part','current') == self::get($regularId,'part','num-of-parts')) {
                    $firstNotComplementaryRow = self::get($regularId,'part','num-of-parts')*self::get($regularId,'part','limit') - self::get($regularId,'part','num-of-recs');
                    if (self::get($regularId,'part','priorPart')=='nextToLast') {
                        $firstShownRow = 0;
                        $firstNotShownRow = $firstNotComplementaryRow; 
                    }
                }
                # This is the penultimate part, but we went from the last part
                elseif (self::get($regularId,'part','current') == (self::get($regularId,'part','num-of-parts') - 1) && self::get($regularId,'part','priorPart')=='last') {
                    $firstShownRow = self::get($regularId,'part','limit') - (self::get($regularId,'part','num-of-parts')*self::get($regularId,'part','limit') - self::get($regularId,'part','num-of-recs'));
                    $firstNotShownRow = self::get($regularId,'part','limit');
                }
            }
            #/ part

            $result = Sql::query($sql);
            if ($result)
            {
                $i = 0;
                if ($isTab)
                {
                    while ($row = $result->fetch_row())
                    {
                        $blockId = array_pop($row);
                        $sortNum = array_pop($row);
                        # Replace initial key '0' with assoc. 'rec' and put it in the beginning
                        $row = ['rec'=>$row[0]] + $row;
                        unset($row[0]);
                        # Create $dat['selects'][] and remove additional data from row
                        if ($selectDataParams) {
                            $selectKeys = array_keys($selectDataParams);
                            rsort($selectKeys);
                            foreach ($selectKeys as $field)
                                $aa[$field] = array_pop($row);
                            ksort($aa);
                            $row['selects'] = $aa;
                        }
                        $row['sort'] = $sortNum;
                        $row['block'] = $blockId;

                        # SIMILAR: Convert date-time format 
                        if ($typesDetails_datetime) {
                            foreach($typesDetails_datetime as $field => $aa) {
                                if ($row[$field]) {
                                    if ($format = $datetimeTransformations[$aa['name']]) {
                                        $s = strtotime($row[$field]); #integer # '0000-00-00' is denied. It produces negative strtotime()
                                        $row[$field] = ($s < 0) ? '' : date($format, $s);
                                    }   
                                }
                            }
                        }
                        if (self::get($regularId, 'search','highlight'))
                            $row = self::highlightSearchWords($regularId, $row);
                        
                        ############# For Shown Recs ############   :DEPRECATED:
                        if ($i < $firstNotComplementaryRow)
                            $row['complementary'] = true;
                        if ($i >= $firstShownRow && $i < $firstNotShownRow)
                            $row['shown'] = true;
                        ##############################################

                        # $dat['blocks'], $dat['selects']
                        if ($typesDetails_spec) {
                            foreach($typesDetails_spec as $field => $aa)
                                if ($row[$field])
                                    $row[$aa['name'].'s'][$field] = $row[$field];
                        }
                        # KLUDGE: Move this up, But then $row will need as an arg.
                        if ($d = $tdd[$xprefix.'fields']['none']) {
                            foreach ($d as $field) {
                                unset($row[$field]);   
                            }
                        }
                        $tab[] = $row;
                        $i++;
                    }
                } else {
                    while ($row = $result->fetch_assoc()) {
                        if (self::get($regularId, 'search','highlight'))
                            $row = self::highlightSearchWords($regularId, $row);
                        
                        ############# For Shown Recs ############
                        if ($i < $firstNotComplementaryRow)
                            $row['complementary'] = true;
                        
                        if ($firstNotShownRow && $i >= $firstShownRow && $i < $firstNotShownRow)
                            $row['shown'] = true;
                        ##############################################
                        $tab[] = $row;
                        $i++;
                    }
                }
                $result->free();
            }
            
            if (!$xprefix) {
                if (self::get($regularId,'backward'))
                    $tab = array_reverse($tab);
                elseif ($GLOBALS['Blox']['limit-is-increased']) {
                    end($tab);
                    $maxKey = key($tab);
                    if ($maxKey >= self::get($regularId,'part','limit')) {
                        array_pop($tab); # Remove last element
                        self::countNumOfParts($regularId, $tbl, self::get($regularId,'part','limit'), $selectFromSqls['count'], $tdd[$xprefix.'params']);
                        self::add([$regularId=>['part'=>['current'=>1]]]);
                    }
                }
            }
        }#  end of "multirec"
//if (375==$regularId)
//qq($tab);
        return $tab;
    }

////////////////////////////////////////////////////////


    /**
     * Trick: To get only conditions "WHERE ...", do $selectFromSqls = []
     * To do WHERE query do not use $xSql, but use $whereSqls
     */
    public static function getTableSql($regularId, $tbl, $whereSqls, $xSql, $isTab, $tddParams=null, $selectFromSqls, $selectDataParams=null, $xprefix=null)
    {
        if (!$xprefix) {
            # search request (LIKE)
            if (self::get($regularId,'search')) {
                if ($aa = self::getSearchSqls($regularId, $tbl, $tddParams, $isTab, $selectDataParams))
                    $whereSqls = ($whereSqls) ? array_merge($whereSqls, $aa) : $aa;
            }
            # pick request (AND)
            if (self::get($regularId,'pick')) {
                if ($aa = self::getPickSqls($regularId, $tbl, $tddParams, $isTab, $selectDataParams))
                    $whereSqls = ($whereSqls) ? array_merge($whereSqls, $aa) : $aa;
            }
            # char (AND IN())
            if ($_GET['block']==$regularId && $_GET['char'] && $_GET['charblock'] && $_GET['charfield']) {
                if ($aa = self::getCharSqls($tbl, $tddParams))
                    $whereSqls = ($whereSqls) ? array_merge($whereSqls, $aa) : $aa;
            }
            /** #SETS POSTPONED 2017-08-11
            # Sets of request (OR)
            if ($set = self::get($regularId,'sets')) {
                foreach ($set as $type=>$elements) # one loop
                    foreach ($elements as $i=>$element) { # Logical members
                        foreach ($element as $filter=>$v) { # one loop
                            if ('pick'===$filter) {
                                foreach ($v as $field=>$v2) # one loop
            }
            */
        }

        # KLUDGE: whether it is necessary if $xprefix
        $GLOBALS['Blox']['where-and-extra-sql'] = self::getWhereAndExtraSql($whereSqls, $xSql);# Used in self::countNumOfParts()
        $sql = $selectFromSqls['columns'].' '.$GLOBALS['Blox']['where-and-extra-sql'];        
        if (!$xprefix) {
            # sort request (ORDER BY) ###
            $sql .= self::getSortSql($regularId, $tbl, $tddParams, $isTab, $selectDataParams);
            # part request (LIMIT)
            if (!isEmpty(self::get($regularId,'part','limit')) && !self::get($regularId,'single')) # for savings: !self::get($regularId,'single')
                $sql .= self::getPartSql($regularId, $tbl, $tddParams, $selectFromSqls);
        }
//if (645==$regularId)
//qq($sql);
        return $sql;
    }



                
                
                
    private static function countNumOfParts($regularId, $tbl, $limit, $countSql, $tddParams=null, $whereAndExtraSql=null, &$remainder=null)
    {
        $remainder = 0;
        if (empty($whereAndExtraSql))
            $whereAndExtraSql = $GLOBALS['Blox']['where-and-extra-sql'];
        $sql = $countSql.' '.$whereAndExtraSql; # Use the main query without the ORDER and LIMIT to count records.
        if ($result = Sql::query($sql))
        {
            if ($numOfRows = $result->fetch_row()[0])        
            {
                $result->free();
                self::add([$regularId=>['part'=>['num-of-recs'=>$numOfRows]]]);
                self::add([$regularId=>['part'=>['num-of-parts'=>Math::divideIntegers($numOfRows, $limit, $remainder, true)]]]);

                # Parts evaluation 
                if (self::get($regularId,'part','limit')) {# Need?
                    if (!self::get($regularId,'single')) { # Need?
                        if (self::get($regularId,'part','num-of-parts'))
                        {# Need?
                            if (self::get($regularId,'backward') && $tddParams['part']['numbering'] == 'desc') {
                                $aa = self::get($regularId,'part','num-of-parts');                        
                                # TODO: You could reduce numOfParts just here (['num-of-parts']--), but while this is done in self::getTableSql()
                                # And while we consider [numOfParts'] without taking into account redistribution
                                if ($tddParams['part']['redistribution'] > 0 && $remainder)# Divided with remainder
                                    $aa--; # As there is no last part now
                                for ($i=$aa; $i >= 1 ; $i--)
                                   $parts[] = $i;
                            } else {
                                for ($i=1; $i <= self::get($regularId,'part','num-of-parts'); $i++)
                                   $parts[] = $i;
                            }
                            self::add([$regularId=>['part'=>['parts'=>$parts]]]);
                            # prev-next parts
                            $currPartKey = array_search(self::get($regularId,'part','current'), $parts);
                            $aa = $currPartKey - 1;
                            self::add([$regularId=>['part'=>['prev'=>$parts[$aa]]]]);
                            $aa = $currPartKey + 1;
                            self::add([$regularId=>['part'=>['next'=>$parts[$aa]]]]);
                        }
                    }
                }                
                return true;
            } else {
                $result->free();
                return false;
            }
        } else
            return false;
    }





    private static function getWhereAndExtraSql($whereSqls, $xSql)
    {
        # There is a conditional request
        if ($whereSqls) {
            foreach ($whereSqls as $filter)
                if ($filter)
                    $whereSql .= " AND $filter";
            $whereSql = substr($whereSql, 4);  # remove initial ' AND '
        }

        if (empty($whereSql) && empty($xSql)) # No request
            return;
        else {
            $sql = "WHERE ";
            if (empty($xSql))
                $sql .= $whereSql;
            elseif (empty($whereSql)) # Only extra request
                $sql .= "1 $xSql"; # In xSql may be WHERE condition
            else # $whereSql && $xSql
                $sql .= "$whereSql $xSql";
            return $sql;
        }
    }




    public static function getSelectDataParams($tddTypes, $regularId, $srcBlockId)
    {
        
        if ($aa = Tdd::getTypesDetails($tddTypes, ['select'], 'only-name'))
        {
            $blockInfo = Blox::getBlockInfo($regularId);
            if (empty($srcBlockId))
                $srcBlockId = $blockInfo['src-block-id'];
            $typesDetailsS = []; 
            $selectListBlockIds = [];
            $selectListRecId = [];

            foreach ($aa as $field => $bb)
                $tddTypesS[$field] = $tddTypes[$field];
            $typesDetailsS = Tdd::getTypesDetails($tddTypesS, ['select']);

            # Reduce array for search
            foreach ($typesDetailsS as $field => $bb) {
                $selectParams = $typesDetailsS[$field]['params']; # Just for compactness
                if ($selectParams['template'][0] && $selectParams['edit'][0])
                {
                    # Check the tables, otherwise there will be sql error
                    $selectListTpl = Files::normalizeTpl($selectParams['template'][0], $blockInfo['tpl']);
                    $tbl = Blox::getTbl($selectListTpl);
                    if (Sql::tableExists($tbl)) { # NOTTESTED 2016-12-04
                        # extract data for select list
                        $selectDataParams[$field]['template']   = $selectListTpl;
                        $selectDataParams[$field]['edit']       = $selectParams['edit'][0];
                        $selectDataParams[$field]['output']     = $selectParams['output'][0];
                        $selectDataParams[$field]['pick']       = $selectParams['pick'][0];
                        $selectDataParams[$field]['search']     = $selectParams['search'][0];
                        $selectDataParams[$field]['sort']       = $selectParams['sort'][0];
                        # Independent list
                        if (empty($selectParams['parentfield'][0])) {
                            # TODO: It can be moved to getJoinList as for all. Although why calculate it every time
                            $sql = "SELECT `select-list-block-id` FROM ".Blox::info('db','prefix')."selectlistblocks WHERE `edit-block-id`=? AND `edit-field`=?";
                            if ($result = Sql::query($sql, [$srcBlockId, $field])) {
                                if ($row = $result->fetch_assoc()) { # It is also used separately in another place
                                    $result->free();
                                    $selectDataParams[$field]['block'] = $row['select-list-block-id']; 
                                } else {
                                    $result->free();
                                    return false; # NOTTESTED
                                }
                            } else
                                return false; # NOTTESTED
                        } else {
                            $selectDataParams[$field]['parentfield']    = $selectParams['parentfield'][0];
                            $selectDataParams[$field]['parenttemplate'] = $selectDataParams[$selectDataParams[$field]['parentfield']]['template'];
                            $selectDataParams[$field]['templateparentidfield']       = $selectParams['templateparentidfield'][0];
                        }
                    } else {
                        Blox::prompt(sprintf(Blox::getTerms('no-table'), $tbl) , true);
                    }
                }
            }
            return $selectDataParams;
        }
    }






    /**
     * @todo If there is no search or sort request to a field, do not "LEFT JOIN". Yet think about WHERE. But how to get `block-id` of dependent list (SELECT `select-list-block-id`...)?
     */  
    public static function getSelectFromSqls($tbl, $tddTypes, $selectDataParams, $isTab=false)
    {
        # Instead of $isTab you can use $tddTypes, but in the future mat be uset $types for non-standard data tables
        if ($isTab)
        {
            if ($tddTypes) {
                /** 
                 * @example
                 *  $sql = "SELECT $columnsList FROM $tbl $joinList";
                 */
                $columnsList = (function($tbl, $tddTypes, $selectDataParams)
                {
                    if ($tddTypes){
                        $columnsList = $tbl.'.`rec-id`';
                        $selectColumnsList = '';
                        $aa = array_keys($tddTypes);
                        $maxKey = max($aa);
                        for ($field = 1; $field <= $maxKey; $field++) {
                            if (isset($_GET['edit']))
                                $activeField = $selectDataParams[$field]['edit'];
                            else
                                $activeField = $selectDataParams[$field]['output'];
                            
                            if ($selectDataParams[$field]) {
                                if ($activeField != 'rec') {
                                    $stab2 = Blox::getTbl(Sql::sanitizeTpl($selectDataParams[$field]['template']), '', true); # quotes are removed for selfy selects
                                    $stabAlias = '`'.$stab2.'_alias`';
                                    $stab_dat = '`'.$stab2.'_dat'.(int)$activeField.'`';
                                    $columnsList .= ', '.$stabAlias.'.dat'.(int)$activeField.' AS '.$stab_dat;
                                } else
                                    $columnsList .= ', '.$tbl.'.dat'.$field;
                                $selectColumnsList .= ', '.$tbl.'.dat'.$field; # Additional fields - unsubstituted select type data
                            } else
                                $columnsList .= ', '.$tbl.'.dat'.$field;
                        }
                        return $columnsList.$selectColumnsList.', '.$tbl.'.sort, '.$tbl.'.`block-id`';
                    } else
                        Blox::error('No tdd types in $columnsList');
                })($tbl, $tddTypes, $selectDataParams);
                //$columnsList = $getColumnsList($tbl, $tddTypes, $selectDataParams);

                /** 
                 * @example
                 *  $joinList = getJoinList(...);
                 *  $sql = "SELECT $columnsList FROM $tbl $joinList";
                 */
                $getJoinList = function($tbl, $tddTypes, $selectDataParams)
                {
                    if ($tddTypes){
                        $joinList = '';
                        if ($selectDataParams){
                            $dep = Blox::info('db','prefix')."dependentselectlistblocks";
                            foreach ($selectDataParams as $field=>$sparams)
                            {
                                if (isset($_GET['edit']))
                                    $activeField = $sparams['edit'];
                                else
                                    $activeField = $sparams['output'];
                                
                                if ($sparams['template'])
                                {
                                    # If there is any substitution, add "LEFT JOIN"
                                    if ($activeField != 'rec' 
                                        || (self::get($regularId,'pick')     && $sparams['pick']     != 'rec')
                                        || (self::get($regularId,'search')   && $sparams['search']   != 'rec')
                                        || (self::get($regularId,'sort')     && $sparams['sort']     != 'rec')
                                    ){ 
                                        $stab2 = Blox::getTbl(Sql::sanitizeTpl($sparams['template']), '', true); # quotes are removed for selfy selects
                                        $stab = '`'.$stab2.'`';
                                        $stabAlias = '`'.$stab2.'_alias`';
                                        $blockIdSql = 0;
                                        # Dependent list. Get BlockId
                                        if ($sparams['parentfield']) {
                                            if ($selectDataParams[$field]['parenttemplate']) {
                                                $ptab = "`".Blox::getTbl(Sql::sanitizeTpl($selectDataParams[$field]['parenttemplate']))."`";
                                                # The parent list is independent
                                                if ($selectDataParams[$sparams['parentfield']]['block']) {
                                                    if ($sparams['templateparentidfield']) {
                                                        $blockIdSql = $blockIdSqls[$field] = "(SELECT `select-list-block-id` FROM $dep WHERE $dep.`parent-list-block-id`=".(int)$selectDataParams[$sparams['parentfield']]['block']." LIMIT 1)";
                                                    } else
                                                        $blockIdSql = $blockIdSqls[$field] = "(SELECT `select-list-block-id` FROM $dep WHERE $dep.`parent-list-block-id`=".(int)$selectDataParams[$sparams['parentfield']]['block']." AND $dep.`parent-list-rec-id`=$ptab.`rec-id`)";}
                                                # The parent list is dependent
                                                # It may be possible applying AS to calculate the parent block only once?
                                                elseif ($blockIdSqls[$sparams['parentfield']]) {
                                                    if ($sparams['templateparentidfield'])
                                                        $blockIdSql = $blockIdSqls[$field] = "(SELECT `select-list-block-id` FROM $dep WHERE $dep.`parent-list-block-id`=".$blockIdSqls[$sparams['parentfield']]." LIMIT 1)";
                                                    else
                                                        $blockIdSql = $blockIdSqls[$field] = "(SELECT `select-list-block-id` FROM $dep WHERE $dep.`parent-list-block-id`=".$blockIdSqls[$sparams['parentfield']]." AND $dep.`parent-list-rec-id`=$ptab.`rec-id`)";
                                                } else
                                                    Blox::error('Error 3574356 in getJoinList()');
                                            }
                                        } elseif ($sparams['block'])  # Independent list
                                            $blockIdSql = $sparams['block'];
                                            
                                        if ($blockIdSql)
                                            $joinList .= ' LEFT JOIN '.$stab.' AS '.$stabAlias.' ON '.$stabAlias.'.`rec-id`='.$tbl.'.dat'.(int)$field.' AND '.$stabAlias.'.`block-id`='.$blockIdSql; # Use alias because you may request to the same table, for exapmle if you use select data for parent rec in nav tpl
                                    }
                                }
                            }
                            return $joinList;
                        }
                    } else
                        Blox::error('No tdd types in getJoinList()');
                };
                $joinList = $getJoinList($tbl, $tddTypes, $selectDataParams);                
                $selectFromSqls['count']  = "SELECT COUNT($tbl.`rec-id`) FROM $tbl $joinList";
                $selectFromSqls['columns'] = "SELECT $columnsList FROM $tbl $joinList";
            } else
                Blox::error('No tdd types in self::getSelectFromSqls()');
        } else {
            $selectFromSqls['count']  = "SELECT COUNT(*) FROM $tbl";
            $selectFromSqls['columns'] = "SELECT * FROM $tbl";
        }
//qq($selectFromSqls);
    	return $selectFromSqls;
    }



    # Do not anonymous
    private static function highlightSearchWords($regularId, $dat)
    {
        if (self::get($regularId,'search','patterns'))
            foreach (self::get($regularId,'search','patterns') as $field => $patterns)
                if ($patterns)
                    $dat[$field] = @preg_replace($patterns, self::get($regularId,'search','replacements',$field), $dat[$field]);
        return $dat;
    }


    
    
    
    /**
     * Calculate Request::get($regularId,'part','current')
     * part request does not affect
     * Retrieve all records by "count"  from the beginning to the current record and count
     *
     * @todo Do anonymously or in class
     */
    private static function genPartNumOfSingle($regularId, $srcBlockId, $tbl, $whereSqls, $xSql, $isTab, $tddParams, $selectFromSqls, $selectDataParams, $recIdColumn)
    {  

        $limit = self::get($regularId,'part','limit');
        # search request (LIKE)
        if (self::get($regularId,'search')) {
            if ($aa =  self::getSearchSqls($regularId, $tbl, $tddParams, $isTab, $selectDataParams))
                $whereSqls = ($whereSqls) ? array_merge($whereSqls, $aa) : $aa;
        }
        # pick request (AND)
        if (self::get($regularId,'pick')) {
            if ($aa = self::getPickSqls($regularId, $tbl, $tddParams, $isTab, $selectDataParams))
                $whereSqls = ($whereSqls) ? array_merge($whereSqls, $aa) : $aa;
        }
        # Count the number of parts is just numOfParts without regard single and backward requests
        $whereAndExtraSql = self::getWhereAndExtraSql($whereSqls, $xSql);    
        self::countNumOfParts($regularId, $tbl, $limit, $selectFromSqls['count'], $tddParams, $whereAndExtraSql, $remainder); # If divided with remainder
        $numOfParts = self::get($regularId,'part','num-of-parts');
        $request_ini = self::get($regularId); # Save requests

        # Emulation. Count the number of records (numOfRecsToCurr) to the current record inclusively
        # Sort
        $whereRecIdSql = Sql::sanitizeName($recIdColumn).'='.(int)self::get($regularId,'single').' AND `block-id`='.(int)$srcBlockId;  
        if (self::get($regularId,'sort')) {   
            foreach (self::get($regularId,'sort') as $field => $order) {       
                $col = ($isTab) ? $tbl.'.dat'.(int)$field : $tbl.'.'.Sql::sanitizeName($field);
                if ($order == 'asc')
                    $sign = '<=';
                elseif ($order == 'desc')
                    $sign = '>=';
                $whereSqls[] = $col.$sign.'(SELECT '.$col.' FROM '.$tbl.' WHERE '.$whereRecIdSql.')';}
        }
        # Even if there is no sort request, sort by column "sort"
        elseif ($isTab) {
            if ($tddParams['part']['numbering'] == 'desc' && self::get($regularId,'backward')) {
                self::add([$regularId=>['sort'=>['sort'=>'desc']]]);
                $sign = '<=';
            } else {
                if (self::get($regularId,'backward')) {
                    self::add([$regularId=>['sort'=>['sort'=>'desc']]]);
                    $sign = '>=';
                } else                
                    $sign = '<=';
             }
            $col = $tbl.'.sort';
            $whereSqls[] = $col.$sign.'(SELECT '.$col.' FROM '.$tbl.' WHERE '.$whereRecIdSql.')';
        }
        $whereAndExtraSql = self::getWhereAndExtraSql($whereSqls, $xSql);
        
        if (self::countNumOfParts($regularId, $tbl, $limit, $selectFromSqls['count'], $tddParams, $whereAndExtraSql))
        {
            # If redistribution
            if (
                $remainder 
                && self::get($regularId,'backward') 
                && $tddParams['part']['numbering'] == 'desc' 
                && $tddParams['part']['redistribution'] > 0 
                && self::get($regularId,'part','limit') > 1
            ) {   
                $redistribution = $tddParams['part']['redistribution'];
                $numOfParts--;
                if ($redistribution > $numOfParts)
                    $redistribution = $numOfParts;
                $numOfRecsToCurr = self::get($regularId,'part','num-of-recs');
                # Count the number of records till the last undisturbed part inclusive (numOfRecs_undisturbed)
                $numOfParts_undisturbed = $numOfParts - $redistribution;
                $numOfRecs_undisturbed = $limit *  $numOfParts_undisturbed;
                if ($numOfRecsToCurr > $numOfRecs_undisturbed) { # the record falls into disturbed part
                    # Redistribute the last part and count how many records (numOfPartGuests) were added to each disturbed part
                    $numOfPartGuests = Math::divideIntegers($remainder, $redistribution);                        
                    $numOfRestRecs = $numOfRecsToCurr - $numOfRecs_undisturbed;
                    $limit2 = $limit + $numOfPartGuests;
                    for ($i = 1, $sum = 0 ;; ++$i) { # Faster than $i++
                        if ($i == $redistribution)
                            break;
                        if (($sum += $limit2) >= $numOfRestRecs)
                            break;
                    }
                    $part_current = $numOfParts_undisturbed + $i;
                } else # Falls to distributed
                    $part_current = Math::divideIntegers($numOfRecsToCurr, $limit, $aa, true); # complete            
            } else {
                $part_current = self::get($regularId,'part','num-of-parts'); # As calculated until the current record, numOfParts is the current
            }
        } else {
            # KLUDGE
            # When multiple sorts can be empty result of retrieving records to the current one.
            # We need a more sophisticated algorithm like $getAdjacentsTab()
            # Now just throw to the first part.
            if (self::get($regularId,'backward'))
                $part_current = $numOfParts; 
            else
                $part_current = 1; 
            # TODO
            #   MySQL: selecting arbitrarily ordered rows after a specific id		http://dba.stackexchange.com/questions/23981/mysql-selecting-arbitrarily-ordered-rows-after-a-specific-id	---http://dba.stackexchange.com/questions/23981/mysql-selecting-arbitrarily-ordered-rows-after-a-specific-id/23988
            #   Selecting all rows after a row with specific values without repeating the same subquery	http://stackoverflow.com/questions/7368672/selecting-all-rows-after-a-row-with-specific-values-without-repeating-the-same-s
        }
        
        # Restore requests
        self::add([$regularId=>$request_ini]);
        self::add([$regularId=>['part'=>['current'=>$part_current]]]);
        self::add([$regularId=>['part'=>['num-of-parts'=>$numOfParts]]]);
        self::remove($regularId, 'sort', 'sort'); # Need?
    }




    private static function getCurrentSingleRow($regularId, $tbl, $whereSqls, $xSql, $isTab, $tddParams, $selectFromSqls, $selectDataParams, $recIdColumn)
    {
        # part-request has affect, as there is a single-request
        $single = (int)self::get($regularId,'single');
        if (!single) # To request to tables
            $single = Sql::sanitizeName(self::get($regularId,'single'));
        $whereSqls[] = $recIdColumn.'='.$single;
        $sql = self::getTableSql($regularId, $tbl, $whereSqls, $xSql, $isTab, $tddParams, $selectFromSqls, $selectDataParams); ## $xprefix not for multi
        if (empty($sql))
            return false;
        $row = self::fetchSingleRow($regularId, $sql, $isTab, $tddParams, $selectDataParams);    
        return $row;
    }
    # Do not rename to getSingleRow()
    private static function fetchSingleRow($regularId, $sql, $isTab, $tddParams, $selectDataParams)
    {
        # SIMILAR: in getAdjacentsTab()
        if ($result = Sql::query($sql)) {
            $fetchFunc = ($isTab) ? 'fetch_row' : 'fetch_assoc';
            if ($row = $result->$fetchFunc()) {
                $result->free();
                # Replace initial key '0' with associative key 'rec' and put it at the beginning of the array
                if ('fetch_row' == $fetchFunc) {
                    $blockId = array_pop($row);
                    $sortNum = array_pop($row);
                    # Replace initial key '0' with assoc. 'rec' and put it in the beginning
                    $row = ['rec'=>$row[0]] + $row;
                    unset($row[0]);
                    # Create $dat['selects'][] and remove additional data from row
                    if ($selectDataParams) {
                        $selectKeys = array_keys($selectDataParams);
                        rsort($selectKeys);
                        foreach ($selectKeys as $field)
                            $aa[$field] = array_pop($row);
                        ksort($aa);
                        $row['selects'] = $aa;
                    }
                    $row['sort'] = $sortNum;
                    $row['block'] = $blockId;
                }
                if (self::get($regularId, 'search','highlight'))
                    $row = self::highlightSearchWords($regularId, $row);
                return $row;
            }
        }
    }



    /**
     */
    private static function getPickSqls($regularId, $tbl, $tddParams, $isTab, $selectDataParams)
    {
        # SIMILAR in recs-delete.php
        $signs = ['lt'=>'<', 'le'=>'<=', 'eq'=>'=', 'ge'=>'>=', 'gt'=>'>', 'ne'=>'!='];
        foreach (self::get($regularId,'pick') as $field => $aa) {
            if ($isTab) {
                if ($field == 'rec')
                    $col = $tbl.'.`rec-id`';
                else {
                    if ($selectDataParams[$field]) {
                        $stabAlias = '`'.Blox::getTbl($selectDataParams[$field]['template'], '', true).'_alias`';
                        if ($selectDataParams[$field]['pick'] == 'rec')
                            $col = $stabAlias.'.`rec-id`'; 
                        else
                            $col = $stabAlias.'.dat'.Sql::sanitizeInteger($selectDataParams[$field]['pick']); 
                    } else
                        $col = $tbl.'.dat'.(int)$field; 
                }
            } else {
                $col = $tbl.'.'.Sql::sanitizeName($field);
                Blox::prompt(Blox::getTerms('named-pick-requests'), true);
            }
            
            foreach ($aa as $k=>$val) {
                if ($k && $signs[$k]) {
                    $psql = $col.' '.$signs[$k].' '.Sql::parameterize($val);
                    if ($tddParams['pick']['case-sensitive']) # Case sensitive comparision
                        $psql .= ' COLLATE utf8_bin';
                    $whereSqls[] = $psql;
                }
            }
        }
        return $whereSqls;
    }


    private static function getCharSqls($tbl, $tddParams)
    {   
        $charBlockInfo = Blox::getBlockInfo($_GET['charblock']);
        $charTbl = Blox::getTbl($charBlockInfo['tpl']);
        $signs = ['lt'=>'<', 'le'=>'<=', 'eq'=>'=', 'ge'=>'>=', 'gt'=>'>', 'ne'=>'!=']; # SIMILAR to self::getPickSqls()
        foreach ($_GET['char'] as $field => $aa) {
            $col = $charTbl.'.dat'.(int)$field;
            foreach ($aa as $k=>$val) {
                if ($k && $signs[$k]) {
                    $insql.= ' AND '.$col.' '.$signs[$k].' '.Sql::parameterize($val);
                    if ($tddParams['pick']['case-sensitive']) # Case sensitive comparision
                        $insql.= ' COLLATE utf8_bin';
                }
            }
        }
        if ($insql) {
            $whereSqls[] = $tbl.'.dat'.$_GET['charfield'].' IN (SELECT '.$charTbl.'.`rec-id` FROM '.$charTbl.' WHERE 1 '.$insql.')';  # TODO JOIN ?
            return $whereSqls;
        }
    }
    

    public static function getSearchSqls($regularId, $tbl, $tddParams, $isTab, $selectDataParams=null)
    {
        # @return  " || LOWER(...)"
        $getFieldsSql = function($regularId, $tbl, $tddParams, $isTab, $selectDataParams, $field, $word, $minus=false) 
        {
            if ($isTab) {
                if ($field == 'rec')
                    $col = $tbl.'.`rec-id`';
                else{
                    if ($selectDataParams[$field]) {
                        $stabAlias = '`'.Blox::getTbl($selectDataParams[$field]['template'], '', true).'_alias`';
                        if ($selectDataParams[$field]['search'] == 'rec')
                            $col = $stabAlias.'.`rec-id`'; 
                        else
                            $col = $stabAlias.'.dat'.Sql::sanitizeInteger($selectDataParams[$field]['search']); 
                    } else
                        $col = $tbl.'.dat'.Sql::sanitizeInteger($field); 
                }
            } else
                $col = $tbl.'.'.Sql::sanitizeName($field);

            if (!$tddParams['search']['case-sensitive']) { # Case insensitive comparision
                $col = 'LOWER('.$col.')';
                $word = mb_strtolower($word);
            }
            $fsql .= ($minus) ? ' && '.$col.' NOT' : ' || '.$col;
            if (self::get($regularId,'search','where') == 'start') {
                $fsql .= ' LIKE '.Sql::parameterize($word.'%'); # The % symbols need to be inside the parameter value: "LIKE ?"
            } elseif (self::get($regularId,'search','where') == 'beginnings') {
                $word = preg_replace("/([.\[\]*^\$])/u", '\\\$1', $word); # Put a "\" in front of the metacharacters: .[]*^\$
                $fsql .= ' REGEXP '.Sql::parameterize('[[:<:]]'.$word); # word-boundary markers: [[:<:]] and [[:>:]]. REGEXP with UTF8 becomes case sensitive! So check in lower case. TODO: Test this: "SELECT 'fofo' REGEXP '^fo'" - IT WORKS!
            } else
                $fsql .= ' LIKE '.Sql::parameterize('%'.$word.'%'); # The % symbols need to be inside the parameter value: "LIKE ?"
            return $fsql;
        };
        $collate = ($tddParams['search']['case-sensitive']) ? ' COLLATE utf8_bin' : ''; # Case insensitive comparision. "ci" in DB
        # atTextBeginning - search for text at beginning
        if (self::get($regularId,'search','where') == 'start') {
            $fsql = '';
            foreach (self::get($regularId,'search','texts') as $field=>$text)
                $fsql.= $getFieldsSql($regularId, $tbl, $tddParams, $isTab, $selectDataParams, $field, $text);//, $minus
            $fsql = substr_replace($fsql, '(', 0, 4);
            $fsql .= $collate.') ';
            $whereSqls[] = $fsql;
        } 
        # Literal (exact) search for words in multiple fields
        else {
            foreach (self::get($regularId,'search','fields-of-words') as $aa) {
                $word = $aa['word'];
                $minus = $aa['minus'];
                $fields = $aa['fields'];
                if ($fields) {
                    $fsql = '';
                    foreach ($fields as $field)
                        $fsql.= $getFieldsSql($regularId, $tbl, $tddParams, $isTab, $selectDataParams, $field, $word, $minus);
                    $fsql = substr_replace($fsql, '(', 0, 4);
                    $fsql .= $collate.') ';
                    $whereSqls[] = $fsql;
                }
            }
        }
        return $whereSqls;
    }
    
    /**
     * @todo Do anonymously or in class
     */
    private static function getSortSql($regularId, $tbl, $tddParams, $isTab, $selectDataParams)
    {
        # Multiple columns  http://www.simplecoding.org/sortirovka-v-mysql-neskolko-redko-ispolzuemyx-vozmozhnostej.html
        # ORDER BY dat1 ASC, dat2 DESC        
        
        # Need?
        # As in the single-request is added artificially Request::get($regularId,'sort')
        $sortRequest = self::get($regularId,'sort');
        if (empty($sortRequest) && $isTab)
            $sortRequest['sort'] = 'asc';
        
        $sanitizeOrder = function($order) {
            if (!in_array($order, ['asc','desc']))
                $order = 'asc';
            return $order;
        };
        
        $ssql = '';
        foreach ($sortRequest as $field => $order) # Do function since the same code is in sort-update.php
        {
            if ($isTab) {
                if ($field == 'rec')
                    $col = $tbl.'.`rec-id`';
                elseif ($field == 'sort')
                    $col = $tbl.'.sort';
                else {
                    if ($selectDataParams[$field]){
                        $stabAlias = '`'.Blox::getTbl($selectDataParams[$field]['template'], '', true).'_alias`';
                        if ($selectDataParams[$field]['sort'] == 'rec')
                            $col = $stabAlias.'.`rec-id`'; 
                        else
                            $col = $stabAlias.'.dat'.Sql::sanitizeInteger($selectDataParams[$field]['sort']); 
                    } else
                        $col = $tbl.'.dat'.(int)$field;
                }
            } else
                $col = $tbl.'.'.Sql::sanitizeName($field);
            $ssql .= ', '.$col;
            if ($tddParams['sort']['case-sensitive']) # Case sensitive
                $ssql .= ' COLLATE utf8_bin';
            $ssql .= ' '.$sanitizeOrder($order);
        }        

        if ($aa = substr($ssql, 2))
            return ' ORDER BY '.$aa;
    }
    
    
    


    /**
     * @todo Do anonymously or in class
     */
    private static function getPartSql($regularId, $tbl, $tddParams, $selectFromSqls)
    {
        # $Parts evaluation
        $limit = self::get($regularId,'part','limit');
        if (self::get($regularId,'backward'))
        {
            if (!self::countNumOfParts($regularId, $tbl, $limit, $selectFromSqls['count'], $tddParams))
                return false; # No records
            # backward, desc
            if ($tddParams['part']['numbering'] == 'desc')
            {
                $redistribution = $tddParams['part']['redistribution'];
                if ($redistribution == -1) # old 
                {
                    if (!self::get($regularId,'part','current')) # Last part by default
                        self::add([$regularId=>['part'=>['current'=>self::get($regularId,'part','num-of-parts')]]]);

                    if (self::get($regularId,'part','num-of-parts') == self::get($regularId,'part','current')){
                        # Complete the last part with all {limit} records
                        $initRow = self::get($regularId,'part','num-of-recs') - $limit; # Retrieve last {limit} records in direct order
                        if ($initRow < 0)
                            $initRow = 0;
                    } else
                        $initRow = (self::get($regularId,'part','current') - 1) * $limit; # Retrieve N-th part in direct order
                } 
                else { # new method
                    if (empty($redistribution) || $limit == 1)
                        $redistribution = 0;
                    # The number of records in the tail (conventionally last part)
                    $numOfRestRecs = self::get($regularId,'part','num-of-recs') - (self::get($regularId,'part','num-of-parts') - 1) * $limit;
                    if ($redistribution == 0 || $numOfRestRecs == 0 || $numOfRestRecs == $limit){
                        if (!self::get($regularId,'part','current')) # Last part by default 
                            self::add([$regularId=>['part'=>['current'=>self::get($regularId,'part','num-of-parts')]]]);                            
                        # Retrieve N-th part in direct order
                        $initRow = (self::get($regularId,'part','current') - 1) * $limit;
                    } elseif ($redistribution > 0) {
                        # TODO: But if the division into parts occurs without a remainder, the last part should not be deleted, This is done in genPartNumOfSingle()
                        if (self::get($regularId,'part','num-of-parts') > 1) 
                            self::add([$regularId=>['part'=>['num-of-parts'=>(self::get($regularId,'part','num-of-parts')-1)]]]);# As no last part now

                        if (!self::get($regularId,'part','current')) # Last part by default 
                            self::add([$regularId=>['part'=>['current'=>self::get($regularId,'part','num-of-parts')]]]);

                        if ($redistribution > self::get($regularId,'part','num-of-parts'))
                            $redistribution = self::get($regularId,'part','num-of-parts');
                        # The number requested part, beginning with parts allocated for distribution
                        $distributedPartNum = self::get($regularId,'part','current') - self::get($regularId,'part','num-of-parts') + $redistribution;

                        # Additional parts
                        # In genPartNumOfSingle() the loop is done differently
                        if ($distributedPartNum > 0){
                            $numOfQuotientRows = Math::divideIntegers($numOfRestRecs, $redistribution, $numOfRemainderRows);
                            $numOfRemainderFreeRows = $redistribution - $numOfRemainderRows;
                            $limit_ = $limit;
                            $sum = 0;
                            $initR = 0;
                            for ($i=1; $i <= $distributedPartNum; $i++){
                                $initR = $sum;
                                $limit = $limit_ + $numOfQuotientRows;
                                if ($i > $numOfRemainderFreeRows)
                                    $limit++;
                                $sum += $limit;}
                            $initRow = $initR + (self::get($regularId,'part','num-of-parts') - $redistribution) * $limit_;
                        }
                        # Retrieve N-th part in direct order. Fill starting from the penultimate part
                        else
                            $initRow = (self::get($regularId,'part','current') - 1) * $limit;                            
                    }
                }

                self::add([$regularId=>['part'=>['default'=>self::get($regularId,'part','num-of-parts')]]]); # Since in "elseif ($redistribution > 0)" numOfParts is changing
                self::add([$regularId=>['part'=>['last'=>1]]]);
            } 
            else # backward, asc
            {
                $aa = self::get($regularId,'part','num-of-parts');                
                self::add([$regularId=>['part'=>['last'=>$aa]]]);
                self::add([$regularId=>['part'=>['default'=>1]]]);
                                
                if (!self::get($regularId,'part','current'))
                    self::add([$regularId=>['part'=>['current'=>1]]]);
                
                # Conventionally the last part               
                if ($aa == self::get($regularId,'part','current')){
                    $initRow = 0;
                    $limit = self::get($regularId,'part','num-of-recs') - (self::get($regularId,'part','current') - 1) * $limit;
                } else
                    $initRow = self::get($regularId,'part','num-of-recs') - self::get($regularId,'part','current') * $limit; # Retrieve N-th part in direct order

            }
        } 
        else # !$backward
        {
            # Not by default
            if (self::get($regularId,'part','current')) {
                if (!self::countNumOfParts($regularId, $tbl, $limit, $selectFromSqls['count'], $tddParams))
                    return false; # No recs
                # Retrieve N-th part in direct order
                $initRow = (self::get($regularId,'part','current') - 1)*$limit;}
            # !$num - by default
            else {
                $initRow = 0; # Retrieve first {limit} recs in direct order
                # numOfParts is unknown, then take the extra rec
                if (!self::get($regularId,'part','num-of-parts')) {
                    $limit++;
                    # If, after retrieving there will datum, then call self::countNumOfParts()
                    $GLOBALS['Blox']['limit-is-increased'] = true;}                    
            }
            self::add([$regularId=>['part'=>['default'=>1]]]);
            self::add([$regularId=>['part'=>['last' => self::get($regularId,'part','num-of-parts')]]]);
        }

        return ' LIMIT '.(int)$initRow.', '.(int)$limit;
    }
    
}



