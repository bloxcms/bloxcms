<?php
    /**
     * This script is invoked only by the system (not through a url query).
     * @todo Rename page.php to page.php
     */
    
    # Reload page for autoassign of dafault templates
    if ('page'==$_GET['reload']) {
        $pagehref = Blox::getPageHref();    
        if (Router::hrefIsParametric($pagehref)) {
            Query2::set($pagehref);
            Url::redirect('?'.Query2::build('','reload')); 
        }
    }
    /* Variant 2
     * Come from assign
     * if (substr($_SERVER['HTTP_REFERER'], 0, 7) === '?change') //not assign!
    */
    
    
    # get page ID
    if (empty($_GET['page'])){
        $pageId = 1;
    	if (Blox::info('user','user-is-admin') && isset($_GET['page'])) # need?
            Blox::prompt($terms['no-page']);}
    else {
        $_GET['page'] = Sql::sanitizeInteger($_GET['page']);
        $pageId = $_GET['page'];
    }

    if ($pageId)
        Blox::setPageId($pageId);
    
    Request::set();
    if (Blox::info('user','id')) { # Place this above outerBlockHtm
        $template->assign('cmsPublicStyled', true);
    } else { # visitor
        (function($pageId) {
            if (!Store::get('statisticsIsOff')) {
        		Blox::statCount('pages', $pageId);
        		Blox::statCount('remotehosts', $_SERVER['REMOTE_ADDR']); # Save IP
        		Blox::statCount('referers', preg_replace(
                        [
                            '~^.*?//~', # scheme
                            '~^www\.~', 
                            '~/$~', # trailing slash
                        ], '', mb_strtolower($_SERVER['HTTP_REFERER'])
                    )
                );
            }
        })($pageId);
        //$countVisitorStatistics($pageId);    
    }
    
    include Blox::info('cms','dir')."/includes/display-page.php";