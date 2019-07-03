<?php
/*
 * @todo All methods related to blocks move to the class "Block".
 */
class Blox
{
    private static 
        $bloxversion = '',
        $bodyAttributes = '',
        $info = [],
        $pagehref = null,
        $pageId,
        $script,
        $terms = [], # localization terms
        $titleAppendixes = [],
        $titlePrependixes = [],
        $xCodes = []
        //$delegatedBlocks = [],
        //$childrenOfDelegatedBlocks = []
    ;
            

    /**
     * @example Blox::setVersion('12.4.10')
     */
    public static function setVersion($str)
    {   
        self::$bloxversion = $str;
    }

    public static function getVersion()
    {   
        return self::$bloxversion;
    }



    /**
     * @example
     *    Blox::info('cms','dir')
     *    Blox::info('cms','url')
     *    Blox::info('site','url')
     *    Blox::info('site','dir')
     *    Blox::info('templates','dir')
     *    Blox::info('templates','url')
     *    Blox::info('db','prefix')
     *    Blox::info('user')
     *
     * @todo Use Arr::getByKeys($arr, $keys) ?
     */
    public static function info()
    {   
        $args = func_get_args();        
        $size = Arr::getUnbrokenSize($args); # Elder empty arguments are forbidden
        if ($size==0) # No arguments
            return self::$info;
        elseif ($size==1) # One argument
            return self::$info[$args[0]];
        elseif ($size==2)
            return self::$info[$args[0]][$args[1]];
        elseif ($size==3)
            return self::$info[$args[0]][$args[1]][$args[2]];
        elseif ($size==4)
            return self::$info[$args[0]][$args[1]][$args[2]][$args[3]];
    }


    /** 
     * @param array $arr The same list of arguments as in the method Blox::info(), but in the form chain of keys with assignment
     * @example
     *    Blox::addInfo(['site'=>['site-is-down'=>true]]);
     *    Blox::addInfo(['site'=>['human-urls'=>['convert'=>true]]]);
     *
     * @todo Use Arr::addByKeys($arr, $keys, $value) and Nav::add()
     */
    public static function addInfo($arr)
    {   
        self::$info = Arr::mergeByKey(self::$info, $arr);        
    }
    
    /**
     * Save user-id in session
     */
    public static function setSessUserId($id)
    {   
        $_SESSION['Blox']['sess-user-id'] = $id;
    }
    
    /**
     * Use in scripts too: if (Blox::info('user'))
     */
    public static function getSessUserId()
    {   
        return $_SESSION['Blox']['sess-user-id'];
    }
    

    
                
    /**
     * Add code to the <head> section of the html document
     *
     * @param string $code 
     * @param array $options = [
     *    'action' =>   One of the values:
     *        'replace' — If the same code has already been inserted, the old code will be removed, and the new code will be inserted.
     *        'insert' — Even if the same code was already inserted, the new code will be inserted anyway.
     *        'include' — The argument $code is the path to the file with the code.
     *    'position' =>   One of the values:
     *        'top' — Code will be inserted above all other codes.
     *        'bottom' — Code will be inserted after all codes
     *    'before' => '' — string Write here code or code snippet for search of the code to put new code above it.
     *    'after' => '' — string Write here code or code snippet for search of the code to put new code below it.
     *    'unlike' => '' — mixed A code snippet or an array of codes. If code containing this string already exists, the current code will be ignored.
     *    'unmatch' => '' — string Regular pattern. If the code matching this pattern already exists, the current code will be ignored.
     *    'minimize' => false — minimize code, i.e replace double spaces, tabs, new lines with single spaces
     *    ];
     * 
     * @todo https://github.com/Stolz/Assets
     */    
    public static function addToHead($code, $options = [])
    {              
        self::addXcode('Head', $code, $options);
    }


    /*
     * Same as Blox::addToHead but adds a code in the end of document before the closing tag </body>
     */
    public static function addToFoot($code, $options = [])
    {              
        self::addXcode('Foot', $code, $options);
    }


   /**
    * 
    * @param string $dst Destination: 'Head' or 'Foot'
    * @param string $code Same as for Blox::addToHead 
    * @param array $options Same as for Blox::addToHead 
    * @return bool
    */
    private static function addXcode($dst, $code, $options=[])
    {   
        $code = trim($code);
        if (self::ajaxRequested() || !$code) # .js and .css are not necessary with ajax
            return;
        if ($options)
            Arr::formatOptions($options);
        # Defaults
        $options += ['action' => '','position' => '','before' => '','after' => '','unlike' => '','minimize' => false]; 

        # Check and prepare the $code
        if ($options['action'] == 'include') {
            if (file_exists($code)) {
                self::$xCodes['codes'][$code] = $code;
                $index = $code; # Do not remove, it is used below
                self::$xCodes['includes'][$index] = true; 
            } else {
                self::prompt(sprintf(self::getTerms('no-file'), $dst, $code), true);
                return;
            }               
        }
        else # String
        {      
            if ($options['unlike']) {
                if (is_array($options['unlike'])) {
                    foreach ($options['unlike'] as $u)
                        if (mb_strpos($code, $u) !== false)
                            return;
                } elseif (mb_strpos($code, $options['unlike']) !== false)
                        return;
            }

            if ($options['unmatch']) {
                if (preg_match($options['unmatch'], $code))
                    return;
            }

            if ($options['like']) {
                $like = false;
                if (is_array($options['like'])) {
                    foreach ($options['like'] as $l) {
                        if (mb_strpos($code, $l) !== false) {
                            $like = true;
                            break;
                        }
                    }
                } elseif (mb_strpos($code, $options['like']) !== false)
                    $like = true;
                if (!$like)        
                    return;
            }
            
            if ($options['match']) {
                if (!preg_match($options['match'], $code))
                    return;
            }

            
            if ($options['minimize']) {
                # TODO https://www.minifier.org/
                ##$code = preg_replace( '~//.*$~u', '', $code); # Remove single line comments. KLUDGE: Do not work for 'http://...'. Use urlencode(). TODO: Use tokens
                $code = preg_replace( '~\s+~u', ' ', $code); # Remove double spaces and new lines
                $code = preg_replace( '~/\*.*?\*/~u', '', $code); # Remove multiline comments
                $code = preg_replace( '~\s+([\{\}();])\s+~u', '$1', $code); # Remove spaces around brackets
            }
            
            # Let's create the $index to chech the code for uniqueness (for jquery). For the rest code you can do a search for array values instead of key.
            if ($code[0] == '<') { # This is an htm tag. Extragere file to check for uniqueness 
                if (strlen($code) < 1000 ) {# Do not check large code - give a unique random key. You can use $options['action'] == 'include' too.
                    if (preg_match('~\s(href|src)=(\'|")(.*?)(\'|")~iu', $code, $matches)) { # Search for links
                        if ($matches[3]) {
                            if (empty($matches[3][1])) # Only one link
                                $index = Url::convertToRelative($matches[3][0]); 
                        }
                    }
                    if (empty($index))
                        $index = $code;
                }
                if (empty($index)) # URL is not found or it is a large code. Do not check for uniqueness
                    $index = Str::genRandomString(11);
            } else { # This is the path to the file
                $index = Url::convertToRelative($code) ?: $code;
            }
            $index = mb_strtolower($index);

            # Similar codes that should not be used together. Use one the key for them
            # Indexes are: jquery, jquery-ui
            if (mb_strpos($index, '/jquery') !== false) {
                ### jquery-ui
                if (preg_match("~jquery-ui.*?\.js~", $index)) { # Found: jquery-ui, for example: /jqueryui/1.11.0/jquery-ui.min.js?d7531c
                    $index = 'jquery-ui';
                    if (!self::$xCodes['codes'][$index] || $options['action'] == 'replace') 
                        self::$xCodes['codes']['jquery-ui'] = $code;
                    elseif ($options['action'] == 'insert')
                        self::$xCodes['codes'][Str::genRandomString(11)] = $code;
                    else { # Such fragment exist. Do not add new code.
                        return;    
                    }
                }
                # Itself jquery
                elseif ( # Found text: jquery
                    mb_strpos($index, '/jquery.js') !== false ||
                    mb_strpos($index, '/jquery.min.js') !== false ||
                    mb_strpos($index, '/jquery.mobile.js') !== false ||
                    preg_match("~\W?jquery-\d+\.~", $index) # /jquery/jquery-1.5.min.js
                ) {
                    $index = 'jquery'; 
                    if (!self::$xCodes['codes'][$index] || $options['action'] == 'replace') {
                        self::$xCodes['codes'][$index] = $code;
                    } elseif ($options['action'] == 'insert') {
                        $index = Str::genRandomString(11);
                        self::$xCodes['codes'][$index] = $code;
                    } else { 
                        return; # Such fragment exist. Do not add new code.
                    }
                } else # Consider the code as unique (i.e. jquery plugin)
                    self::$xCodes['codes'][$index] = $code;
            }
            elseif (!self::$xCodes['codes'][$index] || $options['action'] == 'replace') # Consider the code as unique
                self::$xCodes['codes'][$index] = $code;
            elseif ($options['action'] == 'insert')
                self::$xCodes['codes'][Str::genRandomString(11)] = $code;            
            else # Not unique 
                return;
        }
        

        if ($options['before']) {
            $place = 'before';
            $adjacentCode = $options['before']; 
        } elseif ($options['after']) {
            $place = 'after';
            $adjacentCode = $options['after'];
        } elseif ($options['position']) {
            self::$xCodes[$dst]['positions'][$index] = $options['position'];
        } else {
            self::$xCodes[$dst]['shifted-indexes'][$index] = true; # Consider codes without options as sorted
        }
        
        if ($adjacentCode)
            self::$xCodes[$dst]['adjacents'][$index][$place] = preg_replace( "~\s+~u", ' ', trim($adjacentCode)); # Remove double spaces          

        if ($options['action'] == 'replace') {
            foreach (['Head','Foot'] as $aa)
                unset(self::$xCodes[$aa]['indexes'][$index]);
        }
        self::$xCodes[$dst]['indexes'][$index] = true;
    }
    



           

           

   /**
    * Output additional js and css codes
    * 
    * @param string $dst Destination: 'Head' or 'Foot'
    */
    public static function outputXcode($dst)
    {
        # Absolute codes (top bottom) reodering
        if ($aa = self::$xCodes[$dst]['positions']) {
            foreach ($aa as $index => $position) {
                unset(self::$xCodes[$dst]['indexes'][$index]); # Will be inserted new element
                if ($position == 'top') {
                    if (self::$xCodes[$dst]['indexes'])
                        self::$xCodes[$dst]['indexes'] = [$index => true] + self::$xCodes[$dst]['indexes']; # Put the element to the top  // Arr::mergeByKey() ?
                    else
                        self::$xCodes[$dst]['indexes'][$index] = true; # The element is first i.e. it is on top
                } elseif ($position == 'bottom')
                    self::$xCodes[$dst]['indexes'][$index] = true;
                self::$xCodes[$dst]['shifted-indexes'][$index] = true;
            }   
        }
        
        # Relative codes (before, after) reodering
        self::shiftAdjacents($dst);

        # Output (codes)
        if ($aa = self::$xCodes[$dst]['indexes']) {
            foreach ($aa as $index => $bb) {
                echo "\n";
                if (self::$xCodes['includes'][$index])
                    include self::$xCodes['codes'][$index];
                else
                    echo self::dressCodeWithTags(self::$xCodes['codes'][$index], $dst);
            }
        }
    }



    public static function dressCodeWithTags($code, $dst)
    {
        if ($code[0] == '<') # If it starts with a tag just output
            return $code;
        else { # Add tags for paths to the css or js file
            $terms = self::getTerms();
            $url = Url::convertToAbsolute($code) ?: $code;
            # Check if file exists
            if (self::info('user','user-is-admin'))
                if (!Url::exists($url))
                    self::prompt(sprintf($terms['url-not-exists'], '<b>'.$code.'</b>', $dst), true);
            $queryFreeCode = Str::getStringBeforeMark($code, '?') ?: $code;
            if (mb_strtolower(substr($queryFreeCode, -4)) == '.css')
                $code2 = '<link href="'.$url.'" rel="stylesheet" />';
            elseif (mb_strtolower(substr($queryFreeCode, -3)) == '.js')
                $code2 = '<script src="'.$url.'"></script>';
            else {
                self::prompt(sprintf($terms['arg-has-not-ext'], $dst, $url, $url), true);
                return;
            }
            return $code2;
        }          
    }





   /**
    * Reodering of relative codes (before, after) 
    * 
    * @param string $dst Destination: 'Head' or 'Foot'
    */
    private static function shiftAdjacents($dst) 
    {
        if ($aa = self::$xCodes[$dst]['adjacents']) 
        {
            $counter = count(self::$xCodes[$dst]['adjacents']);
            foreach ($aa as $index => $bb) {
                foreach ($bb as $place => $adjacentCode) {
                    # Fragment search is more convenient for API ...
                    $found = false;
                    foreach (self::$xCodes['codes'] as $index2 => $code) {
                        # You could search by index ($index2), transferring the pattern to the lower case, but due to the short index "jquery", we have to search in the full code ($code).
                        if (mb_strpos($code, $adjacentCode) !== false) { # mb_stripos() is less accurate
                            $targetIndex = $index2;
                            $found = true;
                            break;
                        }
                    }
                    # If the target code has already been successfully rearranged
                    if ($found && self::$xCodes[$dst]['shifted-indexes'][$targetIndex]) {
                        unset(self::$xCodes[$dst]['adjacents'][$index]); # For rerun of the rest        
                        unset(self::$xCodes[$dst]['indexes'][$index]); # Will be inserted new element
                        # Insert before index  (or after)
                        self::$xCodes[$dst]['indexes'] = Arr::wedge(
                            self::$xCodes[$dst]['indexes'], 
                            [$index => true],
                            $targetIndex, 
                            ($place == 'after') ? true : false
                        );
                        self::$xCodes[$dst]['shifted-indexes'][$index] = true;
                    }
                }
            }
            #
            if ($counter2 = count(self::$xCodes[$dst]['adjacents'])) {
                if ($counter2 < $counter)
                    self::shiftAdjacents($dst);
                else { # Relatives still left, since they have not found their masters.
                    foreach (self::$xCodes[$dst]['adjacents'] as $index => $aa) {
                        foreach ($aa as $place => $adjacentCode) {
                            /*
                            if ($dst=='Foot' && $place=='after' && $adjacentCode=='jquery-1') # KLUDGE If jquery is in the Head
                                ;
                            else
                            */
                                $htm .= ', '.$place.'=>"'.htmlentities($adjacentCode).'"';
                        }
                    }
                    if ($htm) {
                        $htm = substr($htm, 2);
                        self::error(sprintf(self::getTerms('no-file-links'), $dst, $htm));
                    }
                    return; # Just quit. These codes remain in the old places.
                }
            }
        }
    }




    public static function setBodyAttributes($str)
    {
        self::$bodyAttributes = $str;
    }
    
    public static function getBodyAttributes()
    {
        return self::$bodyAttributes;
    }
    


    /**
     * @param string $str
     * @return string
     */
    public static function encodeToUtf8($str)
    {
        if (!mb_check_encoding($str, 'UTF-8') || !($str === mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {
            $str = mb_convert_encoding($str, 'UTF-8');
            if (!mb_check_encoding($str, 'UTF-8'))
                self::error('Could not convert to UTF-8');
        }
        /*
        # Remove BOM
        if (substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf))
            $str=substr($str, 3);
        */
        return $str;
    }



    /**
     * @param string $message
     * @param bool $toExit Output message and exit
     *
     * @todo Second param rename to $errorType: warning, notice
     */
    public static function error($message, $toExit=null)
    {
        if (Blox::info('site','blox-errors','on')) {
            $arr = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $s = 
                "\n·························"
                .date("Y-m-d H:i:s")
                .substr((string)microtime(), 1, 9)
                .' ····· '
                .$arr['file'].':'.$arr['line']
                ." \n"
                .print_r($message, TRUE)
                ."\n"
            ;
            
            # Delete log if big
            if (!$_SESSION['Blox']['---blox-errors']) {
                $_SESSION['Blox']['---blox-errors'] = true;
                if (file_exists('---blox-errors.log')) {
                    if (filesize('---blox-errors.log') > 524288) { # 500K=524288    1MB=1048576
                        if (!unlink('---blox-errors.log')) {
                            Blox::prompt('Failed deleting the file: '.$url, true);
                        }
                    }
                }
            }
                    
            file_put_contents('---blox-errors.log', $s, FILE_APPEND);
        }
        if ($toExit)
            exit($message); # Complete the script and display only this message
    }



    
    /**
     * Output message in the log of CMS 
     * 
     * @param string $message
     * @param int $errorType 0 - notice, 1 - warning (displayed red)
     *
     * @todo Rename Blox::prompt() to Admin::prompt() and Check always the condition "if (Blox::info('user'))" outside of call
     */
    public static function prompt($message, $errorType=null)
    {
        if (!Blox::info('user','id'))
            return;
        
        if (empty($errorType))
            $errorType = 0; # 0 - notice
        else {
            $errorType = 1; # 1 - warning
            self::error($message);
        }
        if (!in_array($message, $_SESSION['Blox']['prompts'][$errorType]))
            $_SESSION['Blox']['prompts'][$errorType][] = $message;
    }



    /**
     *  Localization for scripts
     *
     * @param string $script Name of the script, i.e. url request "?scriptname"
     * @return array of localized terms
     *
     * @todo Rename to Admin::getScriptTerms() and Check always the condition "if (Blox::info('user'))" outside of call
     */    
    public static function getScriptTerms($script)
    {
		# Authorized user or scripts for visitors
        if (
            self::info('user','id') ||
            in_array($script, ['login','authenticate','edit','user-info','install','user-activation','password-restore','password-update' ,'registration-denied','maintenance']) ||
            ('page' == $script && $_SESSION['Blox']['fresh-recs']) # fresh public records
        ) {
            if (file_exists($termsFile = self::$info['cms']['dir'].'/lang/'.self::$info['cms']['lang'].'/scripts/'.$script.'.php')) {
                return include $termsFile;
            }
        }
    }




    /**
     * Get localization terms for any file in cms folders: scripts, functions, includes.
     * Accordingly in the folder "lang/en-US/" must be folders and files with same names.
     * If you call this method without arguments it returns an array of terms for all functions and methods of that file.
     * If you specify a key as an argument the method returns a term for the given key. 
     * For the case of multidimensional $terms you can specify several keys as arguments.
     * 
     * @return array of localized terms for the file in which this method was called.
     *
     * @todo Rename to Admin::getTerms() and Check always the condition "if (Blox::info('user'))" outside of call
     */
    public static function getTerms()
    {   
        $fl = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
        $fname = basename($fl);
        $dname = basename(dirname($fl)); # TODO: for second and third level by replacing Blox::info('cms','dir') from path
        if (!self::$terms[$fname]) {
            if (file_exists($termsFile = self::$info['cms']['dir'].'/lang/'.self::$info['cms']['lang'].'/'.$dname.'/'.$fname)) {
                self::$terms[$dname][$fname] = include $termsFile;
            }
        }
        $args = func_get_args();        
        $size = Arr::getUnbrokenSize($args); # Elder empty arguments are forbidden
        if ($size==0) # No arguments
            return self::$terms[$dname][$fname];
        elseif ($size==1) # One argument
            return self::$terms[$dname][$fname][$args[0]];
        elseif ($size==2)
            return self::$terms[$dname][$fname][$args[0]][$args[1]];
        elseif ($size==3)
            return self::$terms[$dname][$fname][$args[0]][$args[1]][$args[2]];
        elseif ($size==4)
            return self::$terms[$dname][$fname][$args[0]][$args[1]][$args[2]][$args[3]];
    
    }
    
    
    
    
    
    /**
     * Localization for includes. Does not replaces terms of the outer script
     *
     * @param array $terms Localized terms
     *
     * @todo Rename to Admin::includeTerms() and Check always the condition "if (Blox::info('user'))" outside of call
     */
    public static function includeTerms(&$terms=[])
    {   
        $fl = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];
        $fname = basename($fl);
        $dname = basename(dirname($fl)); # TODO: for second and third level by replacing Blox::info('cms','dir') from path

        $aa = function($dname, $fname) {
            if (file_exists($termsFile = self::$info['cms']['dir'].'/lang/'.self::$info['cms']['lang'].'/'.$dname.'/'.$fname))
                return include $termsFile;
        };
        # TODO: multidimensional
        if ($terms2 = $aa($dname, $fname)) {
            foreach ($terms2 as $k=>$v) {
                //if (!$terms[$k])
                if (!isset($terms[$k])) # To hide 'submit-and-return' button. If term is declared, but empty, it will be hidden.
                    $terms[$k] = $v;
            }
        }
    }
    
    

    /**
     * Returns array of parameters of the block. Keys of array: id, tpl, `delegated-id`, parent-block-id, `parent-rec-id`, src-block-id.
     *
     * @param int $blockId
     * @param int $paramName. if $paramName is specified method returns particular parameter.
     * @return mixed
     *
     * @todo Add element 'ancestors' to the Blox::getBlockInfo() return
     */
    public static function getBlockInfo($blockId, $paramName=null) 
    {
        if (empty($blockId))
            return;        
        $sql = "SELECT * FROM ".self::info('db','prefix')."blocks WHERE id=?";
        $result = Sql::query($sql, [$blockId]);
        if ($result) {
            if ($row = $result->fetch_assoc()) {
                $result->free();
                if (empty($row['delegated-id']))
                    $row['src-block-id'] = $blockId;
                else {
                    $row['src-block-id'] = $row['delegated-id'];
                    # Reserve tpl 
                    if (!isset($paramName) || $paramName =='tpl') {
                        $delegatedParams = self::getBlockInfo($row['src-block-id']);
                        $row['tpl'] = $delegatedParams['tpl']; # The template name is stored in the source block
                    }
                }
                if (isset($paramName))
                    return $row[$paramName];
                else
                    return $row;
            }
        }
    }



    /**
     * Determine the page ID on which there is a specified block. Only regular blocks are considered.
     *
     * @param int $blockId
     * @param int $delegatedAncestorId. Returns ID of the closest delegated ancestor block ID
     * @return int
     delegatedAncestorId
     *
     */
    public static function getBlockPageId($blockId, &$delegatedAncestorId=null)
    { 
        $blockId = (int)$blockId;
        if (!$blockId)
            return;
        if ($delegatedAncestorId)
            $delegatedAncestorId = (int)$delegatedAncestorId;
        $currBlockId = $blockId;
        while ($currBlockId) {
            $blockInfo = self::getBlockInfo($currBlockId);
            if (empty($blockInfo['parent-block-id'])) {  # Outer block is reached. 
                # Fetch the ID of the native page
                $sql = "SELECT id FROM ".self::info('db','prefix')."pages WHERE `outer-block-id`=? LIMIT 1";
                if ($result = Sql::query($sql,[$currBlockId])) {
                    if ($row = $result->fetch_assoc()) {
                        $result->free();
                        $blockPageId = $row['id'];
                    }
                }
            }
            # $delegatedAncestorId - closest delegated parent block ID 
            if (!$delegatedAncestorId && $currBlockId != $blockId) { # The initial block is not processed
                if ($blockInfo['delegated-id']) { # Is this block delegated?
                    $delegatedAncestorId = $blockInfo['delegated-id'];
                } elseif ($currBlockId) { # Is this block delegated at least once
                    #TODO: This code shows delegation for parent block (footer) block within footer, but it's not right.
                    $sql = "SELECT `delegated-id` FROM ".self::info('db','prefix')."blocks WHERE `delegated-id`=? LIMIT 1";
                    $result = Sql::query($sql,[$currBlockId]);
                    if ($row = $result->fetch_assoc()) {
                        $result->free();
                        if ($row['delegated-id']) {
                            $delegatedAncestorId = $row['delegated-id'];
                        }
                    }
                }
            }
            $currBlockId = $blockInfo['parent-block-id']; # This is regular ID
        }
        if (empty($blockPageId)) # If new block
            $blockPageId = self::getPageId();
        return $blockPageId;
    }        

    
    /**
     * Whether current script evoked by ajax request
     */
    public static function ajaxRequested()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] && mb_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            return true;
    }



    
    /**
     * Build the name of the data table by the template name
     *
     * @param string $tpl Template name
     * @param string $xprefix Write "x" for extra data tables
     * @return string Table name
     */
    public static function getTbl($tpl, $xprefix='', $noBackticks=false)
    {
        $tbl = self::info('db','prefix');
        $tbl.= '$'.$tpl;
        if ($xprefix)
            $tbl .= '$';
        if (!$noBackticks)
            $tbl = '`'.$tbl.'`';
        return $tbl;
        # Do not use "`" - they are not needed in Sql::tableExists() 
    }




    /**
     * RESERVED
     * Get base relative parametric URL of the current page. 
     * @return string 
     
    public static function getPagePhref()
    {
        return Router::getPhref(self::getPageHref());//, $redirectIfNotFound
    }
    */
    
    /**
     * Get base relative URL of the current page. 
     *
     * @param bool $encode To return value encoded by Url::encode().
     * @return string 
     */
    public static function getPageHref($encode=false)
    {
        $href = '';
        if (self::$pagehref !== null) {
            $href = self::$pagehref;
            if ($href && $encode)
                $href = Url::encode($href);
        } elseif (isset($_GET['pagehref'])) {
            $href = $_GET['pagehref'];
            if ($href && !$encode)
                $href = Url::decode($href);
        } elseif (Blox::ajaxRequested()) {
            $href = Url::convertToRelative($_SERVER['HTTP_REFERER']);
            if ($href && $encode)
                $href = Url::encode($href);
        } else { # If developer have not mentioned pagehref in a public form, i.e. wrote: action='?update&block=$block&rec=new'
            if (preg_match('~pagehref=([a-z0-9]+)($|&)~i', $_SERVER['HTTP_REFERER'], $matches))
                $href = $matches[1];
                if ($href && !$encode)
                    $href = Url::decode($href);
        }
        return $href;
    }
    
    
    /**
     * Get absolute URL of the current page. 
     *
     * @param bool $encode To return value encoded by Url::encode().
     * @return string 
     */
    public static function getPageUrl($encode=false)
    {   
        $href = self::getPageHref();
        $a = Url::convertToAbsolute($href);
        if ($encode)
            $a = Url::encode($a);
        return $a;
    }

    public static function setPageId($pageId)
    {   
        self::$pageId = $pageId;
    }
    public static function getPageId()
    {
        if (!self::$pageId) {
            # Get pageId from pagehref
            if (isset(self::$pagehref)) {
                $url = self::$pagehref;
            } elseif (isset($_GET['pagehref'])) {
                $url = Url::decode($_GET['pagehref']);
            }

            if (isset($url)) {
                if ($url) {
                	$phref = Router::convertUrlToPartFreePhref($url);
                	if (empty($phref))
                		$pageId = 1; # home
            		elseif (preg_match('~^\?page=(\d+)~', $phref, $matches)) # clear phref - only page parameter
                    	$pageId = $matches[1];
                }
                else
                    $pageId = 1;
                self::$pageId = $pageId;
            }       
        }
        return self::$pageId;
    }


    

    
    /**
     * Logo of the Blox CMS
     * If param is a string it represent the color of the logo
     * @param mixed $options {
     *   @var string $fill Color of the logo (CSS color property)
     *   @var bool $nofollow
     *   @var string $style CSS properties that replace all default properties
     *   @var string $xstyle CSS extra properties
     *   @var string $href URL
     *   @var string $title Title attribite value of the link
     * }
     * @return string
     * @example echo Blox::getBloxLogo('#fff');
     * @example echo Blox::getBloxLogo(['color'=>'#fff']);
     */
    public static function getBloxLogo($options=[])
    {   
        if (!is_array($options)) {
            $aa = $options;
            $options = [];
            $options['fill'] = $aa;
        }
        
        if ($options)
            Arr::formatOptions($options);
        
        # Defaults
        $options += [
            //'color'=>'#000', 
            'fill'=>'#000', 
            'nofollow'=>false,
            'style'=>'display:inline-block;vertical-align:middle;opacity:0.6;width:88px;margin:6px;', 
            'xstyle'=>'',
            'href'=>'http://blox.ru/', 
            'title'=>'Разработка сайтов и продвижение сайтов. Веб-студия Блокс в Набережных Челнах', 
        ];
        
        return '
        <a href="'.$options['href'].'" title="'.$options['title'].'" target="_blank" style="'.$options['style'].$options['xstyle'].'"'.($options['nofollow'] ? ' rel="nofollow"' : '').'>
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 93.2 21.9" enable-background="new 0 0 93.2 21.9" xml:space="preserve">
            <g>
        	<path fill-rule="evenodd" clip-rule="evenodd" fill="'.$options['fill'].'" d="M20.4,16.6L14.1,21c-1.8,1.3-4.8,1.2-6.7-0.1l-5.9-4.2
        		c-1.2-0.8-1.7-2-1.4-3V5.3c0.2,0.7,0.6,1.4,1.4,2l5.9,4.2c1.9,1.4,4.9,1.4,6.7,0.1l6.3-4.4C21,6.7,21.4,6,21.6,5.4v8.3
        		C21.8,14.8,21.4,15.8,20.4,16.6L20.4,16.6z M16.8,5.4l-4.9,3.5c-0.6,0.4-1.6,0.4-2.3,0L4.9,5.4C4.2,5,4.2,4.2,4.8,3.8l4.9-3.5
        		c0.6-0.4,1.6-0.4,2.3,0l4.8,3.4C17.4,4.2,17.5,4.9,16.8,5.4L16.8,5.4z M32,19.4c0,0.1-0.1,0.1-0.1,0.2c-0.1,0-0.1-0.1-0.1-0.2
        		l-1.3-4.9h-1.2l1.5,5.5c0.2,0.6,0.4,0.9,1,0.9c0.6,0,0.9-0.3,1-0.8l1.1-4.1c0-0.1,0-0.2,0.1-0.2c0.1,0,0.1,0.1,0.1,0.2l1.1,4.1
        		c0.1,0.6,0.4,0.8,1,0.8c0.6,0,0.9-0.3,1-0.9l1.5-5.5h-1.2l-1.3,4.9c0,0.1-0.1,0.2-0.1,0.2c-0.1,0-0.1-0.1-0.1-0.2l-1.2-4.3
        		c-0.1-0.5-0.4-0.6-0.9-0.7c-0.5,0-0.8,0.2-0.9,0.7L32,19.4L32,19.4z M45.2,20.9V20h-2.3c-0.9,0-1-0.4-1-1.2h2.8
        		c0.6,0,0.8-0.4,0.7-0.9c0-1.5-0.9-1.9-2.3-1.9c-1.9,0-2.4,0.6-2.4,2.5c0,1.4,0.2,2.4,1.8,2.4H45.2L45.2,20.9z M41.8,18
        		c0-0.9,0.3-1.2,1.2-1.1c0.6,0,1.2,0.2,1.2,0.9c0,0.2-0.1,0.3-0.2,0.3H41.8L41.8,18z M47.9,14.5v5.5c0,0.6,0.4,0.8,1,0.8h1.5
        		c0.1,0,0.3,0,0.4,0c1.3-0.2,1.5-1.2,1.5-2.3c0-1.4-0.4-2.4-2-2.4h-1.4v-1.5H47.9L47.9,14.5z M48.9,16.9h1.2c1.1,0,1.1,0.8,1.1,1.7
        		c0,0.9-0.1,1.5-1.2,1.5h-0.8c-0.3,0-0.4-0.1-0.4-0.4V16.9L48.9,16.9z M57.3,20.9h3.9c1.1,0,1.5-0.6,1.4-1.6c0-1-0.4-1.4-1.3-1.7
        		L59,16.8c-0.4-0.1-0.6-0.3-0.6-0.7c0-0.4,0.2-0.6,0.7-0.6h3.2v-1h-3.7c-1,0-1.5,0.6-1.4,1.5c0,0.8,0.3,1.5,1.1,1.7l2.5,0.8
        		c0.4,0.1,0.6,0.3,0.6,0.7c0,0.4-0.2,0.7-0.6,0.7h-3.5V20.9L57.3,20.9z M67.9,20.9V20c-1,0-1.4-0.1-1.4-1.2v-1.9h1.3v-0.8h-1.3v-1.5
        		h-1.1v1.5h-0.7v0.8h0.7v2.4C65.5,20.9,66.7,20.9,67.9,20.9L67.9,20.9z M74.8,20v-3.9h-1.1v3.6c0,0.3-0.1,0.4-0.4,0.4h-0.9
        		c-0.9,0-1-0.4-1-1.2v-2.8h-1.1v3c0,1.2,0.5,1.8,1.7,1.8h1.7C74.5,20.9,74.9,20.7,74.8,20L74.8,20z M80.8,14.5l0,1.5
        		c-0.6,0-1.2,0-1.8,0c-1.3,0.1-1.6,1.2-1.6,2.3c0,1.2,0.3,2.5,1.8,2.5H81c0.6,0,0.8-0.3,0.8-0.8v-5.5H80.8L80.8,14.5z M80.8,16.9
        		v2.8c0,0.3-0.1,0.4-0.4,0.4h-0.9c-1.1,0-1.1-0.8-1.1-1.6c0-0.8,0.1-1.6,1.1-1.5H80.8L80.8,16.9z M84.5,20.9h1.1v-4.8h-1.1V20.9
        		L84.5,20.9z M85.6,15.4v-0.8h-1.1v0.8H85.6L85.6,15.4z M90.6,16c-1.8,0-2.6,0.6-2.6,2.5c0,1.8,0.8,2.5,2.6,2.4
        		c1.8,0,2.5-0.6,2.6-2.5C93.2,16.6,92.4,16,90.6,16L90.6,16z M89.2,18.5c0-1.1,0.3-1.7,1.5-1.6c1.2,0,1.5,0.6,1.5,1.6
        		c0,1.1-0.3,1.6-1.5,1.6C89.5,20.1,89.2,19.5,89.2,18.5L89.2,18.5z M31.3,7.4v1.3l11.5,0V7.4L31.3,7.4L31.3,7.4z M31.3,3.6v1.3
        		l11.5,0V3.6L31.3,3.6L31.3,3.6z M28.7,1.1c4.5,0,8.9,0,13.4,0c1.8,0,3.2,1.4,3.2,3.2c0,0.7-0.2,1.4-0.6,1.9
        		c0.4,0.5,0.6,1.2,0.6,1.9c0,1.8-1.4,3.2-3.2,3.2l-13.4,0V1.1L28.7,1.1z M61.2,8.7v2.5c-3.7,0-7.4,0-11.1,0c-1.8,0-3.2-1.4-3.2-3.2
        		v-7h2.5v7.6L61.2,8.7L61.2,8.7z M65.2,11.3c-1.8,0-3.2-1.4-3.2-3.2c0-1.3,0-2.5,0-3.8c0-1.8,1.4-3.2,3.2-3.2c3.1,0,6.3,0,9.4,0
        		c1.8,0,3.2,1.4,3.2,3.2c0,1.3,0,2.5,0,3.8c0,1.8-1.4,3.2-3.2,3.2C71.5,11.3,68.4,11.3,65.2,11.3L65.2,11.3z M75.3,8.7V3.6H64.6v5.1
        		H75.3L75.3,8.7z M85.8,4.7l3.7-3.7H93l-5.1,5.1l5.1,5.1h-3.6l-3.7-3.7l-3.7,3.7h-3.6l5.1-5.1l-5.1-5.1h3.6L85.8,4.7L85.8,4.7z"/>
            </g>
            </svg>
        </a>';
    }


    public static function statCount($statSubject, $obj)
    {
        if (!$obj)
            return;
        $statSubject = Sql::sanitizeName($statSubject);
        if ($statSubject == 'referers')
            $obj = Text::truncate($obj, 329, ['plain', 'ellipsis'=>'...']);
        $sql = "SELECT * FROM ".self::info('db','prefix')."count{$statSubject} WHERE `date`=CURDATE() AND obj=?";
        if ($result = Sql::query($sql, [$obj])) {
            if ($result->fetch_row()) {
                $result->free();
                $sql = "UPDATE ".self::info('db','prefix')."count{$statSubject} SET counter=counter+1 WHERE `date`=CURDATE() AND obj=? LIMIT 1";
                Sql::query($sql,[$obj]);
            } else {
                $sql = "INSERT INTO ".self::info('db','prefix')."count{$statSubject} VALUES(CURDATE(), ?, 1)";
                Sql::query($sql, [$obj]);
            }
        }
    }


    /**
     * Get an array of the original (not delegated) blocks that use that template $tpl.
     *
     * @param string $tpl Template name
     * @param array $options = [
     *        'page-id' => 55, // Search only on page 55
     *        'ignore-page-id' => true, // Not apply pageId filter if only one block is founs. This speed up the execution.
     *        'excluded-block'=> 99 // Remove block 99 from the result array
     * ];
     * @return array Nonassociative array of blocks IDs
     *
     * @todo Accelerate if page is known
     */
    public static function getInstancesOfTpl($tpl, $options=[])
    {
        if (isEmpty($tpl)) 
            return;
        if ($options)
            Arr::formatOptions($options);
        # The list of not delegated blocks with the template $tpl
        $sql = 'SELECT id, `parent-block-id` FROM '.self::info('db','prefix')."blocks WHERE tpl=?";
        if ($options['excluded-block'])
            $sql .= ' AND id != '.$options['excluded-block'];
        $sql .= " ORDER BY id";
        if ($result = Sql::query($sql,[$tpl])) {
            $instances = [];
            while ($row = $result->fetch_assoc()) {
                # toGetBlockPageId       
                if ($options['page-id']) {
                    if ($result->num_rows > 1)
                        $toGetBlockPageId = true;
                    elseif (empty($options['ignore-page-id']))
                        $toGetBlockPageId = true;
                } 
                if ($toGetBlockPageId) {
                    $aa = $row['parent-block-id'] ?: $row['id']; # save one step up
                    $bb = self::getBlockPageId($aa);
                    if ($options['page-id'] == $bb)
                        $instances[] = $row['id'];}
                else 
                    $instances[] = $row['id'];
            }
            $result->free();
            return $instances;
        }
    }  




    

    /**
     * Append text to the end of the <title> element.
     *
     * @param string $str
     */
    public static function setTitleAppendix($str)
    {
        self::$titleAppendixes[] = Text::stripTags($str,'strip-quotes');
    }
    public static function getTitleAppendix()
    {
        if ($appendixes = self::$titleAppendixes) {
            foreach ($appendixes as $v)
                $appendix .= $v;
            return $appendix;
        }
    }
    

    /**
     * Prepend text to the beginning of the <title> element.
     *
     * @param string $str
     */
    public static function setTitlePrependix($str)
    {
        self::$titlePrependixes[] = Text::stripTags($str,'strip-quotes');
    }
    public static function getTitlePrependix()
    {
        if ($prependixes = self::$titlePrependixes) {
            foreach (array_reverse($prependixes) as $v)
                $prependix .= $v;
            return $prependix;
        }
    }

     
     
    /**
     * Execute the script (example ?edit&...) and output a web document
     *
     * @param string $href Base relative URL of the page (human or parametric). 
     * @param array $options = [
     *       'template'      => '',    // Custom template from "template/", for example 'mylogin'
     *       'get'           => false, // If true then method returns document, else outputs the document. TODO rename to 'output' or create new method: Blox::output()
     *       'only-body'      => false, // If true then method document will be truncated - only body without header
     *       'untemplatize'  => false, // Do not render the script by its regular template. Used to render by custom template.
     * ];
     *
     * @todo
     *    Works
     *       to execute script within other scripts
     *       to execute script "?error-document" (404,404,... )
     *    Do not Work
     *       to execute "?page=..." within other scripts
     *       to execute "?page=..." within tdd or tpl files - Infinite loop: Cannot redeclare function
     */
    public static function execute($href, $options=[])
    {
        if ($options)
            Arr::formatOptions($options);
        
        if (self::ajaxRequested() && !isset($options['only-body'])) 
            $options['only-body'] = true;
        
        if ($_GET['template']) {
            if (!isset($options['template']))
                $options['template'] = $_GET['template'];
            if (!isset($options['only-body']))
                $options['only-body'] = true; # KLUDGE
        }
        # Defaults
        $options += [
            'template'      => '',
            'get'           => false,
            'only-body'      => false,
            'untemplatize'  => false,
        ];

        # Scripts and 404 controller
        $getScriptName = function($href)
        {
            # When site-is-down, allow authorization only 
            if (
                !(isset($_GET['login']) || isset($_GET['authenticate'])) &&
                self::info('site','site-is-down') &&
                !self::info('user', 'user-is-admin')
            ) {
                $script = 'maintenance';
                return $script;
            }      
            if (empty($href))
                $script = 'page';
            elseif (Router::hrefIsParametric($href)) {
                if ($href=='?') # No request - just odd question mark
                    Url::redirect('./','exit');
                else { # There is a parametric query
                
                    # What is this? Remove? \\\\\\\\\\\\\
                    $hrefParams = Url::queryToArray($href);
                    if ($hrefParams) 
                        $_GET = Arr::mergeByKey($_GET, $hrefParams);
                    #////////////////////////////
                    
                    preg_match('~^\?([a-z0-9_-]+)=(\d+)~is', $href, $matches);
                    if ($matches[1]) {
                        if ('page'==$matches[1]) {
                            if (1==$matches[2]) {
                                if (count($_GET) > 1) { # ?page=1&utm_source=zzz
                                    $script = 'page';
                                    $pageInfo = Router::getPageInfoById(1); # KLUDGE: It will the superfluous calculation again in page.php: Router::getPageInfoByUrl($pagehref)
                                    if ($pageInfo) { # Regular page exists
                                        if (self::info('site','human-urls','convert')) {
                                            Router::redirectToHumanUrl($href); # Pseudopages too. 
                                        }
                                    }
                                } else {
                                    Url::redirect('./','exit');
                                }
                            } else {
                                $script = 'page';
                                $pageId = $matches[2];
                                if (Str::isInteger($pageId)) {
                                    $pageInfo = Router::getPageInfoById($pageId); # KLUDGE: It will the superfluous calculation again in page.php: Router::getPageInfoByUrl($pagehref)
                                    if ($pageInfo) { # Regular page exists
                                        if (self::info('site','human-urls','convert')) {
                                            Router::redirectToHumanUrl($href); # Pseudopages too. we need urlencode() because of spaces in search request like "?page=62&block=645&search=Baxi ECO4S". No $href must be $phref
                                        }
                                    } else
                                        $script = 'error-document';
                                }
                                else
                                    $script = 'error-document'; //$showError404 = true;
                            }
                        } elseif ('block'==$matches[1] || 'src'==$matches[1])
                            $script = 'block'; # Output block but not a page
                    } elseif (preg_match('~^\?([a-z0-9_-]+)(&|$)~is', $href, $matches)) { # there's a script (i.e. the first parameter without a value: /?assign)
                        $script = $matches[1];
                    } else
                        $script = 'error-document';
                }
            }            
            else { # Not parametric (i.e. human URL, i.e. main script)
                if ($phref = Router::getPhref($href, true)) { # true - redirect if not found. TODO: refuse this argument, redirect separately.
                    $script = 'page';
                    Router::emulateParametricRequest($phref);
                } else
                    $script = 'error-document';
            }

            if (empty($script))
                $script = 'error-document';
            
            # error page
            if ($script == 'error-document') {         
                $hrefParams2 = Url::queryToArray($href); # KLUDGE
                $code = (int)$hrefParams2['code'] ?: 404;
                //$_GET['code'] = $code; # KLUDGE
                http_response_code($code);
                # Is there a custom error page?
                if ($errorPagePhref = self::info('site','errorpages',$code)) {
                    $script = 'page';
            		if (preg_match('~^\?page=(\d+)~', $errorPagePhref, $matches)) { # KLUDGE for template param $page
                        Blox::setPageId($matches[1]); 
                        $_GET['page'] = $matches[1];
                    }
                    $_SERVER['REQUEST_URI'] = self::info('site','path').'/'.Router::convert($errorPagePhref);    # $_SERVER['REQUEST_URI'] - NOTTESTED in arbitrary calls
                }
            }
            # Current page URL
            if ($script == 'page') {
                self::$pagehref = Url::convertToRelative(
                    preg_replace('~&pagehref=.*$~u', '', $_SERVER['REQUEST_URI'])
                    //preg_replace('~&pagehref=.*$~u', '', urldecode($_SERVER['REQUEST_URI'])) #497436375
                );
            }
            return $script;
        };        
        $script = $getScriptName($href);
        # config.php
        if (file_exists('config.php')) {
            if (in_array($script, ['page','change','block','edit','update'])) { # 'edit' — to create a new record
                (function() {
                    include'config.php';
                })();
            }
        }

        if ($options['template'])
            $tplFile = self::$info['templates']['dir'].'/'.$options['template'].'.tpl';
        else
            $tplFile = self::$info['cms']['dir'].'/scripts/'.$script.'.tpl';
        
        $scriptFile = self::$info['cms']['dir'].'/scripts/'.$script.'.php';
        
        if (!file_exists($scriptFile)) {
            $script = 'error-document';
            $scriptFile = self::$info['cms']['dir'].'/scripts/'.$script.'.php';
        }

        if ($script != 'block')
            $terms = self::getScriptTerms($script);

        $template = new Templater;
        if (self::info('user','id')) {
            if (SID && self::$info['user']['user-is-admin']) // cookies off
                $template->assign('sess', '&'.SID); // for JS self-references via "onchange" only. Output_add_rewrite_var() does not work.            
        }
        
        if ($options['get']) {
            ob_start();
            include $scriptFile;
            return ob_get_clean();
        } else {
            self::$script = $script; # For Blox::getScriptName()
            include $scriptFile;
            exit; # otherwise two documents are output
        }
    }


    public static function getScriptName()
    {
        return self::$script;
    }



    /*
    public static function autoloadStore($fl)
    {
        if (!$fl)
            return;
        require_once $fl;
        Sql::query('CREATE TABLE IF NOT EXISTS autoloadedfunctions (path varchar(332) NOT NULL UNIQUE) ENGINE=MyISAM DEFAULT CHARSET=utf8'); //UNIQUE(path)
        Sql::query("INSERT IGNORE INTO autoloadedfunctions VALUES('$fl')");
    }
    public static function autoloadRegister() // In go.php
    {
        $result = Sql::query('SELECT * FROM autoloadedfunctions');
        while ($row = $result->fetch_row()) {
            spl_autoload_register(function(){include $row[0];});
        }
        $result->free();
    }
    */
    


    /**
     * @todo Optimize the code. If template has not been assigned, it makes no sense to extract the $tdd
     */
    public static function getBlockHtm($regularId, $blockInfo=null, $xprefix=null)
	{
            
        $pageId =  self::getPageId();
        $pagehrefQuery = '&pagehref='.self::getPageHref(true);             
        # for change  tpl
        if (isset($_GET['change']) && $regularId == $_GET['block']) {
            $boundChangingBlock = true;
            if ($_GET['instance'])
                $regularId = Sql::sanitizeInteger($_GET['instance']);
        }

        if (isset($_GET['tpl']) && $_GET['tpl'])
            $_GET['tpl'] = urldecode($_GET['tpl']);
        if (isset($_GET['old-tpl']) && $_GET['old-tpl'])
            $_GET['old-tpl'] = urldecode($_GET['old-tpl']);
      
	    if ($regularId) {   
            if ($blockInfo === null || $_GET['instance']) {
                $blockInfo = self::getBlockInfo($regularId);
            }
            $srcBlockId = $blockInfo['src-block-id']; # Moved above for initial outer block assignment
            $settings = ($blockInfo['settings'])
                ? unserialize($blockInfo['settings'])
                : []
            ;
            if ($blockInfo['tpl']) {
                $tpl = $blockInfo['tpl'];
                if (file_exists($f = self::info('templates', 'dir').'/'.$tpl.'.tplh')) #block-caching
                    $templatePrehandlerFile = $f; # deliberately lengthened
                #block-caching # Return cached block with linked css and js files
                if (!$templatePrehandlerFile && !Permission::ask('record', $srcBlockId)) { 
                    if ($settings['block-caching']['cached']) {
                        if (file_exists($f = 'cached-blocks/'.$regularId.'.htm')) {
                            ob_start();
                            require $f;
                            return ob_get_clean();
                        }
                    }
                }
                # Updates old file too
                $tdd = Tdd::get($blockInfo);
                # @todo Permission::ask() more strict
                Permission::addBlockPermits($srcBlockId, $tdd);
                
                if ($tdd['params']['dont-output-block']) # DEPRECATED since 14.2.5  Remove
                    $tdd['params']['template-files'] = [];
                                
                if (isset($tdd['params']['template-files'])) {
                    if ($tdd['params']['template-files']) {
                        foreach ($tdd['params']['template-files'] as $ext)
                            $templateFiles[$ext] = true;
                    } else
                        return ''; # dont-output-block
                } else
                    $templateFiles = ['tdd'=>true,'tpl'=>true,'css'=>true,'js'=>true]; # Not used: 'tplh'=>true, 'md'=>true,'tddh'=>true,'tuh'=>true,'tdh'=>true,'tdph'=>true
                    
                 # the template was without tdd then became with tdd. Update $blockInfo to get $blockInfo['src-block-id']
                if (empty($srcBlockId) && $tdd) {
                    $blockInfo = self::getBlockInfo($regularId);
                    $srcBlockId = $blockInfo['src-block-id'];
                }

                if ($tdd['params']['dont-retrieve-data'])  # DEPRECATED since 14.2.5  Remove
                    unset($templateFiles['tdd']);
                
                if (!$templateFiles['tdd'])
                    unset($tdd);
            }
        } else {
            return;
        }

        $template = (function($tddTemplater, &$blank) {
            # Template Type: Smarty or PHP?
            if ($tddTemplater) {
                if ('Smarty' == $tddTemplater)
                    $templater = 'Smarty';
                elseif ('PHP' == $tddTemplater)
                    $templater = 'PHP';
            } else {
                if ('Smarty' == self::info('site', 'templater'))
                    $templater = 'Smarty';
                else
                    $templater = 'PHP';
            }
            
            if ('PHP' == $templater) {
               	$template = new Templater;
                $blank = 'blank-php';
            } elseif ('Smarty'== $templater) { # Smarty now is vestige and may be removed
                if (file_exists(self::info('cms','dir')."/smarty")) {
                    require_once self::info('cms','dir')."/smarty/libs/Smarty.class.php";
                   	$template = new Smarty;
                    $template->cache_dir = self::info('site','dir').'/cached'; # Use exactly "cache" or none.  If none then it uses the same dir as default.  Do not use "."; "cash".
                    $template->compile_dir = self::info('site','dir').'/compiled'; # show this even this is default dir
                    $template->config_dir = self::info('site','dir');
                    $blank = 'blank-smarty';
                } else
                    self::error("There is no directory <b>smarty</b> in Blox CMS files");
            }
            return $template;
        })($tdd['params']['templater'], $blank);
        //$template = $createTemplate($tdd['params']['templater'], $blank);

        $allRecsPerms = Permission::get('record', $srcBlockId)[''];

        if ($tdd) {
            # userSeesEditButtons
            if ($allRecsPerms['edit'] && $allRecsPerms['create']) {
                if (!self::info('user','user-is-admin')) {
                    $noEditButtons = false;
                    if ($tdd['params']['no-edit-buttons'])
                        $noEditButtons = true;
                    else {
                        # If in $types there are only page or block types, then edit buttons show only to admin.
                        # If there are editable data, but they are secret then don't show button.
                        if ($t = $tdd['types']) {
                            if ($typesNames = Tdd::getTypesDetails($tdd['types'], ['page','block'], 'only-name')) {
                                foreach ($typesNames as $field => $aa)
                                    unset($t[$field]);
                            }
                            if ($z = $tdd['fields']['secret']) { # TODO: for editors of own records: $params['editor-of-records']['secret-fields'] = [2, 5];
                                foreach ($z as $field)
                                    unset($t[$field]);
                            }
                            if (empty($t))
                                $noEditButtons = true;
                        }
                    }
                    
                    if ($noEditButtons) {
                        Permission::add('record', [$srcBlockId, ''], ['edit'=>false, 'create'=>false]);
                    }
                }
            }
        }

        # public
        if (!self::info('user','id') && $tdd['params']['public']['show-new-rec-edit-button']) {
            Permission::add('record', [$srcBlockId, ''], ['create'=>true]);
            $GLOBALS['Blox']['enable-user-style-for-visitor'] = true; 
        }
        
        $buttElem = ($tdd['params']['span-edit-buttons']) ? 'span' : 'a';
        $editButtonStyleAttr = '';
        # Do not place this inside "if (!isEmpty($tpl))". For editbutton noTpl 
        if (Permission::ask('record', $srcBlockId)) {
            if ($style = self::getEditButtonStyle($regularId, $tdd['params']['edit-button-style'], $settings['edit-button-style']))
                $editButtonStyleAttr = ' style="'.$style.'"';
            $editButtonClass = 'blox-edit-button';
            if ($tdd['params']['no-edit-buttons'] || empty($tdd['types'])) {
                $editButtonClass .= ' blox-no-edit-buttons';
                $xEditButtonTitle = '. '.self::getTerms('no-edit-buttons');
            }
            # Editing query with all requests to the block
            //$filtersQuery = urldecode(Request::convertToQuery(Request::get($regularId))); # $filtersQuery used also in includes/button-edit.php   #497436375
            $filtersQuery = Request::convertToQuery(Request::get($regularId)); # $filtersQuery used also in includes/button-edit.php
            $editButtonClass .= ' blox-maintain-scroll';
            # New record button 
            if ($allRecsPerms['create']) {
                # Prepare new rec button. It is not yet assigned 
                $newRecHref = '?edit&block='.$regularId.$filtersQuery.'&rec=new'; # $filtersQuery added for $defaults[1] = Request::get(...)
                if ($xprefix && Blox::getscriptName()=='edit') # KLUDGE Blox::getscriptName()=='edit'
                    $newRecHref.= '&xprefix='.$xprefix;
                $newRecHref.= $pagehrefQuery; 
                $newRecButton = '<!--noindex--><'.$buttElem.' class="'.$editButtonClass.'"'.$editButtonStyleAttr.' href="'.$newRecHref.'" title="'.self::getTerms('new-rec').' ('.$tpl.')'.$xEditButtonTitle.'" rel="nofollow"><img class="blox-edit-button-img" src="'.self::info('cms','url').'/assets/edit-button-new-rec.png" alt="+" /></'.$buttElem.'><!--/noindex-->';
                $newDat = ['edit' => $newRecButton, 'edit-href' => $newRecHref]; # Do not show NewRecButton in case of single record mode if there are data
            }
            # Multirecord edit button
            if ($allRecsPerms['edit']) {
                $edit['href'] = '?edit&block='.$regularId.$filtersQuery.$pagehrefQuery;
                $imgfile2 = ($tdd['types']) ? 'edit-button-multi-rec.png' : 'edit-button-no-data.png'; # tpl without data                
                $edit['button'] = '<!--noindex--><'.$buttElem.' class="'.$editButtonClass.'"'.$editButtonStyleAttr.' href="'.$edit['href'].'" title="'.self::getTerms('multirec-edit-button-title').$xEditButtonTitle.' ('.$tpl.')" rel="nofollow"><img class="blox-edit-button-img" src="'.self::info('cms','url').'/assets/'.$imgfile2.'" alt="&equiv;" /></'.$buttElem.'><!--/noindex-->';
            }
        }

       	if (!isEmpty($tpl)) {
            if ($tdd['params']['dst']) {
                $GLOBALS['Blox']['ajax'] = true; # For blox.ajax.js
            }
            if (empty($tdd['types'])) # tpl without data
            {
                if (self::info('user','user-is-admin') && $allRecsPerms['edit']) {
                    $tab[0]['edit-href'] = $newRecHref;
                    $tab[0]['no-data'] = true;
                    $xEditButtonTitle = '. '.$terms['no-edit-buttons']; # The edit button of noneditable templates admin sees without: $tdd['params']['no-edit-buttons']
                    $tab[0]['edit'] = '<!--noindex--><'.$buttElem.' class="blox-edit-button blox-no-edit-buttons blox-maintain-scroll" href="'.$tab[0]['edit-href'].'" title="'.self::getTerms('no-data').' ('.$tpl.')'.$xEditButtonTitle.'" rel="nofollow"><img class="blox-edit-button-img" src="'.self::info('cms','url').'/assets/edit-button-no-data.png" alt="&#160; &#160;" /></'.$buttElem.'><!--/noindex-->';
                }
            }
            else # There are data types
            {
                # Fields that require pick request to retrieve data
                if (Request::get($regularId,'search')) # If there is a search request, pick-request are not required (but allowed).
                    $requiredPickRequestIsEmpty = false;
                else {
                    # fields that require pick request, otherwise records will not be retrieved
                    $requiredPickRequestIsEmpty = (function($regularId, $pickKeyFields)
                    {
                    	if ($pickKeyFields) {
                            foreach ($pickKeyFields as $field) {
                                $field = Sql::sanitizeInteger($field);
                                if ($field) {
                                    if (Request::get($regularId,'pick',$field)) { # May be empty which must return true
                                        $notempty = false;
                                        foreach (Request::get($regularId,'pick',$field) as $filter) {
                                            if (!isEmpty($filter)) {
                                                $notempty = true;
                                                break;
                                            }
                                        }
                                        if (!$notempty)
                                            return true;
                                    } else
                                        return true;
                                }
                            }
                    	}
                    })($regularId, $tdd['params']['pick']['key-fields']);
                    //$requiredPickRequestIsEmpty = $isAnyRequiredPickRequestEmpty($regularId, $tdd['params']['pick']['key-fields']);
                }

                # new record button will be hidden 
                if ($requiredPickRequestIsEmpty) {
                    Permission::add('record', [$srcBlockId,''], ['edit'=>false, 'create'=>false]);
                    self::prompt(sprintf(self::getTerms('required-pick-sess-is-empty'), '<b>'.$regularId.'('.$tpl.')</b>', count($tdd['params']['pick']['key-fields'])));
                } else {
                    # Get saved autoincrement part. Second part will be below
                    if ($tdd[$xprefix.'params']['part']['autoincrement']) {
                        # KLUDGE: Sometime after editing of tdd autoincrement becomes negative
                        if ($_SESSION['Blox']['Request'][$srcBlockId]['part']['autoincrement'] < 0)
                            unset($_SESSION['Blox']['Request'][$srcBlockId]['part']['autoincrement']);
                        # There is an explicit request for the partition. Under these conditions, the autoincrement does not work.
                        if ($_GET['block']==$regularId && $_GET['part']) {
                            self::prompt(sprintf(self::getTerms('autoincrement-and-part'), $regularId.'('. $tpl.')'));
                        } else { # No explicit request for the partition
                            $doAutoincrement = true;
                            if ($_SESSION['Blox']['Request'][$srcBlockId]['part']['autoincrement'])
                                Request::add([$regularId=>['part'=>['current'=>$_SESSION['Blox']['Request'][$srcBlockId]['part']['autoincrement']]]]);
                            elseif ('random' == $tdd[$xprefix.'params']['part']['autoincrement']) {
                                $retrieveTwice = true;
                            }
                         }  
                    }
                    $tab = Request::getTab($blockInfo, $tdd);
                    # randomPart  # KLUDGE: retrieve again to get numOfParts
                    if ($retrieveTwice) {
                        $randomPart = rand(1, Request::get($regularId,'part','num-of-parts'));
                        Request::add([$regularId=>['part'=>['current'=>$randomPart]]]);
                        $tab = Request::getTab($blockInfo, $tdd);
                    }
                    # Unobligatory safety code if there's a crash in Admin::assignNewTpl() for outer block. Sinse 13.1.2. To remove?
                    if (
                        !$tab &&
                        !$blockInfo['parent-block-id'] && 
                        !Request::get($regularId) && 
                        $allRecsPerms['create']
                    )   Dat::insert($blockInfo, null, $xprefix, $tdd);
                }

                # Substitution of data of types:'block','page','select' with html-codes
                if ($tdd['types'] && $tab) {
                    self::replaceBlockIdsByHtm($srcBlockId, $blockInfo['parent-block-id'], $tpl, $tab, $tdd);   # returns $tab
                }
                # This should be above the 'Edit-buttons codes creating', Otherwise, near the new record button appear new record button of the a nested block

                # Edit-buttons codes creating. Weakened the prohibition due to own records of users
                if (Permission::get('record', $srcBlockId)) {
                    # for Admin::isHtmValid
                    # TODO: Admin::isHtmValid()	-->	Text::getUnbalancedTags(). Do before $tagsList?.    
                    # TODO: Move this to ?update
                    if ($tab) {
                        $typesDetails_texts = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['tinytext', 'text', 'mediumtext', 'longtext'], 'only-name'); # for Admin::isHtmValid
                        foreach ($tab as $ro => $dat) {
                            if (Permission::get('record', [$srcBlockId, $dat['rec']], ['dat'=>$dat, 'tdd'=>$tdd])['edit']) {
                                include self::info('cms','dir')."/includes/button-edit.php";
                                # inspection of tags pairs
                                if ($typesDetails_texts) {
                                    foreach ($typesDetails_texts as $field => $aa)
                                        if ($dat[$field] && !$tdd['fields'][$field]['dont-check-tags'])
                                            Admin::isHtmValid($dat[$field], $tpl, $srcBlockId, $dat['rec'], $field); 
                                }
                            }
                        }
                    }
                    # Single-record template initially has regular edit button (not newRec). Solves this problem: after creating tdd file for a non-editable template, buttons disappear
                    elseif (!$tdd['params']['multi-record']) {
                        $tab[] = $newDat;
                        $edit['new-rec']['href'] = $newRecHref;
                        $edit['new-rec']['button'] = $newRecButton;
                    }
                } else { # !$userSeesEditButtons. Public
                    if ($tdd['params']['public']['show-fresh-recs-edit-buttons']) {
                        foreach ($tab as $ro => $dat) {
                            if ($_SESSION['Blox']['fresh-recs'][$srcBlockId][$dat['rec']])
                                include self::info('cms','dir')."/includes/button-edit.php";
                        }
                    }
                }

                # New Record Button for multi-record templates
                if ($allRecsPerms['create']) 
                {
                    if ($tdd['params']['multi-record']) 
                    {
                        # if you have a secret field for the user-id and there is $params['pick']['key-fields'] but for another user then do not show new record button
                        $toHideNewRecButton = function($regularId, $userIdField, $pickKeyFields) {
                            if ($userIdField && in_array($userIdField, $pickKeyFields)) {
                                $hideNewRecButton = true;
                                $operators = ['eq', 'le', 'ge'];
                                foreach ($operators as $oper) {
                                    if (Request::get($regularId,'pick',$userIdField,$oper) == self::info('user','id')) {
                                        $hideNewRecButton = false;
                                        break;
                                    }
                                }
                                return $hideNewRecButton;
                            }
                        };

                        # Do not show two buttons: first and new
                        if (!$toHideNewRecButton($regularId, $userIdField, $tdd['params']['pick']['key-fields'])) # If not single and no forbid of key-fields
                        {
                            if (!Request::get($regularId,'single')) {
                                $showNewRecButton = false;
                                if ($tdd['params']['no-new-rec-button']) { # Don't show new record button  if "no-new-rec-button" and and there are already any records and there is no search and pick requests
                                    ;// && $tab[0] 
                                # New record button show only once
                                } elseif (Request::get($regularId,'part','num-of-parts') && Request::get($regularId,'part','current')) {
                                    # backward
                                    if (Request::get($regularId,'backward')){
                                        # backward desc
                                        if ($tdd['params']['part']['numbering'] == 'desc') {
                                            if (Request::get($regularId,'part','num-of-parts') == Request::get($regularId,'part','current'))
                                                $showNewRecButton = true; 
                                            }
                                        # backward asc
                                        elseif (Request::get($regularId,'part','current') == 1) # Put button to the beginning
                                            $showNewRecButton = true;
                                    }
                                    # not backward
                                    else {
                                        if (Request::get($regularId,'part','num-of-parts') == Request::get($regularId,'part','current'))
                                            $showNewRecButton = true;
                                    }
                                } else
                                    $showNewRecButton = true;

                                if ($showNewRecButton){

                                    # Put button to the beginning
                                    if (Request::get($regularId,'backward')) {
                                        if (empty($tab)) 
                                            $tab[] = $newDat;
                                        else 
                                            $tab = array_merge([$newDat], $tab);
                                    } else
                                        $tab[] = $newDat;
                                    //$edit['new-rec']['href'] = $newRecHref;
                                    $edit['new-rec']['button'] = $newRecButton;
                                }
                            } else { # Single
                                if (!$tab) {
                                    $tab[] = $newDat;
                                }
                            }
                            // Code removed because new rec button appears in "as-visitor" mode
                            # New record href always
                            $edit['new-rec']['href'] = $newRecHref;
                            $edit['new-rec']['button'] = $newRecButton;
                        }
                    }
                }

                # If subscription is allowed
                if ($tdd['params']['subsription'] && $tdd['params']['multi-record'])
                {
                    if (Proposition::get('user-is-subscriber', self::info('user','id'), $srcBlockId))
                        $subscription['user-is-subscribed'] = true;

                    # Attempt the subscribe
                    $subscrError = Subscription::getError($srcBlockId);
                    if (!isEmpty($subscrError))
                        $subscription['error'] = $subscrError;
                }

                # Autoincrement and save. The first part is above
                if ($doAutoincrement) {
                    if (Request::get($regularId,'backward')) {
                        if (Request::get($regularId,'part','current') == 1)
                            Request::add([$regularId=>['part'=>['current'=>Request::get($regularId,'part','num-of-parts')]]]);
                        else
                            Request::add([$regularId=>['part'=>['current'=>(Request::get($regularId,'part','current')-1)]]]);
                    } else {
                        if (Request::get($regularId,'part','current') == Request::get($regularId,'part','num-of-parts'))
                            Request::add([$regularId=>['part'=>['current'=>1]]]);
                        else
                            Request::add([$regularId=>['part'=>['current'=>(Request::get($regularId,'part','current')+1)]]]);
                    }
                    $_SESSION['Blox']['Request'][$srcBlockId]['part']['autoincrement'] = Request::get($regularId,'part','current');
                }
            } # end of tpl with data


            # Do not show the bar for editors of own records
            if (($tdd['params']['no-bar']) || $tdd['params']['editor-of-records']['no-bar'])  // || ($whatRecordsEditsUser == 'own' && $tdd['params']['editor-of-records']['no-bar'])
                $GLOBALS['Blox']['no-bar'] = true;

            $tplFile = self::info('templates', 'dir').'/'.$tpl.'.tpl';
            
            if ($tdd['xtypes']){
                $xtab = Request::getTab($blockInfo, $tdd, 'x');
                
                # Subsitution of ids (block,page,select) by html code
                if ($xtab) {
                    self::replaceBlockIdsByHtm($srcBlockId, $blockInfo['parent-block-id'], $tpl, $xtab, $tdd, 'x');   # returns $tab
                }
            }
        }
        # No tpl
    	else {
            //if ($allRecsPerms['edit']) {
            if (Blox::info('user','user-is-admin') && !Blox::info('user','user-as-visitor') && !isset($_GET['change'])) { # KLUDGE: Remake this conditions with Permission class
                $tab[0]['edit-href'] = $newRecHref;
                $aa = 'blox-edit-button blox-no-tpl blox-maintain-scroll';
                $tab[0]['edit'] = '<!--noindex--><'.$buttElem.' class="'.$aa.'" href="'.$newRecHref.'" title="'.self::getTerms('tpl-not-assigned').'" rel="nofollow"><img class="blox-edit-button-img" src="'.self::info('cms','url').'/assets/edit-button-no-tpl.png" alt="?" /></'.$buttElem.'><!--/noindex-->';
                $edit['new-rec']['href'] = $newRecHref;
                $edit['new-rec']['button'] = $tab[0]['edit'];
            }
            # For outer block show the blank, for nested block show assignment button 
            $tplFile = self::info('cms','dir').'/scripts/'.$blank.'.tpl';
       	}


        # All tpl vars:
        #$tab   
        #$xtab 
        #$request
        #$blockInfo
        $dat = $tab[0];
        //$request = Request::get($regularId);
        $block = $blockInfo['id'];
        $page = $pageId;
        if ($xtab)  {
            $xdat = $xtab[0];
            if (Permission::ask('record', $srcBlockId)) {
                $xdat['edit-href'] = '?edit&block='.$regularId.$filtersQuery.'&xprefix=x&rec=1'.$pagehrefQuery;
                $xdat['edit'] = '<!--noindex--><'.$buttElem.' class="'.$editButtonClass.'"'.$editButtonStyleAttr.' href="'.$xdat['edit-href'].'" title="" rel="nofollow"><img class="blox-edit-button-img" src="'.self::info('cms','url').'/assets/edit-button-edit-rec.png" alt="&equiv;" /></'.$buttElem.'><!--/noindex-->';
            }
        }
        if ($udat = $_SESSION['Blox']['udat'][$regularId]) # udat - handled form data
            unset($_SESSION['Blox']['udat'][$regularId]);
        if ($dpdat = $_SESSION['Blox']['dpdat'][$regularId])
            unset($_SESSION['Blox']['dpdat'][$regularId]);
        if ($drdat = $_SESSION['Blox']['drdat'][$regularId])
            unset($_SESSION['Blox']['drdat'][$regularId]);
            
        # .tplh    
        if ($templateFiles['tpl'] && $templatePrehandlerFile) {
            (function(&$tdat, &$block, &$blockInfo, &$dat, &$page, &$tab, &$udat, &$dpdat, &$drdat, &$xdat, &$xtab, $templatePrehandlerFile) {
                include $templatePrehandlerFile;
            })($tdat, $block, $blockInfo, $dat, $page, $tab, $udat, $dpdat, $drdat, $xdat, $xtab, $templatePrehandlerFile);
            $template->assign('tdat', $tdat);
        }
        

        # All assignments
        if ($templateFiles['tpl']) {
            $template->assign('tab', $tab); # to single templates too
            $template->assign('dat', $dat);    # dublicate for single block and single
            $template->assign('blockInfo', $blockInfo);
            $template->assign('block', $block);
            $template->assign('page', $page);
            if (isset($xtab))
                $template->assign('xtab', $xtab);
            if (isset($xdat))
                $template->assign('xdat', $xdat);
            if (isset($subscription))
                $template->assign('subscription', $subscription); # User subscribed
            if (isset($udat))
                $template->assign('udat', $udat);
            if (isset($dpdat))
                $template->assign('dpdat', $dpdat);
        }

        # Custom template for system script. 
        # Data template variables should not match system template variables. 
        # url parameters for the script are not needed, as they come via get
        if ($bb = Request::get($regularId,'script')) {
            (function($script, $options, &$template) { # $options not used? To add editable data?
                $terms = self::getScriptTerms($script);
                $scriptFile = self::info('cms','dir').'/scripts/'.$script.'.php';
                include $scriptFile;
            })($bb, ['only-body'=>true, 'untemplatize'=>true], $template); # Returnes $template
            //$aa($bb, ['only-body'=>true, 'untemplatize'=>true], $template); 
        }

        if (Permission::get('record', $srcBlockId)) {
            $template->assign('edit', $edit);
            if ($tplFile && $templateFiles['tpl']) {
                if (file_exists($tplFile)) {
                    # KLUDGE: It is possible not to retrieve block data
                    # Do to show the old template, when the new template is selected 
                    if (isset($_GET['change']) && $_GET['block']==$regularId && isset($_GET['old-tpl']) && isset($_GET['tpl']) && $_GET['old-tpl'] != $_GET['tpl']) 
                        $blockHtm = ' ';
                    else {
                        $template->assign('blankTerms', self::getTerms('blank-terms'));
                        $blockHtm = $template->fetch($tplFile);
                    }
                }
                else
                    self::error("The template file <b>{$tpl}.tpl</b> does not exist!");
            } elseif ($allRecsPerms['edit']) # Template was removed - put the edit button
                $blockHtm = $newRecButton;
            # Blocks boundaries for the script "objectOfUser"
            if ($_GET['bound-block'])  {
                if ($regularId == $_GET['bound-block']) {
                    $borderColor = 'f00';
                    $borderWidth = 2;
                    $borderStyle = 'dotted';
                }
            } elseif ($boundChangingBlock) {# for change tpl
                $borderColor = 'f00';
                $borderWidth = 3;
                $borderStyle = 'dashed';
                # Spaces only
                if (preg_match("/^\s*$/", $blockHtm)) { 
                    $changeProps = '; background: #ff0; color:red; text-align:center; padding:9px; font:12px Verdana; text-transform:none';
                    if ($_GET['tpl']) {
                        $blockHtm = '<p style="font-size:15px">'.sprintf(self::getTerms('to-assign-tpl'), '<b>'.$_GET['tpl'].'</b>').'</p>';
                        $blockHtm .= '<p>'.Tdd::get(['tpl'=>$_GET['tpl']])['params']['description'].'</p>';
                        $blockHtm .= '<p style="margin:11px 0"><a href="" class="blox-maintain-scroll" data-blox-submit-selector="#blox-check" style="background:red; color:#ff0; font-weight:bold; padding: 5px 9px">'.self::getTerms('assign').'</a></p>';
                        $blockHtm .= '<p style="font-size:11px">';
                            $blockHtm .= (file_exists(self::info('templates', 'dir').'/'.$_GET['tpl'].'.tpl'))
                                ? self::getTerms('to-delegate')
                                : self::getTerms('tpl-not-exists')
                            ;
                        $blockHtm .= '</p>';
                    } else
                        $blockHtm = '<p><b>'.self::getTerms('select-tpl').'</b></p>';
                }
            } elseif (self::info('user','user-sees-block-boundaries')) {
                $borderColor = (function() {
                    $colors = ['F00','C0F','06E','090','DA0'];
                    $aa = array_keys($colors); # Do not join codes!
                    $maxId = end($aa);
                    $GLOBALS['Blox']['color-id'] = ($GLOBALS['Blox']['color-id'] + 1) % ($maxId + 1);
                    return $colors[$GLOBALS['Blox']['color-id']];
                })();
                //$borderColor = $getNextColor();
                $borderWidth = 1;
                $borderStyle = 'dotted';
            }

            if ($borderStyle)
                $blockHtm = '<div id="bound-block'.$regularId.'" class="blox-bound-block" style="border:'.$borderWidth.'px '.$borderStyle.' #'.$borderColor.'; margin:'.$borderWidth.'px'.$changeProps.'">'.$blockHtm.'</div>';

            if ($tdd['params']['nocaching'])
                Cache::deleteByBlock($regularId);
		} else { # visitor
            if ($templateFiles['tpl'] && file_exists($tplFile))
                $blockHtm = $template->fetch($tplFile);
            # Server caching
            if ($tdd['params']['nocaching'])
                self::addInfo(['site'=>['caching'=>false]]);
            elseif (self::info('site', 'caching'))
                Cache::registerBlock($pageId, $regularId);
        }
        # Browser cache
        if ($tdd['params']['nocache'])
            self::addInfo(['site'=>['nocache'=>true]]);
        # blox-dst-99
        if ($tdd['params']['dst'] && !self::ajaxRequested()) {
            $blockHtm = '<div id="blox-dst-'.$regularId.'">'.$blockHtm.'</div>';
        }

        # noindex
        if ($tdd['params']['no-index-if-delegated'] && ($srcBlockId != $regularId))
            $blockHtm = '<!--noindex-->'.$blockHtm.'<!--/noindex-->';        

        # Link template's CSS and JS files 
        if ($templateFiles['css'] && file_exists(self::info('templates', 'dir').'/'.$tpl.'.css')) {
            $cssUrl = self::info('templates', 'url').'/'.$tpl.'.css';
            self::addToHead($cssUrl);
        }
        if ($templateFiles['js'] && file_exists(self::info('templates', 'dir').'/'.$tpl.'.js')) {
            $jsUrl = self::info('templates', 'url').'/'.$tpl.'.js';
            self::addToFoot($jsUrl);
        }

        #block-caching # Create cache file of the block
        if (
            !Permission::ask('record', $srcBlockId) &&
            $blockHtm &&
            $settings['block-caching']['cache'] &&
            (!$settings['block-caching']['cached'] || !file_exists('cached-blocks/'.$regularId.'.htm')) &&
            Files::makeDirIfNotExists('cached-blocks')
        ){
            # img
            $blockHtm = (function($htm) {
                $htm2 = preg_replace_callback( 
                    '~\s(src|href)="([^"]*?)"~siu', # Find "src" in images   #   '~(<img\s[^>]*src=)("??)([^"\s>]*?)\\2(\s|>)~siu'
                    function ($matches) {
                        $url = $matches[2];
                        if ($u = Url::convertToAbsolute($url) ?: $url)
                            return ' '.$matches[1].'="'.$u.'"'; 
                        else
                            return $matches[0]; # do not change
                    },
                    $htm
                );
                return $htm2 ?: $htm;
            })($blockHtm);
            # <style...>...:url(___)...</style>
            # ...style="...:url(___)..."
            # ...:url(___)
            $blockHtm = (function($htm) {
                $htm2 = preg_replace_callback( 
                    '~(\w\s*:\s*url\s*\()(|\'|")([^"\'\)]+)(\\2\))~siu', 
                    function ($matches) {
                        $url = $matches[3];
                        if ($u = Url::convertToAbsolute($url) ?: $url)
                            return $matches[1].$matches[2].$u.$matches[4]; 
                        else
                            return $matches[0]; # do not change
                    },
                    $htm
                );
                return $htm2 ?: $htm;
            })($blockHtm);
            # links
            if ($settings['block-caching']['absolute']) {
                $blockHtm = (function($htm) {
                    $htm2 = preg_replace_callback( 
                        '~\shref="([^"]*?)"~siu', # KLUDGE: Consider only links without endings: jpg|gif|png|svg|webp. Now "href" is iterated twice
                        function ($matches) {
                            $url = $matches[1];
                            if ($u = Url::convertToAbsolute($url) ?: $url)
                                return ' href="'.$u.'"'; 
                            else
                                return $matches[0]; # do not change
                        },
                        $htm
                    );
                    return $htm2 ?: $htm;
                })($blockHtm);
            }
            
            if ($blockHtm) {
                $code = '';
                if ($cssUrl)
                    $code.= 'Blox::addToHead(\''.$cssUrl.'\');';
                if ($jsFile)
                    $code.= 'Blox::addToFoot(\''.$jsFile.'\');';
                if ($code)
                    $code = '<?php '.$code.' ?>';
                #block-caching
                if (file_put_contents('cached-blocks/'.$regularId.'.htm', $blockHtm.$code))
                    if (!$settings['block-caching']['cached']) {
                        $settings['block-caching']['cached'] = true;
                        Sql::query('UPDATE '.Blox::info('db','prefix').'blocks SET `settings`=? WHERE id=?', [serialize($settings), $regularId]);
                    }
            }
        }
        
        return $blockHtm;
	}


    /**
     * #block-caching
     * @param int $regularId
     * @return bool
     */
    public static function updateBlockCache($regularId)
    {
        $blockInfo = self::getBlockInfo($regularId);
        if ($blockInfo['settings']) {
            $settings = unserialize($blockInfo['settings']);
            unset($settings['block-caching']['cached']);
            Sql::query('UPDATE '.Blox::info('db','prefix').'blocks SET `settings`=? WHERE id=?', [serialize($settings), $regularId]);
        }
        if ($blockInfo['parent-block-id'])
            self::updateBlockCache($blockInfo['parent-block-id']);
        //unlink('cached-blocks/'.$regularId.'.htm');
    }
    
    
    
    /**
     * @todo Make outer foreach loop by $tab not by $typeNames
     * @todo Rename to Blox::substituteData()/ 
     * @todo Not used $parentBlockId ?
     */
    public static function replaceBlockIdsByHtm($srcBlockId, $parentBlockId, $tpl, &$tab, $tdd, $xprefix='')
    {
        $pagehref = self::getPageHref();
        $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
        $typesNames = ['block','page','select'];
        # What is it!
        if (Permission::get('record', [$blockInfo['src-block-id']])['']['edit'] && !$_SESSION['Blox']['check-urls'][$srcBlockId]) {
            ;   
        }
        
        if (
            $tdd[$xprefix.'fields']['reconvert-url'] || 
            $tdd[$xprefix.'fields']['remove-elements'] ||
            $tdd[$xprefix.'fields']['strip-tags']
        ) $typesNames = array_merge($typesNames, ['varchar','tinytext', 'text', 'mediumtext', 'longtext']);
            
        if ($tdd[$xprefix.'fields']['reconvert-url']) { 
            $transformToHhref = function($htm) {
                $htm2 =  preg_replace_callback(                             
                    '~(<a\s[^>]*href=)("??)([^"\s>]*?)\\2(\s|>)~siu',
                    function ($matches) {
                        if ($href = Url::convertToRelative($matches[3])) {  # Inner link
                            if (Blox::info('site','human-urls','convert')) { 
                                if (Router::hrefIsParametric($href)) # Convert parametric url to human
                                    $href = Router::convert($href); 
                            }
                        } else # Outer link
                            $href = $matches[3]; 
                            
                        if ($href)
                            return $matches[1].$matches[2].$href.$matches[2].$matches[4]; 
                        else
                            return $matches[0]; # do not change
                    },
                    $htm
                );
                return $htm2 ?: $htm;
            };
        }
        
        if ($aa = Tdd::getTypesDetails($tdd[$xprefix.'types'], $typesNames, 'only-name')) { # $addTypeParams=0
            # Reduce an array for foreach and for search
            foreach ($aa as $field => $typeName)
                $typeNames[$field] = $typeName['name'];
        }
        
        if ($d = $tdd[$xprefix.'fields']['none']) {
            foreach ($d as $field)
                unset($typeNames[$field]);
        }
        
        if (Permission::get('record', $srcBlockId))
            $editPermissionExists = true;

        if ($typeNames)
        {
            $tbl = self::getTbl($tpl, $xprefix);
            # templates-from-grandparents
            if (empty($_SESSION['Blox']['templates-from-grandparents']))
                if ($editPermissionExists)
                    $_SESSION['Blox']['templates-from-grandparents'] = Store::get('templates-from-grandparents') ?: null;

            if ($editPermissionExists) {
                $typesDetailsB = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['block']);
                $typesDetailsP = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['page']);
            }

    
            # KLUDGE: Improve order of loops
            foreach ($typeNames as $field => $typeName)
            {
                if ('varchar' == $typeName)
                {
                    if (
                        $tdd[$xprefix.'fields'][$field]['remove-elements'] ||
                        isset($tdd[$xprefix.'fields'][$field]['strip-tags']) ||
                        $tdd[$xprefix.'fields'][$field]['reconvert-url']
                    ) { 
                        foreach ($tab as $ro => $dat) {
                            if ($r = $tdd[$xprefix.'fields'][$field]['remove-elements'])
                                $tab[$ro][$field] = Text::removeElements($tab[$ro][$field], $r);
                                                                
                            if (isset($tdd[$xprefix.'fields'][$field]['strip-tags']))
                                $tab[$ro][$field] = Text::stripTags($tab[$ro][$field], $tdd[$xprefix.'fields'][$field]['strip-tags']);
                            
                            if ($tdd[$xprefix.'fields'][$field]['reconvert-url'])
                                $tab[$ro][$field] = Router::convert($tab[$ro][$field]);
                        }
                    }
                }
                elseif (in_array($typeName, ['tinytext', 'text', 'mediumtext', 'longtext'])) {
                    if (
                        $tdd[$xprefix.'fields'][$field]['remove-elements'] ||
                        isset($tdd[$xprefix.'fields'][$field]['strip-tags']) ||
                        $tdd[$xprefix.'fields'][$field]['reconvert-url']
                    ) {

                        foreach ($tab as $ro => $dat) {

                            if ($r = $tdd[$xprefix.'fields'][$field]['remove-elements'])
                                $tab[$ro][$field] = Text::removeElements($tab[$ro][$field], $r);
                                
                            if (isset($tdd[$xprefix.'fields'][$field]['strip-tags']))
                                $tab[$ro][$field] = Text::stripTags($tab[$ro][$field], $tdd[$xprefix.'fields'][$field]['strip-tags']);

                            if ($tdd[$xprefix.'fields'][$field]['reconvert-url'])
                                $tab[$ro][$field] = $transformToHhref($tab[$ro][$field]);
                        }
                    }
                }
                elseif ('block' == $typeName) # This should be above "foreach ($tab)" since may be no data types
                {
                    foreach ($tab as $ro => $dat) {
                        if ($dat['rec'] && !isset($_GET['change'])) {
                            if ($editPermissionExists) {
                                if (empty($dat[$field])) {
                                    self::prompt(sprintf(Blox::getTerms('gen-block-id-branch'), '<b>$'.$xprefix.'dat['.$field.']</b>', '<b>'.$tpl.'</b>'), true);                                        
                                    # Gen block Id                                  
                                    if ($generatedBlockId = Admin::genBlockId($srcBlockId, $dat['rec'], $field, $xprefix)) { # extradata block is not generated here
                                        $sql = 'UPDATE '.$tbl.' SET dat'.$field.'='.$generatedBlockId.' WHERE  `block-id`=? AND `rec-id`=?';
                                        if (!isEmpty(Sql::query($sql, [$srcBlockId, $dat['rec']])) )
                                            $dat[$field] = $generatedBlockId;
                                    } else {
                                        Sql::query('DELETE FROM '.self::info('db','prefix').'blocks WHERE id=?', [$generatedBlockId]);
                                        self::error(sprintf(self::getTerms('failed-block-id'), '{tpl:'.$tpl.', src-block-id:'.$srcBlockId.'}'));
                                    }
                                }
                            }
                        }
                        if ($dat[$field]) 
                        {   
                            $childBlockInfo = self::getBlockInfo($dat[$field]);                                            
                            # Assign default template
                            if (empty($childBlockInfo['tpl']) && $typesDetailsB[$field] && !isset($_GET['change']))
                            {
                                $defaultTpl = '';
                                $defaultOption = '';
                                # TODO: Move up of loops \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
                                if ($defaultTpl = $_SESSION['Blox']['templates-from-grandparents'][$srcBlockId][$field]['template']) { # Hello from grandpa
                                    $defaultOption = $_SESSION['Blox']['templates-from-grandparents'][$srcBlockId][$field]['option'];
                                    unset($_SESSION['Blox']['templates-from-grandparents'][$srcBlockId][$field]);
                                }
                                elseif ($typesDetailsB[$field]['params']['template'][0]) {  # DEPRECATED:
                                    $defaultTpl = Files::normalizeTpl($typesDetailsB[$field]['params']['template'][0], $tpl); # Define the path to the template that is in the same folder as tdd file.
                                    $defaultOption = $typesDetailsB[$field]['params']['option'][0];
                                }
                                ///////////////////////////////////////////////////////////////////////

                                # Default template
                                if ($defaultTpl) # i.e. there are additional params
                                {
                                    # Autoassign default template as new, i.e. 'change' not 'delegate'
                                    if ('new' == $defaultOption) {
                                        if (!Admin::assignNewTpl($dat[$field], $defaultTpl))  # $regularId == $srcBlockId
                                            self::prompt(sprintf(self::getTerms('failed-tpl'), '<b>'.$defaultTpl.'</b>', '<b>$tdd[types]['.$field.']</b>', '<b>'.$tpl.'.tdd</b>'), true); # SIMILAR: see below
                                        else {
                                            self::prompt(sprintf(self::getTerms('auto-assign'), '<b>'.$dat[$field].'</b>', '<b>'.$defaultTpl.'</b>'));
                                            # Assigned
                                            # If the page is opened after the assignment, then reload it again to automatically assign templates in nested blocks
                                            # See assign.php too: #Reload...
                                            Url::redirect($pagehref);
                                        }
                                        # In order not to repeat each cycle
                                        if (empty($templatesFromGrandparentsChecked[$field])) {
                                            # defaultTemplates for grandchilds
                                            if ($typesDetailsB[$field]['params'])
                                                foreach ($typesDetailsB[$field]['params'] as $paramName=>$values)
                                                    if ($paramName != 'block' && $paramName != 'template' && $paramName != 'option')
                                                        if ('field' == substr($paramName, 0, 5))
                                                            if ($childField = substr($paramName, 5))
                                                                $_SESSION['Blox']['templates-from-grandparents'][$dat[$field]][$childField] = ['template' => Files::normalizeTpl($values[0], $tpl), 'option'=>$values[1]];
                                            $templatesFromGrandparentsChecked[$field] = true;
                                        }
                                    }
                                    # Autoassign default template with 'delegate' option
                                    elseif ('delegate' == $defaultOption) {
                                        # Find the first block with the same tpl
                                        $sql = "SELECT id FROM ".self::info('db','prefix')."blocks WHERE tpl=? LIMIT 1";
                                        if ($result = Sql::query($sql, [$defaultTpl])) {
                                            # If block with this template already exists
                                            if ($row = $result->fetch_assoc()) {
                                                $result->free();
                                                $lastDelegated = Admin::getLastDelegatedBlock($defaultTpl);
                                                if (empty($lastDelegated)) # No delegated blocks with the same tpl. Take the first available block
                                                    $lastDelegated = $row['id'];
                                                if (!Admin::delegate($dat[$field], $lastDelegated))
                                                    self::prompt(sprintf(self::getTerms('failed-delegating'), $lastDelegated.'('.$defaultTpl.')', $dat[$field]), true); 
                                            }
                                            # If no block with the same template yet, automatically assign the new template
                                            else {
                                                if (!Admin::assignNewTpl($dat[$field], $defaultTpl))  # $regularId == $srcBlockId
                                                    self::prompt(sprintf(self::getTerms('failed-tpl'), '<b>'.$defaultTpl.'</b>', '<b>$tdd[types]['.$field.']</b>', '<b>'.$tpl.'.tdd</b>'), true); # SIMILAR: see above
                                                else
                                                    self::prompt(sprintf(self::getTerms('auto-assign'), '<b>'.$dat[$field].'</b>', '<b>'.$defaultTpl.'</b>'));
                                            }
                                            $result->free();
                                        }
                                    } else {
                                        # Such template does not exist on the website
                                        if (!(
                                            file_exists(self::info('templates', 'dir').'/'.$defaultTpl.'.tpl') ||
                                            file_exists(self::info('templates', 'dir').'/'.substr($defaultTpl, 0, -1 )) # Folder # remove trailing slash
                                        ))   self::prompt(sprintf(self::getTerms('no-auto-tpl'), '<b>'.$tpl.'</b>', $field, '<b>'.$defaultTpl.'</b>'));
                                    }
                                }
                            }
                            # Regular output of the block
            	    		$tab[$ro][$field] = self::getBlockHtm($dat[$field], $childBlockInfo, $xprefix);
                            $tab[$ro]['blocks'][$field] = $dat[$field];
                        }
                    }
                }
                elseif ('page' == $typeName)
                {
                    foreach ($tab as $ro => $dat)
        	    	{
                        # Assign the page number and the template for the outer block
                        if ($editPermissionExists) {
                            if (empty($dat[$field])) {
                                $outerBlockId = Admin::genBlockId();
                                $sql = "INSERT ".self::info('db','prefix')."pages (`outer-block-id`) VALUES (?)";
                                $num = Sql::query($sql, [$outerBlockId]);
                                if ($num > 0) {
                                    $newPageId = Sql::getDb()->insert_id;
                                    $sql = "UPDATE $tbl SET dat{$field}='$newPageId' WHERE `block-id`=? AND `rec-id`=?";
                                    Sql::query($sql, [$srcBlockId, $dat['rec']]);
                                    # Parent page
                                    $parentPageId = 0;
                                    $sql = "SELECT dat$field FROM $tbl WHERE `block-id`=? LIMIT 2";
                                    if ($result = Sql::query($sql, [$srcBlockId])) {
                                        while ($row = $result->fetch_row()) {
                                            # = targetPageId
                                            if ($row[0]) {
                                                $pageInfo = Router::getPageInfoById($row[0]);
                                                if ($pageInfo['parent-page-id']) {
                                                    $parentPageId = $pageInfo['parent-page-id'];
                                                    break;
                                                }
                                            }
                                        }
                                        $result->free();
                                    }
                                    if (empty($parentPageId))
                                          $parentPageId = self::getPageId();
                                    $tab[$ro][$field] = $newPageId;
                                    $sql = "UPDATE ".self::info('db','prefix')."pages SET `parent-page-id`=? WHERE id=?";
                                    Sql::query($sql, [$parentPageId, $newPageId]);
                                    # Default template as new 
                                    if ($typesDetailsP[$field]['params']['template'][0]) {
                                        # Autoassign default template as new. #autoassignment-of-the-page
                                        if ('new' == $typesDetailsP[$field]['params']['option'][0]) {
                                            $defaultTpl = Files::normalizeTpl($typesDetailsP[$field]['params']['template'][0], $tpl);
 
                                            if (!Admin::assignNewTpl($outerBlockId, $defaultTpl))  # $outerBlockId == $srcBlockId
                                                self::prompt(sprintf(self::getTerms('failed-tpl-for-outer'), '<b>'.$defaultTpl.'</b>', '<b>$tdd[types]['.$field.']</b>', '<b>'.$tpl.'.tdd</b>'), true);
                                            else
                                                self::prompt(sprintf(self::getTerms('auto-assign-for-outer'), '<b>'.$outerBlockId.'</b>', '<b>'.$dat[$typesDetailsP[$field]['params']['doctitlefield'][0]].'</b>', '<b>'.$defaultTpl.'</b>'));
                                            # defaultTemplates for grandchilds
                                            # In order not to repeat each loop
                                            if (empty($templatesFromGrandparentsChecked[$field])) {
                                                if ($typesDetailsP[$field]['params'])
                                                    foreach ($typesDetailsP[$field]['params'] as $paramName=>$values)
                                                        if ($paramName != 'page' && $paramName != 'template' && $paramName != 'option' && $paramName != 'unset')
                                                            if ('field' == substr($paramName, 0, 5))
                                                                if ($childField = substr($paramName, 5))
                                                                    $_SESSION['Blox']['templates-from-grandparents'][$outerBlockId][$childField] = ['template'=> Files::normalizeTpl($values[0], $tpl), 'option'=>$values[1]];
                                                $templatesFromGrandparentsChecked[$field] = true;
                                            }
                                        }
                                    }
                                }
                                else
                                    self::error("Page ID was not generated by sql:<br />".$sql."<br />in Blox::getBlockHtm()");
                            }
                        }
                        else # Not the editor of the block
                        {
                            $pageInfo = Router::getPageInfoById($dat[$field]);            
                            if ($pageInfo['page-is-hidden']) {
                                if (!self::info('user','id')) {
                                    unset($tab[$ro][$field]); # Hide the link
                                } elseif (self::info('user','user-as-visitor')) {
                                    self::prompt(sprintf(self::getTerms('hide-link'), '<b>'.$dat[$field].'</b>('.Router::convert('?page='.$dat[$field]).')', '<b>'.$srcBlockId.'</b> ('.$tpl.')'));
                                    unset($tab[$ro][$field]); # Hide the link
                                } else { # User
                                    if (!self::info('user','user-is-activated'))
                                        unset($tab[$ro][$field]); # Hide the link
                                    else {
                                        if (!Admin::userIsEditorOfHiddenPage($dat[$field], $tdd[$xprefix.'params'])) {
                                            if (!Proposition::get('user-sees-hidden-page', self::info('user','id'), $dat[$field]))
                                                unset($tab[$ro][$field]);# Hide the link
                                        }
                                    }
                                }
                            }
                        }
                    }
                } 
            }

            # templates-from-grandparents
            if ($editPermissionExists && $_SESSION['Blox']['templates-from-grandparents']) {
                Store::set('templates-from-grandparents', $_SESSION['Blox']['templates-from-grandparents']);
            }
        }
    }

 

    private static function getEditButtonStyle($regularId, $tddStyle='', $settingsStyle=[])
    {
        $props1 = $props2 = $props3 = [];
        if ($tddStyle) {
            # Default style
            $props1 = ['display'=>'block!important', 'position'=>'absolute!important', 'top'=>'0', 'left'=>'0', 'padding-top'=>'1px!important'];
            # Style from tdd
            if (is_string($tddStyle)) {
                if ($z = explode(';', strtolower(trim($tddStyle, ';'))))
                    foreach ($z as $s) {
                        if ($x = explode(':', $s))
                            $props2[trim($x[0])] = $x[1];
                }
            }
        }   
        # Style from DB
        if ($settingsStyle) {
            foreach ($settingsStyle as $k=>$v) {
                if (!isEmpty($v)) {
                    if (in_array($k, ['top','left']))
                        $v.='px';
                    $props3[$k] = $v;
                }
            }
        }
        #
        $style = '';
        if ($props = Arr::mergeByKey($props1, $props2, $props3)) {
            foreach ($props as $k=>$v)
                $style.= ';'.$k.':'.$v;
        }
//if (131==$regularId)
//qq($style);
        return trim($style, ';');
    }
 

    /**
     * @deprecated since 14.2.5. Use Tdd::getByJson()
     */
    public static function getByJson($json)
    {
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
                    if ($options['keys'])
                        return Arr::getByKeys($_SESSION, $options['keys']);
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