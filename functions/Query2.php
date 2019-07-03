<?php
/**
 * Class Query2 is not used in API (only in admin scripts)
 * Copy of Query
 * "class Query2 extends Query" - this does not usefull
 */
class Query2 
{
    private static 
        $queries=[],
        $scriptName
    ;
    
    
    public static function capture($params=null)
    {
        if (empty($_GET))
            return true;
        
        if ($params == null) { # Capture the hole URL
            self::$queries = $_GET;
            # Get the script name, to put it later in the beginning
            self::$scriptName = Blox::getScriptName();
            if (self::$scriptName == 'page') 
                self::$scriptName = null;
        } else {   
            # String to array
            if (!is_array($params))
                $params = Url::queryToArray($params);
            if ($params)
                self::$queries = Arr::intersectByKey($_GET, $params);
        }
        return true;
    }

    
    


    public static function set($params)
    {
        if ($params) {
            # String to array
            if (!is_array($params))
                $params = Url::queryToArray($params);
            if ($params)
                self::$queries = $params;
        }
    }

    
    
    
    
    /**
     * @todo Use Arr::addByKeys($arr, $keys, $value) and Nav::add()
     */
    public static function add($params)
    {
        # String to array
        if (!is_array($params))
            $params = Url::queryToArray($params);
        self::$queries = Arr::mergeByKey(self::$queries, $params);
    }




    public static function get($param=null)
    {
        if (is_null($param))
            return self::$queries;
        else {
            if (is_array($param))                     
                $param = substr(Url::arrayToQuery($param), 1);
            $param = preg_replace('~[=].*~u', '', $param); # Remove all after the sign '='                
            $param = preg_replace( '~([^\[]+)(.*$)~u', '[$1]$2', $param); # dress the name of the parameter in square brackets
            if ($param)
                return Arr::getByChainOfKeys(self::$queries, $param);
        }     
    }
    



    public static function remove($params)
    {
        if (self::$queries && $params) {
            if (!is_array($params)) 
                $params = Url::queryToArray($params);                   
            if ($params)
                self::$queries = Arr::diffByKey(self::$queries, $params);
        }
    }
    
    

    public static function build($attachments=null, $detachments=null)
    {
        if (self::$queries) 
        {
            $queries = self::$queries;
            # Put this before attachments
            if ($detachments) {
                if (!is_array($detachments)) 
                    $detachments = Url::queryToArray($detachments);
                
                if ($detachments)
                    $queries = Arr::diffByKey($queries, $detachments);
            }
            
            # Additional attachments
            if ($attachments) {
                if (!is_array($attachments)) 
                    $attachments = Url::queryToArray($attachments);                    
                if ($attachments)
                    $queries = Arr::mergeByKey($queries, $attachments);
            }

            

            # Script name
            if (self::$scriptName) {
                if (isset($queries[self::$scriptName]))
                    $firstKey = self::$scriptName;
            } elseif (isset($queries['page'])) {
                $firstKey = 'page';
            }
                                
            if ($firstKey) {
                $first_ = $queries[$firstKey];
                unset($queries[$firstKey]);
            }
            # src
            if ($src_ = $queries['src'])
                unset($queries['src']); 
            # block
            if ($block_ = $queries['block'])
                unset($queries['block']);               
            # Do not sort sort-params
            if ($sort_ = $queries['sort'])
                unset($queries['sort']);
            # part
            if ($part_ = $queries['part'])
                unset($queries['part']);
            # single
            if ($single_ = $queries['single'])
                unset($queries['single']); 
            # pagehref
            if ($pagehref_ = $queries['pagehref'])
                unset($queries['pagehref']); 


            # Build a package
            $query = '';

            if ($firstKey) {
                $query .= '&'.$firstKey;
                if ($first_)
                    $query .= '='.$first_;
            }
            if ($src_)
                $query .= '&src='.$src_;
            if ($block_)
                $query .= '&block='.$block_;
            if ($queries) {
                Arr::orderByKey($queries); # sort     
                ##Arr::walk($queries, 'urlencode'); #497436375 # The function "urlencode()" replaces spaces " " by "+" in values
                $query.= Url::arrayToQuery($queries);
                /* #497436375
                if ($aa = urldecode(http_build_query($queries))) # The function "urlencode()" replaces spaces "+" by "%2B", [ by %5B, ] by %5D in a whole query 
                    $query .= '&'.$aa;
                */
                    
            }
            if ($sort_)
                $query.= Url::arrayToQuery(['sort'=>$sort_]);
                /* #497436375
                if ($aa = urldecode(http_build_query(['sort'=>$sort_])))
                    $query .= '&'.$aa;
                */
            if ($part_)
                $query .= '&part='.$part_;
            if ($single_)
                $query .= '&single='.$single_;
            if ($pagehref_)
                $query .= '&pagehref='.$pagehref_;
            $query = substr($query, 1);  # remove initial
            return $query;
        }
    }
}
