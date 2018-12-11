<?php
    
    /**
     * @todo See: "HERE IS BUG!"
     */

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) {
        $pagehref = Blox::getPageHref();
        Url::redirect($pagehref,'exit');
    }

    $pageChilds = getPageChilds(0);
    $listOfPages = outputListOfPages($pageChilds);
    $template->assign('listOfPages', $listOfPages);

    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/display.php";

    function outputListOfPages($pageChilds)
    {
        $str .= '<ul>';
        foreach ($pageChilds as $pageInfo) {
            $str .= '<li><a target=_blank href="'.$pageInfo['phref'].'">'.(($pageInfo['name']) ? $pageInfo['name'] : $pageInfo['phref']).'</a>';
            if ($pageInfo['childs'])
                $str .= outputListOfPages($pageInfo['childs']);
            $str .= '</li>';
        }
        $str .= '</ul>';
        return $str;
    }
    
    

    /**
     * Tree of the site.
     * Build a tree of regular pages (get descendants, including pseudotransit).
     * Start with the parent page, to get the params of the page itself.
     * For example, to get the params of the home page, write: $pageChilds = getPageChilds(0);
     */
    function getPageChilds($pageId)
    {
        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'pages WHERE `parent-page-id`=?';
        if ($result = Sql::query($sql, [$pageId])) {
            $now = date("Y-m-d H:i:s"); # for autoChangefreq()
            while ($row = $result->fetch_assoc()) {
                if ($pageId = $row['id']) {
                    if ($aa = getPageChilds($pageId))
                        $row['childs'] = $aa;
                    /* HERE IS BUG!
                    elseif ($aa = getPseudopageChilds('', $pageId)) { # No childs in "pages", check "pseudopages"
                        $row['childs'] = $aa;
                    */
                    $row['phref'] = '?page='.$pageId;                   
                    Router::autoChangefreq($row, $now, true);
                }
                $pageChilds[] = $row;
            }
            $result->free();
            return $pageChilds;
        }
    }
    


    /**    
     * To build the tree of the pseudopage (get descendants)
     * Start with the regular page, to get the params of the first level page.
     */
    function getPseudopageChilds($key, $pageId=null)
    {
        if ($pageId && $key=='')
            $phrefSql = " AND phref LIKE '?page=".$pageId."&%'"; # For the first time we are looking for pseudopages which have no parents (pseudopages) with the given pageId
        elseif ($key=='')
            return;

        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'pseudopages WHERE `parent-key`=?'.$phrefSql; 
        if ($result = Sql::query($sql, [$key])) {
            $now = date("Y-m-d H:i:s"); # for autoChangefreq()                
            while ($row = $result->fetch_assoc()) {
                if ($aa = getPseudopageChilds($row['key']))
                    $row['childs'] = $aa;         
                $pseudopageChilds[] = $row;
                Router::autoChangefreq($row, $now, true);
            }
            $result->free();
            return $pseudopageChilds;
        }
    }
        