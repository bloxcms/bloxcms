<?php

/**
 * @todo Create method for parent keys store
 * @todo $options['refresh'] = true  for convert() #NEW if you change the name or title automatically change the:  name, title, alias (rather simply delete them as in nav-pills-paragraphs/nav-pills.tuh )		---update
 */


class Router
{
    private static 
        $pageNewInfo = [],
        $uniqueAliases = [],
        $uniqueAliasParentId,
        $uniqueAliasCounter = 0,
        $truncateAliasCounter = 0,
        $errorCounters = [],
        $currPageBreadcrumbs = []
    ;
    
    /**
     * Convert parametric URL to human friendly URL.
     *
     * @param string $phref Base relative parametric URL (?page=2&block=47&p[1]=1).
     * @param array $options
     *      ....
     *      string $xhref Additional parametric tail.
     *      array $url-params Array of the names of non-standard parameters that can be used in $phref.
     * @return string
     * 
     * @ todo Home page and xhref problem: If there is '?page=1', it should be removed, but in the argument it should be presented
     *       --'$phref'+'$xhref'--
     *       '?page=1'+''                                --> ''
     *       '?page=1#myform'                            --> '#myform'
     *       '?page=1'+'#myform'                         --> '#myform'
     *       '?page=1#form1'+'#form2'                    --> '#form2'
     *       '?page=1&block=2&part=3#form1'+'#form2'     -->  '?page=1&block=2&part=3#form2'
     *
     *       Solution: Put hash outside of Router::convert()
     */
    public static function convert($phref, $infos=[])
    {
        # Input control
        $conversionError = '';
        if (empty($phref)) {
            if ($infos)
                $conversionError = Blox::getTerms('first-arg-is-empty');
        } elseif ($phref[0] != '?') {
            # This is a secondary human URL
            if (!$infos) #KLUDGE
                return $phref;
            else
                $conversionError = sprintf(Blox::getTerms('should-be-relative-parametric-url'), '<b>'.$phref.'</b>'); 
        } elseif (mb_strtolower($phref) == '?page=1') { # Home page
            if (!$infos) #KLUDGE
                return '';
        }
        
        # Other data to find a code
        if ($conversionError) {
            if ($infos) {
                foreach($infos as $k => $v)                
                    if ($v)
                        $aa .= ", $k: $v";
            }
            #
            if ($aa) {
                $aa = '. '.sprintf(Blox::getTerms('other-inputs'), '{'.substr($aa, 1).'}');
            }
            $conversionError .= '. '.Blox::getTerms('search-mark');
            Blox::prompt($conversionError.' '.$aa,  true);
        }

        # Initial infos
        $aa = $infos['name'] ?: ($infos['title'] ?: ($infos['alias'] ?: ''));
        if (!$infos['name'])
            $infos['name']  = $aa;
        if (!$infos['title'])
            $infos['title'] = $aa;
        if (!$infos['alias'])
            $infos['alias'] = $aa;
        
        $specParams = self::getPhrefParams($phref, $afterHash); # Resort URL params in standard order

        $basePhref = '';
        if ($specParams['page']['value']) {
            /** #temp
            if ($specParams['page']['value'] == 1) # KLUDGE: As unconverted links on the home page with pagination in the human URL mode do not work. "?page=1&block=178&part=2" --> "2/"  --> Do not find
                return $phref;
            else
                */
                $basePhref .= '?page='.$specParams['page']['value']; 
        } else {
            self::addErrorPrompt('page', $phref);
            return $phref;
        }
        
        # 'src' has priority over 'block'
        if (isset($specParams['src'])) 
            $srcParam = 'src';
        elseif (isset($specParams['block'])) 
            $srcParam = 'block';
        
        if ($srcParam) {
            if ($specParams[$srcParam]['value']) {
                $blockQuery = $specParams[$srcParam]['amp'].$srcParam.'='.$specParams[$srcParam]['value'];
                # This is a regular page '?page=1&block=2&part=3'  or '?page=1&block=2'. The last one is used for parametric tails in human links
                if (empty($specParams['rest']) && empty($specParams['single']))    
                    $onlyBlockIsRequested = true;# Regular page, for example search request, submitted via post
                else
                    $basePhref .= $blockQuery;
            } else { # If empty '&block=' then quit
                self::addErrorPrompt($srcParam, $phref);
                return $phref;
            }
        }
            
        $basePhref .= $specParams['rest']['value'];
        

        if (isset($specParams['part'])) {
            if ($specParams['part']['value'])
                $partQuery = $specParams['part']['amp'].'part='.$specParams['part']['value'];
            else {
                self::addErrorPrompt('part', $phref);
                return $phref;
            }
        }

        if (isset($specParams['single'])) {
            if ($specParams['single']['value'])
                $singleQuery = $specParams['single']['amp'].'single='.$specParams['single']['value'];
            else {
                self::addErrorPrompt('single', $phref);
                return $phref;
            }
        }
            
        
        $partfreePhref = $basePhref.$singleQuery; # Without 'part'


        # Primary pseudpage, i.e. we should update pseudpages table
        if ($infos['key'] && isset($infos['parent-key'])) {
            if ($infos['key']) {
                /*
                 * Check for standard and allowed params.
                 * Standard params:
                 *    Treated above: ['page', 'block', 'part', 'single'] 
                 *    $stdRestParams=['backward', 'limit', 'p', 'pick', 's', 'search', 'sort','highlight','fields','what','where']
                 *    Can be used:   ['script','template','selected-user-id','src']
                 *    No need:       ['action','file','pagehref','rec','code']
                 */
                $unAllowedParamExist = false;
                if ($specParams['rest']['value']) {
                    $stdRestParams = ['backward', 'limit', 'p', 'pick', 's', 'search', 'sort','highlight','fields','what','where'];
                    if ($infos['url-params'])//allowed-params
                        $stdRestParams = array_merge($stdRestParams, $infos['url-params']);
                    foreach ($specParams['rest']['names'] as $p) {
                        if (!in_array($p, $stdRestParams)) { //$p && 
                            $unAllowedParamExist = true;
                            Blox::prompt(sprintf(Blox::getTerms('nonstandard-param'), '<b>'.$p.'</b>'), true);
                            break;
                        }
                    }   
                }
                
                if (!$unAllowedParamExist) {
                    #############################  Select  #############################
                    $pseudopageOldInfos = self::selectPseudopage($infos['key']);

                    #extras: 'parent-key'                
                    if ($infos['parent-key'] !==null) {
                        ; # Do not analize if new parents info passed. New info is prior. "" id for regular page.
                    } elseif ($pseudopageOldInfos['parent-page-is-adopted']) { # If a parent is adopted manually, do not touch it.
                        if ($pseudopageOldInfos['parent-key']===null)
                            Blox::prompt(Blox::getTerms('no-parent-page-info'),  true);
                        unset($infos['parent-key']);
                    } elseif ($pseudopageOldInfos['parent-key']!==null) { # If there was old parents
                        ;
                    } elseif ($specParams['single']['value']) { # If there is a "single" query, then page without "single" is the parent
                        $parentPageInfo = self::selectPseudopageByPhref($basePhref);
                        $infos['parent-key'] = $parentPageInfo['key'];
                    }
                    # Update infos in pseudopages
                    $sqlValues = [];
                    # Insert
                    if (empty($pseudopageOldInfos)) {
                        $setSql = "`phref`=?";
                        $sqlValues[] = $partfreePhref;
                        if ($infos) {
                            foreach (['key', 'parent-key', 'name', 'title', 'alias'] as $k) {
                                if ($v = $infos[$k]) {
                                    if ($k == 'alias') {  
                                        $v = Str::sanitizeAlias($v, Blox::info('site', 'transliterate'));
                                        $v = self::makeUniqueAlias($v, true, $infos['key'], $infos['parent-key']);
                                    }
                                    if ($k == 'title') {
                                        $v = Text::stripTags($v);
                                    }
                                    $setSql .= ", `$k`=?";
                                    $sqlValues[] = $v;
                                }
                            }
                        }
                        $sql = 'INSERT INTO '.Blox::info('db','prefix').'pseudopages SET '.$setSql;
                        if (Sql::query($sql, $sqlValues)===false)
                            Blox::prompt(Blox::getTerms('pseudopages-insert-error'),  true);
                    }
                    # Update
                    //elseif ($infos['key']) # as the key is used in WHERE clause       2018-09-07 20:33
                    elseif (empty($pseudopageOldInfos['alias'])) {
                        $setSql = '';
                        if ($partfreePhref != $pseudopageOldInfos['phref']) {
                            $setSql .= ", phref=?";
                            $sqlValues[] = $partfreePhref;
                        }
                        #
                        if ($infos) {
                            foreach (['parent-key', 'alias'] as $k) {
                                $v = $infos[$k];
                                if ($v !== null && $pseudopageOldInfos[$k] !== $v) {
                                    if ($k == 'alias') {
                                        $v = Str::sanitizeAlias($v, Blox::info('site', 'transliterate'));
                                        $v = self::makeUniqueAlias($v, true, $infos['key'], $infos['parent-key']);
                                    }
                                    #
                                    $setSql .= ", `$k`=?";
                                    $sqlValues[] = $v;
                                
                                }
                            }
                            /* variant 2
                            foreach (['parent-key', 'name', 'title', 'alias'] as $k) {  # as the 'key' is used in WHERE clause
                                $v = $infos[$k];
                                if ($v !== null) {
                                    $update = false;
                                    if ($k == 'parent-key') {
                                        if ($pseudopageOldInfos[$k] !== $v)
                                            $update = true;
                                    } elseif ($k == 'alias') {
                                        if ($pseudopageOldInfos[$k] !== $v) {
                                            $update = true;
                                            $v = Str::sanitizeAlias($v, Blox::info('site', 'transliterate'));
                                            $v = self::makeUniqueAlias($v, true, $infos['key'], $infos['parent-key']);                                        
                                        }
                                    } elseif ($k == 'name') {
                                        if (!$pseudopageOldInfos[$k])
                                            $update = true;
                                    } elseif ($k == 'title') {
                                        if (!$pseudopageOldInfos[$k]) {
                                            $update = true;
                                            $v = Text::stripTags($v);
                                        }
                                    }
                                    #
                                    if ($update) {
                                        $setSql .= ", `$k`=?";
                                        $sqlValues[] = $v;
                                    }
                                }
                            }
                            */
                        }
                        #
                        if ($setSql = substr($setSql, 2)) {  # remove initial ', '
                            $sql = 'UPDATE '.Blox::info('db','prefix').'pseudopages SET '.$setSql." WHERE `key`=?";
                            $sqlValues[] = $infos['key'];
                            $result = Sql::query($sql, $sqlValues);
                            #TODO: Why $result is false?
                            if ($result===false) { # KLUDGE: if $pseudopageOldInfos are not changed, do not update
                                Blox::error(sprintf(Blox::getTerms('pseudopages-update-error'), $sql));
                            }
                            
                        }
                    }
                }
            }
        }
        # Regular page or secondary pseudopage
        elseif ($infos['name'] || $infos['title'] || $infos['alias'] || $onlyBlockIsRequested) # If it is a regular page, update $infos2 too
        {
            $pageId = $specParams['page']['value'];
            if ($pageId)
            {
                # Update only $infos2
                if ($pseudopageOldInfos = self::getPageInfoById($pageId))
                {   
                    $sqlValues = [];            
                    $setSql = '';
                    foreach (['name','title','alias'] as $k) {
                        if ($v = $infos[$k]) {
                            if (empty($pseudopageOldInfos[$k])) { # once recorded, no longer necessary
                                if ($k == 'alias') {
                                    $v = Str::sanitizeAlias($v, Blox::info('site', 'transliterate'));
                                    $v = self::makeUniqueAlias($v, false, $infos['key'], $pseudopageOldInfos['parent-page-id']);
                                }
                                $setSql .= ", `$k`=?";
                                $sqlValues[] = $v;
                            }
                        }
                    }

                    # This is the main content block on the page to which direct requests are made via URL
                    if ($onlyBlockIsRequested) {
                        if ($pseudopageOldInfos['main-block-id']) { # there was an old one
                            if ($pseudopageOldInfos['main-block-id'] != $specParams['block']['value']) { # differ
                                $setSql .= ', `main-block-id`=?';
                                $sqlValues[] = $specParams['block']['value'];
                                Blox::prompt(sprintf(Blox::getTerms('main-block-id-is-changed'), $pseudopageOldInfos['main-block-id'], $specParams['block']['value']),  true);
                            }
                        }
                        # there was not an old one
                        else { 
                            $setSql .= ', `main-block-id`=?';
                            $sqlValues[] = $specParams['block']['value'];
                        }
                    }

                    if ($setSql) {
                        if ($setSql = substr($setSql, 2)) { # remove initial ', '
                            $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET '.$setSql.' WHERE `id`=?';
                            $sqlValues[] = $pageId;
                            if (isEmpty(Sql::query($sql, $sqlValues)))
                                Blox::prompt(Blox::getTerms('pages-update-error'),  true);
                        }
                    }
                }
            }
        }

        # Return href (human or parametric)
        if (Blox::info('site','human-urls','convert')) {
            if ($breadcrumbs = self::getBreadcrumbs($partfreePhref, $specParams, $notConvertedToHhref)) {
//qq($breadcrumbs);
                $lastBreadcrumb = end($breadcrumbs); # The last element of the chain is our link
                $href = $lastBreadcrumb['href'];
                if ($specParams['part']['value']) { # No part in request
                    if ($specParams['single']['value']) { # If there are both part and single, insert part num before the last alias, related to "single". Although it is better to use "single" without "part".
                        $prevElement = prev($breadcrumbs); # The last element of the chain is our link
                        $href = $prevElement['href'];
                        $href .= $specParams['part']['value'].'/';
                        $href .= $lastBreadcrumb['alias'].'/';                     
                    } else
                        $href .= $specParams['part']['value'].'/';
                }
                if ($infos['xhref'])
                    $href .= '?'.$infos['xhref']; # Do not urlencode, because there are not only values
            } else
                $notConvertedToHhref = true; # parametric mode # failed attempt - not enough data
        } else { # parametric mode
            $notConvertedToHhref = true;
        }        
        #
        if ($notConvertedToHhref) { # parametric mode
            if ($onlyBlockIsRequested) 
                $href = $basePhref.$blockQuery.$partQuery; # Single block param is necessary for search request via form
            else 
                $href = $basePhref.$partQuery.$singleQuery;
            
            if ($infos['xhref']) 
                $href .= ($href ? '&' : '?').$infos['xhref'];
            
            //$href = urlencode($href);  
        }
        
        if ($afterHash && mb_strpos($infos['xhref'], '#')===false) # If there is hash in xhref, use it but not "afterHash"
            $href .= '#'.urlencode($afterHash);
        elseif ($conversionError)
            $href .= '#conversionError';
        return $href;
    }






   /**
    * 
    * When you load a lot of records in one process, this function controls uniqueness good. But when you run convert() at another time or change an alias, you should search in db too.
    *
    * @param string $alias
    * @param bool $isPseudoPage
    * @param string $key Do not remove. Added because parents can not exist
    * @param string $pk - parent-key (if isPseudoPage) OR $parentPageId
    */
    private static function makeUniqueAlias($alias, $isPseudoPage, $key, $pk)
    {
        $parentId = $key.'-'.$pk; # arbitrary parent id

        # There is no parent or another parent is taken
        if (empty(self::$uniqueAliasParentId) || self::$uniqueAliasParentId != $parentId) { 
            self::$uniqueAliasParentId = $parentId;
                
            # get Aliases From Db once per parent
            if ($isPseudoPage) {
                if ($pk !== null) {
                    $sql = 'SELECT `alias` FROM '.Blox::info('db','prefix').'pseudopages WHERE `parent-key`=? AND `key`=?';
                    if ($result = Sql::query($sql, [$pk, $key])) {
                        while ($row = $result->fetch_row())
                            $aliases[$row[0]] = true;
                        $result->free();
                    }
                }
            } elseif ($pk) { # not pseudopage
                $sql = 'SELECT `alias` FROM '.Blox::info('db','prefix').'pages WHERE `parent-page-id`=?';
                if ($result = Sql::query($sql, [$pk])) {
                    while ($row = $result->fetch_row())
                        $aliases[$row[0]] = true;
                    $result->free();
                }
            }
            self::$uniqueAliases = $aliases;
        }
        self::$uniqueAliasCounter = 0;
        $alias = self::uniquizeAlias($alias);
        self::$uniqueAliases[$alias] = true; # Add new alias to control list
        return $alias;
    }
    
    /**
     * For Router::makeUniqueAlias()
     */
    private static function uniquizeAlias($alias)
    {
        if (self::$uniqueAliasCounter > 40) { # this maybe an infinite loop
            self::$uniqueAliasCounter = 0;
            Blox::prompt(sprintf(Blox::getTerms('infinite-loop-in-uniquize-alias'), $alias),  true);
            return $alias; # Infinite loop protection
        }
        self::$uniqueAliasCounter++;     
        
        self::$truncateAliasCounter = 0;
        $tAlias = self::truncateAlias($alias);
        if (self::$uniqueAliases[$tAlias]) # Such an alias already exists in the list of aliases
        {
            # Append a postfix '-1' or increase the number. The length of the alias will increas at least two signs
            $parts = Str::splitByMark($tAlias, '-', true);
            if ($parts[1]) {                        
                if (Str::isInteger($parts[1])) { # digit one or more times in the end  
                    $uAlias = $parts[0].'-'.($parts[1] + 1);
                } else
                    $uAlias = $tAlias.'-1';
            } else
                $uAlias = $tAlias.'-1';
            $uAlias = self::uniquizeAlias($uAlias);
        } elseif ($tAlias != $alias)
            $uAlias = self::uniquizeAlias($tAlias);
        else
            $uAlias = $alias;
        return $uAlias;
    }

    private static function truncateAlias($alias)
    {
        $maxLength = 332; # $max Length of alias. Alias stored in varchar(332) columns
        
        if (self::$truncateAliasCounter > 40) { # this maybe an infinite loop
            self::$truncateAliasCounter = 0;
            Blox::prompt(sprintf(Blox::getTerms('infinite-loop-in-truncate-alias'), $alias),  true);
            return $alias;  # Infinite loop protection
        }
        self::$truncateAliasCounter++;
        
        # If alias is to long, truncate from the end.         
        $strLength = mb_strlen($alias);
        if ($strLength > $maxLength) {
            $parts = Str::splitByMark($alias, '-', true);
            if ($parts[1]) {                        
                if (Str::isInteger($parts[1])) # digit one or more times in the end  
                    $tAlias = truncateAlias($parts[0]); # It is better to throw out digits, because there may be an infinite loop. The case when $parts[0] is empty is impossible, because margin hyphens are removed
                else
                    $tAlias = $parts[0];
            } else
                $tAlias = substr($alias, 0, $maxLength-3);
        } else
            $tAlias = $alias;

        return $tAlias;
    }
    
    

    


    
    /** 
     * @todo http://www.phpbuilder.com/articles/databases/mysql/handling-hierarchical-data-in-mysql-and-php.html
     *    $result = Sql::query("SELECT c1.parent_id,c2.category_name AS parent_name FROM category AS c1
     *    LEFT JOIN category AS c2 ON c1.parent_id=c2.category_id 
     *    WHERE c1.category_id='$category_id' ");
     */
    public static function getBreadcrumbs($phref=null, $specParams=null, &$notConvertedToHhref=false)
    {
        $notConvertedToHhref = false;
        if ($phref===null) { # Current page
            if (self::$currPageBreadcrumbs)
                return self::$currPageBreadcrumbs;
            $phref = self::convertUrlToPartFreePhref(Blox::getPageHref(), $specParams);
        }

        if ($phref)
        {
            if ($specParams === null) # get $specParams if $_SERVER['REQUEST_URI'] was human
                $specParams = self::getPhrefParams($phref);
            # pageId
            if ($pageId = $specParams['page']['value'])
                ;
            elseif (preg_match('~\Wpage=(\d+)~', $phref, $matches))
                $pageId = $matches[1];
            /* 2017-09-09
            # `block-id`
            if ($blockId = $specParams['block']['value'])
                ;
            elseif (preg_match('~\Wblock=(\d+)~', $phref, $matches))
                $blockId = $matches[1];
            */

            if ($pageId && $breadcrumbs = self::getRegularBreadcrumbs($pageId))
            {
                # If pseudopage
                if ($specParams['single'] || $specParams['rest']) {
                    if ($breadcrumbs2 = self::getPseudoBreadcrumbs($phref))
                        $breadcrumbs = array_merge($breadcrumbs, $breadcrumbs2);
                    else
                        return false; # In the url there are params, but this url is not registered.
                }
                

                if (Blox::info('site','human-urls','convert'))
                {
                    $hhref = '';
                    foreach ($breadcrumbs as $k => $breadcrumb) {
                        if ($k==0) # first item (home) has empty alias
                            continue;
                        # Build a hhref from breadcrumb
                        if ($breadcrumb['alias'] && !$notConvertedToHhref) {
                            $hhref .= $breadcrumb['alias'].'/';  # human href
                            $breadcrumbs[$k]['href'] = $hhref;
                        } else {
                            $breadcrumbs[$k]['href'] = $breadcrumb['phref'];
                            if (!$notConvertedToHhref) {
                                $notConvertedToHhref = true;
                                Blox::prompt(sprintf(Blox::getTerms('no-alias-for-page'), '<a target="_blank" href="'.$breadcrumb['phref'].'">'.$breadcrumb['phref'].'</a>'));
                            }
                        }
                    }
                }
                # For optimization
                if ($phref===null) # Current page
                    self::$currPageBreadcrumbs = $breadcrumbs;
                        
                return $breadcrumbs;
            }
        }
    }

    
    public static function hrefIsAncestor($href)
    {
        if (empty($href))
            return;
        if ($href[0] == '?') # is parametric
            $parametric = true;
        if ($breadcrumbs = self::getBreadcrumbs()) {
            foreach ($breadcrumbs as $breadcrumb) {   
                $hh = ($parametric) ? $breadcrumb['phref'] : $breadcrumb['href'];                
                if ($hh == $href)
                    return true;
            }
        }
    }
    
    
    

    private static function getPseudoBreadcrumbs($phref)
    {
        $breadcrumbs = [];        
        if ($row = self::selectPseudopageByPhref($phref)) {
            if ($row['parent-key']===null) # Parent is not defined. "" means level=1
                return;
            $breadcrumbs[] = [
                'name'  => $row['name'],
                'title' => $row['title'],
                'alias' => $row['alias'],
                'phref' => $row['phref'],
                'href'  => $row['phref']
            ];
        }
        while ($row['parent-key']) {
            if ($row = self::selectPseudopage($row['parent-key'])) {
                if ($row['parent-key']===null) # Parent is not defined. "" means level=1
                    return;
                $breadcrumbs[] = [
                    'name'  => $row['name'],
                    'title' => $row['title'],
                    'alias' => $row['alias'],
                    'phref' => $row['phref'],
                    'href'  => $row['phref']
                ];
            } else # The link with these keys is not registered, although it is already named as a parent. Most likely it is navigation using filters.
                return; 
        }
        if ($breadcrumbs)
            $breadcrumbs = array_reverse($breadcrumbs);

        return $breadcrumbs;
    }




    private static function getRegularBreadcrumbs($pageId)
    {
        $breadcrumbs = [];
        while ($pageId > 0) {
            $pageInfo = self::getPageInfoById($pageId);
            $phref = ($pageId > 1) ? '?page='.$pageId : '';
            $breadcrumbs[] = [
                'name'  => $pageInfo['name'],
                'title' => $pageInfo['title'],
                'alias' => $pageInfo['alias'],
                'phref'  => $phref,
                'href'  => $phref
            ];
            $pageId = $pageInfo['parent-page-id'];
        }
        if ($breadcrumbs)
            $breadcrumbs = array_reverse($breadcrumbs);
        return $breadcrumbs;
    }





    public static function selectPseudopage($key)
    {
        if ($key) {
            $sql = 'SELECT * FROM '.Blox::info('db','prefix').'pseudopages WHERE `key`=?';
            if ($result = Sql::query($sql, [$key]))
                return $result->fetch_assoc();
            #$result->free();
        }
    }

    public static function selectPseudopageByPhref($phref)
    {
        if ($phref) {
            $sql = 'SELECT * FROM '.Blox::info('db','prefix')."pseudopages WHERE `phref`=?";//.' LIMIT 1'
            if ($result = Sql::query($sql, [$phref]))
                return $result->fetch_assoc();
        }
    }





    public static function hrefIsParametric($href)
    {
        /**
         * @todo Check more strictly
         */
        if ($href[0] == '?')
            return true;
    }






    /**
     * "part" is not included in the href. If you want insert "part" separately in the end or before "single"
     */
    private static function convertHhrefToPartFreePhref($hhref, &$part=null, $redirectIfNotFound=null)
    {
        $aliases = explode('/', $hhref);
        # If there is slash in the end of hhref, the last element will be empty. But we check it twice for reliability
        if ($lastAlias = array_pop($aliases)) {
        	if ($lastAlias[0] == '?') # additional parametric tail 
                $lastAlias = array_pop($aliases);
            else # wrong hurl without trailing slash 
                $redirectToTrailingSlashHurl = true;
        } else { # normal hurl with trailing slash 
			$lastAlias = array_pop($aliases); # The array $aliases decreases
        }

        /**
         * If the alias consists of digits, then this is "part", which is not stored in the database
         * This algorithm leads to part written in the end of the url, even for single-query. To prevent this you have to determine whether the request is single.
         */
        if (Str::isInteger($lastAlias)) { # Digits
            $part = $lastAlias;
            $lastAlias = array_pop($aliases); # The array $aliases decreases
            //$redirectToTrailingSlashHurl = false; 2017-01-30
        } else { # "part" is missing in the end, but it can be the penultimate, if you are using "single"
            /** Method #1. Better if "single" query is used without "part" */
            $penultimateAlias = end($aliases); # Take the penultimate link of the chain
            if (Str::isInteger($penultimateAlias)) {
                $part = $penultimateAlias;
                array_pop($aliases);
            }
            /** Method #2. Better if used both "single" and "part" &part=2&single=99
            $penultimateAlias = array_pop($aliases);            
            if (Str::isInteger($penultimateAlias))            
                $part = $penultimateAlias;            
            else
                $aliases[] = $penultimateAlias;
            */            
        }
        if ($redirectToTrailingSlashHurl && $redirectIfNotFound)
            Url::redirect($hhref.'/','exit');
        # "part" can be before "single"
        # The table "pages" is checked first, as it is much less than pseudopages
    	$sql = 'SELECT `id`, `parent-page-id`, `main-block-id` FROM '.Blox::info('db','prefix').'pages WHERE `alias`=?';
        if ($pagesRows = Sql::select($sql,[$lastAlias])) {
        	foreach ($pagesRows as $pageInfos) {
        		if (self::checkPageForAliases($pageInfos, $aliases)) {
                    $partFreePhref = '?page='.$pageInfos['id'];                    
                    if ($part && $pageInfos['main-block-id'])
                        $partFreePhref .= '&block='.$pageInfos['main-block-id'];
        			return $partFreePhref;
                }
            }
        }        
    	# pseudopages
        $sql = 'SELECT `phref`, `parent-key` FROM '.Blox::info('db','prefix').'pseudopages WHERE `alias`=?';  
        if ($pseudopagesRows = Sql::select($sql,[$lastAlias])) {
        	foreach ($pseudopagesRows as $pseudopageInfos){
				if (self::checkPseudopageForAliases($pseudopageInfos, $aliases, $part)) {
        			return $pseudopageInfos['phref'];        
                }
            }
        }
    }
    
    


    private static function checkPageForAliases($pageInfos, $parentAliases)
    {
		if ($pageInfos['parent-page-id'] == 1 && empty($parentAliases))
    		return true; # Reached the top
		if ($lastAlias = array_pop($parentAliases)) {	
	        $sql = "SELECT `parent-page-id` FROM ".Blox::info('db','prefix')."pages WHERE `alias`=? AND `id`=?";
		    if ($result = Sql::query($sql, [$lastAlias, $pageInfos['parent-page-id']])) {
    		    if ($pageInfos = $result->fetch_assoc()) {
                    $result->free();
    	        	if (self::checkPageForAliases($pageInfos, $parentAliases))
    	        		return true;
                }
                $result->free();
            }
	    }
    }
    
        



    private static function checkPseudopageForAliases($pseudopageInfos, $parentAliases, &$part=null)
    {
        # pages
		if ($pseudopageInfos['parent-key']=='') # Reached the top of pseudopages table
		{
			if (array_key_exists('parent-key',$pseudopageInfos))
			{
				/* $pseudopageInfos = 
			    [href] => ?page=2&block=14&p[1]=1&p[2]=0
			    [parent-key] => ''
			    */
			    # Get page id 
		        if (preg_match('~\Wpage=(\d+)~', $pseudopageInfos['phref'], $matches)) {
		            if ($pageId = $matches[1]) {
                        if (1 == $pageId) { # Request on home page
                            return true; # ?page=1&block=14&p[1]=1
						} elseif ($lastAlias = array_pop($parentAliases)) {	
					        $sql = "SELECT `parent-page-id` FROM ".Blox::info('db','prefix')."pages WHERE `alias`=? AND `id`=?";
						    if ($result = Sql::query($sql, [$lastAlias, $pageId])) {
    						    if ($pageInfos = $result->fetch_assoc()) {	
                                    $result->free();
    					        	if (self::checkPageForAliases($pageInfos, $parentAliases))
    					        		return true;
    					        }
                                $result->free();
                            }
					    }
		            } else
		            	return;
		        } else
		            return;
                return;
    			# Reached the top, go to pages table
    		} else
    			return;
    	}
    	elseif ($pseudopageInfos['parent-key']===null) {
    		Blox::prompt(sprintf(Blox::getTerms('no-parent-key-in-pseudopages'), 'key='.$pseudopageInfos['key']),  true);
    		return;
    	}

    	# pseudopages
		if ($lastAlias = array_pop($parentAliases)) {
            # If the alias consists of digits, then this is "part", which is not stored in the database
            if (empty($part) && Str::isInteger($lastAlias)) {
                $part = $lastAlias;
                $lastAlias = array_pop($aliases); # The array $aliases decreases
            }
	        $sql = "SELECT `phref`, `parent-key` FROM ".Blox::info('db','prefix')."pseudopages WHERE `alias`=? AND `key`=?";                
			if ($result = Sql::query($sql, [$lastAlias, $pseudopageInfos['parent-key']])) {
    		    if ($pseudopageInfos = $result->fetch_assoc()) {
    	        	if (self::checkPseudopageForAliases($pseudopageInfos, $parentAliases, $part)) {
                        $result->free();
    	        		return true;
                    }
    	        }
                $result->free();
            }
	    }
    }
    
        

    public static function getPageInfoByUrl($url)
    {
    	$phref = self::convertUrlToPartFreePhref($url);
        return self::getPageInfoByPhref($phref);# $pageInfo
    }


    public static function getPageInfoByPhref($phref, $isPseudopage=null)
    {
    	if (empty($phref)) {  	    		
    		$pageId = 1; # home
            $isPseudopage = false;
        } elseif (preg_match('~^\?page=(\d+)~', $phref, $matches)) {
        	$pageId = $matches[1];
        } else {
            return false;
        }

        if ($isPseudopage) {
            $pageInfo = self::getPseudopageInfo($phref, $pageId);
        } elseif ($isPseudopage === false) {
            $pageInfo = self::getPageInfoById($pageId);
        } elseif ($isPseudopage === null) {
            $pageInfo = self::getPseudopageInfo($phref, $pageId);
            if ($pageInfo) {
                $isPseudopage = true;
            } else {
                if (preg_match('~&block=(\d+)~', $phref)) { # Unregistered pseudopage
                    //Blox::prompt(sprintf(Blox::getTerms('pseudopageIsNotRegistered'), $phref), true);
                	return false;
                } else { # Regular page
            	    $pageInfo = self::getPageInfoById($pageId);
                }
            }
        } else {
            return false;
        }

        # Insert info of the regular page into info of pseudopage
        if ($isPseudopage) {
            $pageInfo2 = self::getPageInfoById($pageId);
            $pageInfo = Arr::mergeByKey($pageInfo2, $pageInfo);
        }
        if ($phref) {
            if ($aa = self::convert($phref))
                if ($phref != $aa && $aa[0] != '?')
                    $pageInfo['hhref'] = $aa;
        }
        return $pageInfo;
    }



    private static function getPseudopageInfo($phref, $pageId)
    {
        if ($pageInfo = self::selectPseudopageByPhref($phref))
        {
            if ($pageInfo['parent-key'] == '')
                $pageInfo['parent-phref'] = '?page='.$pageId;
	        elseif ($aa = self::selectPseudopage($pageInfo['parent-key'])) # parentPageInfo
	            $pageInfo['parent-phref'] = $aa['phref'];

            $sql = "SELECT `name`, `pseudo-pages-title-prefix` FROM ".Blox::info('db','prefix')."pages WHERE `id`=?";
            if ($result = Sql::query($sql, [$pageId])) {
                if ($row = $result->fetch_assoc()) {
                    $result->free();
                    $pageInfo['base-page-name'] = $row['name'];
                    $pageInfo['pseudo-pages-title-prefix'] = $row['pseudo-pages-title-prefix'];
                }
            }
            
            $pageInfo['is-pseudopage'] = true;
            return $pageInfo;
	    }
    }
            
            



    
    
    public static function convertUrlToPartFreePhref($url='', &$specParams=[])
    {
	    if ($href = Url::convertToRelative($url)) { # Any kind of URL
	        if ($href[0] != '?') # This is hhref, but we need href
                $partFreePhref = self::convertHhrefToPartFreePhref($href, $part);
            else { # phref
                $specParams = self::getPhrefParams($href); # Resort URL params in standard order
                $partFreePhref = '?page='.$specParams['page']['value'];
                
                $query = '';
                if ($specParams['rest']['value'])
                    $query .= $specParams['rest']['value']; # ampersand already presented
                if ($specParams['single'])
                    $query .= $specParams['single']['amp'].'single='.$specParams['single']['value'];


                # 'src' has priority over 'block'
                if (isset($specParams['src'])) 
                    $srcParam = 'src';
                elseif (isset($specParams['block'])) 
                    $srcParam = 'block';
                
                if ($query && $srcParam)
                    $partFreePhref .= $specParams[$srcParam]['amp'].$srcParam.'='.$specParams[$srcParam]['value'].$query;
            }
	    } else
	    	$partFreePhref=''; # home
	    return $partFreePhref;
	}




    # Not used?
    public static function sanitizeUrlParam($str)
    {
        $str = preg_replace('~[^\\pL0-9_]+~u', '-', $str); # Replace nonletters and notdigits by "-"
        $str = preg_replace("/[-]+/u", "-", $str); # Remove double "-"
        $str = trim($str, '-'); # Strip "-" from the beginning and end
        return $str;
    }    



    
    /**
     * Returns array with elements: page, block, part, single.
     * Rest of params will be sorted and collected in the element "rest"
     * Params delimeter ('&' or '&amp;') is in subelement "amp"
     *
     * @todo Parse the url as in Url::arrayToQuery($arr) or Query::capture()
     */
    public static function getPhrefParams($phref, &$afterHash='') //, &$afterHash
    {
        $params = [];
        $ampersands = []; # Collect here params that use delimiter '&amp;' instead of '&'
        # Remove 'amp;' from '&amp;'
        $stripApms = function($k, &$ampersands) {
            if (substr($k, 0, 4) == 'amp;') {
                $k = substr($k, 4);
                $isMnemocode = true;
            }
            
            if ($isMnemocode)
                $ampersands[$k] = '&amp;';
            else
                $ampersands[$k] = '&';

            return $k;
        };
        
        $aa = substr($phref, 1); # Remove '?'
        $bb = explode('#', $aa);        
        $afterHash = $bb[1]; # TODO: Actually the hash is generally not transmitted to the server
        
        if ($requests = explode('&', $bb[0])) { # before Hash
            foreach ($requests as $request) { # page=2&block=14&p[1]=1&p[2]=0
                # Break up as a scalar, not an array, although parameter name can contain "[]". All the parameters we need are not arrays.
                $parts = explode('=', $request); 
                $k = $stripApms($parts[0], $ampersands);
                if (substr($parts[0], 0, 4) == 'sort') # Do not sort "sort" params because the original order is important
                    $sortparams[$k] = $parts[1];
                else
                    $params[$k] = urldecode($parts[1]);
            }
            $ampersands['page'] = ''; # Page goes first 
        }       

        if ($params)
        {
            $specParams = [];
            $srcExists = false;
            # Some params will be at the beginning (page), some params will be in the end (single)
            foreach (['page', 'src', 'block', 'part', 'single'] as $paramName) {
                if (array_key_exists($paramName, $params)) {
                    if ($paramName=='src')
                        $srcExists = true;
                    elseif ($srcExists && $paramName=='block') # 'src' has priority over 'block'
                        continue;
                    $specParams[$paramName]['value'] = $params[$paramName];
                    $specParams[$paramName]['amp'] = $ampersands[$paramName];                    
                    unset($params[$paramName]);
                }
            }   

            /**
             * Rest of params (custom and 'backward', 'limit', 's', 'search', 'p', 'pick' ) will be in the middle.
             * Move 'sort' params to the end 
             */
            $restPhref = '';
            $restParamsNames = [];
            # $params decreased
            if ($params) {    
                ksort($params, SORT_NATURAL);
                foreach ($params as $paramName=>$v){
                    $restPhref .= $ampersands[$paramName].$paramName;
                    if (!isEmpty($v))
                        $restPhref .= '='.$v;
                    $restParamsNames[] = explode('[',$paramName)[0];
                }
            }
            
            if ($sortparams) {
                foreach ($sortparams as $paramName=>$v) {
                    $restPhref .= $ampersands[$paramName].$paramName;
                    if (!isEmpty($v))
                        $restPhref .= '='.$v;
                    $restParamsNames[] = explode('[',$paramName)[0];
                }
            }

            if ($restPhref) {
                $specParams['rest']['value'] = $restPhref;
                # formally
                $specParams['rest']['amp'] = '';
                $specParams['rest']['names'] = $restParamsNames;
            }
            return $specParams;
        } 
    }




    private static function redirectToParametricUrl($hhref)    
    {
        if ($phref = self::convertHhrefToPartFreePhref($hhref, $part)) {
            if ($part)
                $phref .= '&part='.$part;
            Url::redirect(Blox::info('site','url').'/'.$phref,'exit');
        } else
            return false;
    }
    
    # Redirect to human URL or do nothing
    public static function redirectToHumanUrl($phref)
    {
        if ($phref) {
            $hhref = self::convert($phref);
            if ($hhref !== false) { # home page
                if ($hhref != $phref) {
                    Url::redirect(Blox::info('site','url').'/'.$hhref,'exit');
                }
            }
        }
        return false;
    }



    
    
    
	public static function getPageInfoById($pageId)
	{
        if ($pageId) {
            $sql = "SELECT * FROM ".Blox::info('db','prefix')."pages WHERE `id`=?";
            $result = Sql::query($sql, [$pageId]);
            if ($result) {
                if ($pageInfo = $result->fetch_assoc()) {
                    $result->free();
        	        return $pageInfo;
                } else {
                    Blox::error(sprintf(Blox::getTerms('no-page'), $pageId, '<a href="?page=1">', '</a>'));
                    return false;
                }
            }
        }
	}
    
    
    
    /**
     * By default, this method does no effect, if pageInfo was edited manually before
     *
     * @param array infos 
     * @param bool $priority Unconditionaly replace the page info, even if it was edited manually
     * @return void
     *
     * @todo rename to
     *     Router::changeCurrentPageInfo($infos)
     *     Router::updateCurrentPageInfo($infos)
     *     Router::replaceCurrentPageInfo($infos)
     */
    public static function addCurrentPageInfo($infos, $priority=false) 
    {
        foreach ($infos as $name => $value) {
            if ($priority) {
                self::$pageNewInfo[$name]['value'] = $value;
                self::$pageNewInfo[$name]['priority'] = true;
            } elseif (!self::$pageNewInfo[$name]['priority'])
                self::$pageNewInfo[$name]['value'] = $value;
       }    
    }

    /**
     * @return array Info about current page including new info added by Router::addCurrentPageInfo()
     */
    public static function getCurrentPageInfo() 
    {
        if ($pageInfo = Router::getPageInfoByUrl(Blox::getPageHref())) {
            # $pageInfo retrieved from DB is being modified programmatically
            if (self::$pageNewInfo) {
                foreach (self::$pageNewInfo as $name => $bb) {
                    if ($bb['value']) {
                        if ($bb['priority'] || empty($pageInfo[$name]))
                            $pageInfo[$name] = $bb['value'];                   
                        if ($name == 'title')                
                            unset($pageInfo['pseudo-pages-title-prefix']);
                    }
                }
            }
        }
        return $pageInfo;
    }




    /**
     * @example Router::updatePageInfoParamByUrl($pagehref, 'lastmod', date('Y-m-d H:i:s'));
     */
    public static function updatePageInfoParamByUrl($url, $fieldName, $fieldValue)
    {   
        if ($pageInfo = self::getPageInfoByUrl($url)) {
            if ($pageInfo['is-pseudopage']) {
                $sql = 'UPDATE '.Blox::info('db','prefix')."pseudopages SET `$fieldName`=? WHERE `key`=?";
                if (Sql::query($sql, [$fieldValue, $pageInfo['key']])===false)
                    Blox::prompt(sprintf(Blox::getTerms('failed-to-write-to-pseudopages'), $fieldName.': '.$fieldValue),  true); 
            } else { # Regular page
                $sql = 'UPDATE '.Blox::info('db','prefix')."pages SET `$fieldName`=? WHERE `id`=?";
                if (Sql::query($sql, [$fieldValue, $pageInfo['id']])===false) {
                    Blox::prompt(sprintf(Blox::getTerms('failed-to-write-to-pages'), $fieldName.': '.$fieldValue),  true);
                }
            }
            # changefreq 
            if ($fieldName == 'lastmod') {
                # If there is a single query, the page without a "single" is also considered as edited
                $singleFreePhref = preg_replace( "~&single=\d*~u", '', $pageInfo['phref']);#(|amp;)
                if ($singleFreePhref != $pageInfo['phref'])
                    self::updatePageInfoParamByUrl($singleFreePhref, 'lastmod', $fieldValue); # $isPseudopage
                self::autoChangefreq($pageInfo, $fieldValue);
            }
        }
    }
    

    public static function autoChangefreq($pageInfo, $lastmodValue, $update=null)
    {               
        if ($lastmodValue && $pageInfo['lastmod'] && $pageInfo['changefreq'] != 'always' && $pageInfo['changefreq'] != 'never')
        {
            # Last period
            $diffHourly = Math::divideIntegers(strtotime($lastmodValue) - strtotime($pageInfo['lastmod']), 3600); 
            # $changefreq = 'yearly'; This hamper the update
            $oldFreqIsLesser = false;
            foreach (['hourly'=>1,'daily'=>24,'weekly'=>168,'monthly'=>720,'yearly'=>8760] as $k => $v) {   
                if ($diffHourly < $v) { # If the interval more than a day but less than a week, set weekly
                    if ($update) { # Updating entire table
                        if ($oldFreqIsLesser) # If the old interval is be less than real interval, put real interval
                            $changefreq = $k;                        
                        break;
                    } else { # edit one page
                        if ($oldFreqIsLesser) # If it was "hourly" or "daily" then set the "daily".
                            $changefreq = $kk;
                        else
                            $changefreq = $k;
                        break;
                    }   
                }
                
                if ($k == $pageInfo['changefreq'])
                    $oldFreqIsLesser = true;
                $kk = $k;
            }
            if ($changefreq)
                self::updatePageInfoParamByUrl($pageInfo['phref'], 'changefreq', $changefreq);
        }
    }





    /**
     * @param string $rhref Base relative phref or hhref
     * @param bool $redirectIfNotFound If empty, the method is used to obtain phref, otherwise just redirects if necessary (basic use)
     * @return string|bool
     *
     * @todo Get rid of $redirectIfNotFound param. Organize redirection separately, where it is needed
     */
    public static function getPhref($rhref, $redirectIfNotFound=null)
    {
        # Do I need to do the reverse conversion from hhref to phref?
        $deconvert = false;
        if (empty($rhref)) {
            return '';
        } elseif ($rhref[0] == '?') { # Parametric
            if ($rhref=='?') {
                if ($redirectIfNotFound)
                    Url::redirect('./','exit');
                else
                    return '';
            }
            
            # @todo: Test more strictly, not only: $rhref[0] == '?'
            $hrefIsParametric = true; # relative href is parametric            
            if (mb_substr($rhref, 0, 6) == '?page=') { # '?block=' via Ajax
                $deconvert = true; # As "human urls" can be turned on and we have to redirect
                if ($rhref == '?page=1') {
                    if ($redirectIfNotFound)
                        Url::redirect('./','exit'); # Redirect to home page (without url query)
                    else
                        return false;
                }
            } else 
                return $rhref; # This is admin script
        } elseif (substr($rhref, 0, 9) == 'index.php') { # accidentall "index.php", remove it and redirect 
            if ($redirectIfNotFound)
                Url::redirect(Blox::info('site','url').'/'.substr($rhref, 9),'exit');
            else
                return substr($rhref, 9);
        } else # Human URL
            $deconvert = true; 

        if ($deconvert) {
            if ($redirectIfNotFound) {
                if (Blox::info('site','human-urls','convert')) {

                    if (Blox::info('user','id')) {
                        if (Blox::info('user','user-as-visitor')) {
                            if ($hrefIsParametric)
                                self::redirectToHumanUrl($rhref); # Redirect or do nothing
                            else # Human URL
                                $rhref = self::getPhrefByHhref($rhref, $redirectIfNotFound);
                        # Editor mode
                        } else { # This is excluded in go.php: elseif (Blox::info('user','id') && !Blox::info('user','user-as-visitor'))
                            if (!$hrefIsParametric)
                                self::redirectToParametricUrl($rhref); # # Redirect or do nothing
                        }
                    } else { # Visitor
                        if ($hrefIsParametric)
                            self::redirectToHumanUrl($rhref); # Redirect or do nothing
                        else { # Human URL
                            $rhref = self::getPhrefByHhref($rhref, $redirectIfNotFound);
                        }
                    }
                } else { # You need no conversion
                    if (!$hrefIsParametric) {      
                        if (!self::redirectToParametricUrl($rhref)) # Redirect or do nothing
                            $rhref = false;
                    }
                }
            }
            elseif (!$hrefIsParametric) { # Just get phref
                $rhref = self::getPhrefByHhref($rhref);
            }
        }        
        return $rhref;
    }


    /**
     * @param string $hhref Human base relative URL
     * @param bool $redirectIfNotFound If empty, the method is used to obtain phref, otherwise just redirects if necessary (basic use)
     * @return string|bool
     */
    public static function getPhrefByHhref($hhref, $redirectIfNotFound=null)
    {
        if (empty($hhref)) 
            return '';

        # Is the query really human?
        $hhrefPieces = explode('?', $hhref); # Separate the parametric tail. We do not need the tail itself - it work automatically 
        if ($hhrefPieces[0]) { # The URL is human
            if ($hhrefPieces[0] == 'index.php') 
            	return ''; # insure
            
            if ($phref = self::convertHhrefToPartFreePhref($hhrefPieces[0], $part, $redirectIfNotFound)) {
                if ($part)
                    $phref .= '&part='.$part;
            } else { # 404 Nothing is found
                if ($redirectIfNotFound) {
                    $hhrefPieces2 = Str::splitByMark($hhrefPieces[0], '-/', true); # Looking for the latest alias with a hyphen
                    if ($hhrefPieces2 !== false) # The alias ends with a hyphen
                        Url::redirect($hhrefPieces2[0].'/'.$hhrefPieces2[1], 'exit'); # Try without a hyphen ('-/' to '/')
                } else
                    return false;
            }
        }
        # Parametric Tail
        if (!$redirectIfNotFound && $hhrefPieces[1])
            $phref .= '&'.$hhrefPieces[1];
        return $phref;
    }



    public static function emulateParametricRequest($phref)
    {
    	$arr = self::phrefToArray($phref);                                        
        foreach ($arr as $k=>$v)
            $_GET[$k] = $_REQUEST[$k] = $v; ##$_GET = array_merge_recursive($_GET, $arr); # Works. Optimize?
    }
        
        


        
    /**
     * Convert href to array (for GET)
     */
    public static function phrefToArray($phref)
    {
        $aa = substr($phref, 1); # Remove '?'
        return Url::queryToArray($aa);   
    }



    private static function addErrorPrompt($type, $phref)
    {
        if (self::$errorCounters[$type] <= 9 && $type!='page') { # $type=='page' is too annoying
            $aa = sprintf(Blox::getTerms('no-request-value'), $type, $phref);
            if (self::$errorCounters[$type] == 9)
                $aa .= ' ...';
            Blox::prompt($aa, true);
            self::$errorCounters[$type]++;
        } 
    }
}