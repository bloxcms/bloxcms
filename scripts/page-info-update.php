<?php

/**
 * @todo $defaultParentPageAssigning for pseudopages too
 */
 
if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')))
    Blox::execute('?error-document&code=403');

$pagehref = Blox::getPageHref();

Url::redirect($pagehref);
$pagehrefQuery = '&pagehref='.Url::encode($pagehref);
$parentPhref = Router::convertUrlToPartFreePhref($_POST['page-info']['parent-phref']);
if (empty($parentPhref))
	$parentPhref = '?page=1';
$pageId = (int)$_POST['page-info-old']['id'];
$phref = $_POST['page-info']['phref'];
/**  
 * @todo Redo using 'navAliases'
 *     $hhrefToBeChanged = false;
 */


###################### For pages and pseudopages #######################
$sqlset = '';

# alias
if ($_POST['page-info']['alias']) {
    if ($alias = Str::sanitizeAlias($_POST['page-info']['alias'], Blox::info('site', 'transliterate'))) {
        if ($_POST['page-info-old']['alias'] != $alias) {
            $alias2 = makeUniqueAlias($alias, $_POST['page-info-old']);
            if ($alias2 != $alias) {
                Blox::prompt(sprintf($terms['alias-is-taken'], '<b>'.$alias.'</b>', '<b>'.$alias2.'</b>'),  true);
                Url::redirect(Blox::info('site','url').'/'.$_POST['page-info-old']['phref']); # Go to a parametric URL since old human URL does not exist
            }
            $_POST['page-info']['alias'] = $alias2;
            if (Blox::info('site','human-urls','on') && Blox::info('site', 'caching')) {
                # Delete caches of all regular descendants
                Cache::gatherDescendantPages($pageId, $descendantpages);
                if ($descendantpages) {
                    foreach ($descendantpages as $p=>$z)
                        Cache::deleteByPage($p);
                }
                # Delete the cache of the current regular page and caches of its pseudopages
                Cache::deleteByPage($pageId);
            }
        } else
            unset($_POST['page-info']['alias']);
    } else
        unset($_POST['page-info']['alias']);
} # else - Rest an alias 

# title
if ($_POST['page-info']['title'])
    $_POST['page-info']['title'] = Text::stripTags($_POST['page-info']['title']); # Remove tags

# keywords
if ($_POST['page-info']['keywords'])
    $_POST['page-info']['keywords'] = Text::stripTags($_POST['page-info']['keywords'], ['strip-quotes'=>true]);
# description
if ($_POST['page-info']['description'])
    $_POST['page-info']['description'] = Text::stripTags($_POST['page-info']['description'], ['strip-quotes'=>true]);



##### priority
if ($_POST['page-info']['priority'] === '')
{
    # Old value does exist
    if ($_POST['page-info-old']['priority']) {
        $sqlset = ', priority=NULL';
        unset($_POST['page-info']['priority']);
    }
} else {
    $aa = (function($priority, $min = 0.0, $max = 1.0) {
        /**
         * @kludge Russian format of decimal numbers (comma instead of point)
         * @todo Use locale
         */        
        if ('ru' == Blox::info('site', 'lang'))
            $priority = str_replace("," ,".", $priority);
        
    	$priority = floatval($priority);
    	if ($priority <= (float)$min)
    		return number_format($min,  true);
    	elseif ($priority >= (float)$max)
    		return number_format($max, 1);
    	else
    		return number_format($priority, 1);
    })($_POST['page-info']['priority']);
    //$aa = $sanitizePriority($_POST['page-info']['priority']);        
    if ($_POST['page-info-old']['priority'] == $aa)    
        unset($_POST['page-info']['priority']);
    elseif ($aa == 0.5) {
        $sqlset = ', priority=NULL';
        unset($_POST['page-info']['priority']);
    } else
        $_POST['page-info']['priority'] = $aa;
}


# changefreq
if ($_POST['page-info']['changefreq'] == 'auto') {
    if ($_POST['page-info-old']['changefreq'] == 'always' || $_POST['page-info-old']['changefreq'] == 'never')    
        $sqlset = ', changefreq=NULL';
    unset($_POST['page-info']['changefreq']);
}



###################### Foe pseudopages #######################
if ($_POST['page-info']['is-pseudopage'])
{   
    $key = $_POST['page-info-old']['key'];
    $updateSiblings = function($key, $pageOldInfo, $parentKey)
    {
        if ($key && $pageOldInfo['level'] && $parentKey)
        {
            /** 
             * Who are siblings? 
             *     -  They were output in the same list
             *     -- If you already have a parent and all parents are the same, but this case does not cause problems. Prevent assigning of a parent in a multi-level menu 
             *     -  Sublist could be formed by a pick request
             *     +  Siblings are in the same block
             *     +  Siblings have the same (not empty) level
             */
            if ($levels = Sql::select('SELECT level FROM '.Blox::info('db','prefix').'pseudopages WHERE key=?', [$key])) {
                foreach ($levels as $aa) {
                    if ($aa['level'] != $pageOldInfo['level']) { 
                        Blox::prompt($terms['failed-to-change-parent-for-all-links'], true);
                        return;
                    }
                }
            }

            $sql = 'UPDATE '.Blox::info('db','prefix').'pseudopages SET `parent-key`=?, `parent-page-is-adopted`=1 WHERE `key`=?';
            Sql::query($sql, [$parentKey, $key]);
			
			/** 
             * @todo Redo using 'navAliases'
                $sql = "
                    DELETE FROM ".Blox::info('db','prefix')."humanurls AS hu
                    WHERE hu.href
                    IN(
                        SELECT phref
                        FROM ".Blox::info('db','prefix')."pseudopages as pp
                        WHERE pp.block=$key && pp.`parent-key`={$pageOldInfo['parent-key']}
                    )";
                Sql::query($sql);
                Deleting multiple records from itself http://www.zriel.com/mysql/8-mysql-delete-select-in-how-it-works
            */
        } else
            Blox::prompt($terms['insufficient-data-for-all-links'], true);
    };//$updateSiblings

    
    foreach ($_POST['page-info'] as $fieldName => $value)
    {
        $fieldName = Sql::sanitizeName($fieldName);            
        if (
            $fieldName == 'is-pseudopage'||
            $fieldName == 'parent-phref'  ||  // KLUDGE
            $fieldName == 'parent-key' || // KLUDGE
            $fieldName == 'change-parent-page-for-all-siblings'
        ) ;
        elseif ($fieldName === 'parent-page-is-adopted') # For the parent page
        {
            /**
            if ($_POST['page-info-old']['parent-page-is-adopted']) # parentPage Was Adopted manualy earlier
            {
            */
                if ($value) # manualy
                {
                    if ($parentKeys = adoptedParentPhrefIsCorrect($parentPhref, $_POST['page-info-old']['phref'], $pagehrefQuery)) {
                        # Replace the parent page for all other similar links of block (in the same field)
                        if (isset($parentKey)) {
                            if ($_POST['page-info']['change-parent-page-for-all-siblings']) {
                                $updateSiblings($key, $_POST['page-info-old'], $parentKeys); 
                            } else { # Only current link
                                $sqlset .= ', `parent-key`='.Sql::parameterize($parentKey);
                            }
                        }
                    } else
                        Blox::prompt($terms['parent-page-url-is-not-correct'], true);
                    $sqlset .= ', `parent-page-is-adopted`=1';
                }
                # automaticaly
                else {
                    $sqlset .= ', `parent-page-is-adopted`=0'; # Remove mark that `parent-page-is-adopted`
                }
        }        
        elseif ($fieldName === 'pseudo-pages-title-prefix')
        {
            $value = Text::stripTags($value, ['strip-quotes'=>true]);            
            if ($_POST['page-info-old']['pseudo-pages-title-prefix'] != $value) {
                $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET `pseudo-pages-title-prefix`=? WHERE id=?';
                Sql::query($sql, [$value, $pageId]);
            }
        }
        else # SIMILAR
            $sqlset .= ', '.$fieldName.'='.Sql::parameterize($value);
    }

    $sqlset = substr($sqlset, 1);  # remove initial ','
    $sql = 'UPDATE '.Blox::info('db','prefix').'pseudopages SET '.$sqlset.' WHERE `key`='.Sql::parameterize($key);
    Sql::query($sql);
	/** 
     * @todo Redo using 'navAliases'
        if ($_POST['page-info-old']['parent-phref'] != $parentPhref)
            $hhrefToBeChanged = true;
    */

}
else # Regular page
{
    if (preg_match('~page=(\d+)~', $parentPhref, $matches))
        $parentPageId = (int)$matches[1];

    foreach ($_POST['page-info'] as $fieldName => $value)
    {
        if ($fieldName === 'page-is-hidden') {
            $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET `page-is-hidden`=? WHERE id=?'; # NOTTESTED
            Sql::query($sql, [(int)$_POST['page-info']['page-is-hidden'], $pageId]); 
        } else {
            $value = trim($value);

            # For the parent page
            if ($fieldName === 'parent-page-is-adopted') {
                # Default parent page assigning 
                $defaultParentPageId = 0;
                if ($_POST['page-info']['parent-page-is-adopted'] === '0') { # Set default parent page. $_POST['page-info']['parent-phref'] is not set
                    //if ($_POST['page-info-old']['parent-page-is-adopted']) { # If page was adopted before
                        $t = Tree::get(1, ['block', 'page'], ['page'=>$pageId]); # Info about a nav-block that contains current page
                        if ($t['block-id']) { # Nav-block that contains link to current page
                            $t2 = Tree::get(1, ['block', 'page'], ['block'=>$t['block-id']]); # Info about a parent block that contains nav-block 
                            # Find field with page-datum 
                            $tdd2 = Tdd::get(['src-block-id'=>$t2['block-id'], 'tpl'=>$t2['tpl']]);
                            if ($p = Tdd::getTypesDetails($tdd2['types'], ['page'], 'only-name')) {
                                if (1 == count($p)) {
                                    foreach ($p as $field=>$dd) {
                                        $defaultParentPageId = Dat::get(['src-block-id'=>$t2['block-id'], 'tpl'=>$t2['tpl']], ['rec'=>$t2['rec-id']])[$field]; #   TODO: for $xprefix too
                                        break;
                                    }
                                }
                            }
                        }
                        if ($defaultParentPageId) {
                            # Emulate new parent page assigning 
                            $parentPageId = $defaultParentPageId;
                            $_POST['page-info']['parent-phref'] = '?page='.$defaultParentPageId;
                            $_POST['page-info']['parent-page-is-adopted'] = 1;
                            //$_POST['page-info-old']['parent-page-is-adopted'] = 1; //temp---------------------------------------------------
                            $value = 1;
                            $defaultParentPageAssigning = true;
                        } else {
                            Blox::prompt($terms['failed-to-assign-default-parent-page'], true);
                        }
                    //}
                }
                /*
                if ($_POST['page-info-old']['parent-page-is-adopted']) {
                */
                    if ($value) # Manualy
                    {
                        if (adoptedParentPageIsCorrect($pageId, $parentPageId, $parentPhref, $pagehrefQuery, $defaultParentPageAssigning)) {
                            $sqlset .= ', `parent-page-id`='.Sql::parameterize($parentPageId);
                        } else
                            Blox::prompt($terms['parent-page-id-is-not-correct'], true);
                        # Replace the parent page for all other similar links of block (in the same field)
                        if ($_POST['page-info']['change-parent-page-for-all-siblings']) {
                            # Find page-datum with the value $_POST['page-info']['id']
                            if ($foundDatumAddress = Tree::get(1, ['block', 'page'], ['page'=>$pageId])) {
                                # Find all other similar links of block
                                $sql = 'SELECT dat'.$foundDatumAddress['field'].' FROM '.Blox::getTbl($foundDatumAddress['tpl']).' WHERE `block-id`=?';
                                if ($result = Sql::query($sql, [$foundDatumAddress['block-id']])) {
                                    while ($row = $result->fetch_row()) {
                                        if ($parentPageId != $row[0])  {# If assigned as a parent, not a sibling
                                            $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET `parent-page-id`=?, `parent-page-is-adopted`='.($defaultParentPageAssigning ? 0 : 1).' WHERE id=?';
                                            Sql::query($sql, [$parentPageId, $row[0]]);
                                            /** 
                                             * @todo Redo using 'navAliases'
                                                $sql2 = "DELETE FROM ".Blox::info('db','prefix')."humanurls WHERE phref='?page={$row[0]}'";
                                                Sql::query($sql2);
                                            */
                                        }
                                    }
                                    $result->free();
                                }
                            } else
                                Blox::error($terms['block-with-page-is-not-found'], true);
                        }
                        ###################################################################################################################
                    } else { 
                        ;
                        /*
                        # Simple but total default parent pages assigning (for all pages)
                        # Automaticaly
                        $sql = "UPDATE ".Blox::info('db','prefix')."pages SET `parent-page-is-adopted`=1 WHERE id=?"; # NOTTESTED
                        Sql::query($sql, [$pageId]);
                        Tree::renewParentsIds();
                        */
                    }
                /*
                } else { # Was automaticaly
                    if ($value) { # Do manualy
                        if (adoptedParentPageIsCorrect($pageId, $parentPageId, $parentPhref, $pagehrefQuery, $defaultParentPageAssigning)) {
                            $sqlset .= ', `parent-page-id`='.Sql::parameterize($parentPageId);
                        } else
                            Blox::prompt($terms['parent-page-id-is-not-correct'], true);
                    }
                    # else # Do automaticaly
                }
                */
            }
            # Skip this since parent-page-id processed in the previous block
            elseif ($fieldName == 'parent-page-id' || $fieldName == 'parent-phref' || $fieldName == 'change-parent-page-for-all-siblings')
                ;
            else
                $sqlset .= ', '.$fieldName.'='.Sql::parameterize($value);
        }
    }


    $sqlset = substr($sqlset, 1);  # remove initial ','
    $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET '.$sqlset.' WHERE id='.Sql::parameterize($pageId);
    Sql::query($sql);
	/** 
     * @todo Redo using 'navAliases'
        if ($_POST['page-info-old']['parent-page-id'] != $parentPageId)
            $hhrefToBeChanged = true;
    */

} # is regular page


/** 
 * @todo Redo using 'navAliases'
    if ($_POST['page-info-old']['alias'] != $_POST['page-info']['alias'])
        $hhrefToBeChanged = true;
*/

# End of script



function adoptedParentPageIsCorrect($pageId, $parentPageId, $parentPhref, $pagehrefQuery, $defaultParentPageAssigning=false)
{
    if ($pageId == $parentPageId) 
        return false;
    
    $_SESSION['Blox']['new-adopted-parent-phref'] = $parentPhref;
    if ($parentPageId && preg_match("/^\d+$/", $parentPageId)) {
        # Page exists
        if (Router::getPageInfoById($parentPageId)) {
            $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET `parent-page-is-adopted`='.($defaultParentPageAssigning ? 0 : 1).' WHERE id=?';
            Sql::query($sql, [$pageId]);
            return true; 
        } else {# if this page does not exist
            # Return with report
            Url::redirect(Blox::info('site','url').'/?page-info&page='.$pageId.'&error=parentPageDoesNotExist'.$pagehrefQuery);
            return false;
        }
    }
    else {# Manual. Wrong value (0 or not number)
        # Return with report
        Url::redirect(Blox::info('site','url').'/?page-info&page='.$pageId.'&error=adoptedParentPageIsNotCorrect'.$pagehrefQuery);
        return false;
    }
}

/** 
 * @kludge Remake!
 * Applies to regular pages?
 */
function adoptedParentPhrefIsCorrect($parentPhref, $phref, $pagehrefQuery)
{
    if ($phref == $parentPhref)
        return false;

    $_SESSION['Blox']['new-adopted-parent-phref'] = $parentPhref;

    if ($parentPhref)
    {
        $currentPageInfo = Router::getPageInfoByPhref($phref);
        $parentPageInfo = Router::getPageInfoByPhref($parentPhref);
        # Parent page is pseudopage
        if ($parentPageInfo['is-pseudopage']) {
            if ($currentPageInfo['is-pseudopage']) { # TODO. Does not work with different page numbers
                return $parentPageInfo['key'];
            # Current Page is Regular page
            } else { 
                Blox::prompt('Incomplete code J8654IUGI', true);
            }
        # Parent page is regular page
        } elseif ($parentPageInfo['id']) {
            if ($currentPageInfo['is-pseudopage']) {
                if ($parentPageInfo['id'] == $currentPageInfo['id']) { # OK KLUDGE: This situation should never appear
                    return '';
                } else { # Not yet developed
                    Blox::prompt(sprintf($terms['different-page-id-is-denied'], $phref, $parentPageInfo['id']), true);
                    return false;
                }
            # Current Page is Regular page
            } # else ok  
        # The page does not exist
        } else {
            Url::redirect(Blox::info('site','url').'/?page-info&page='.$pageId.'&error=parentPageDoesNotExist'.$pagehrefQuery);
            return false;
        }
    }
}


/**
 * @todo This is an analogue of the same functions from the Router class
 */
function makeUniqueAlias($alias, $pageOldInfo)
{
    if ($pageOldInfo['parent-page-id']) {
        # Retrieve all aliases of the pages with the same parent
        $sql = 'SELECT alias FROM '.Blox::info('db','prefix').'pages WHERE `parent-page-id`=? AND id != ?'; #  To remove from the array the alias of the current page
        if ($result = Sql::query($sql, [$pageOldInfo['parent-page-id'], $pageOldInfo['id']])) {
            while ($row = $result->fetch_assoc()) {
                if ($row['alias'])
                    $aliases[$row['alias']] = true;
            }
            $result->free();
        }
        

        if ($aliases)
            $alias = makeUniqueAlias2($alias, $aliases, $counter);
    }        
    return $alias;
}



/**
 * Used by makeUniqueAlias 
 *
 * @todo This is an analogue of the same functions from the Router class
 */
function makeUniqueAlias2($alias, $aliases, &$counter)
{
    if ($counter > 9) {
        $counter = 0;
        Blox::prompt(sprintf($terms['infinite-loop'], $alias), true);
        return $alias; # Infinite loop protection
    }
    
    if ($aliases[$alias]) {
        $alias .='-';
        $alias = makeUniqueAlias2($alias, $aliases, $counter);
    }
    
    $counter++;
    return $alias;
}
    