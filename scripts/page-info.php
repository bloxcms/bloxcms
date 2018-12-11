<?php

    /** 
     * @todo    Redo as ?edit-button-setting- or ?block-settings
     * @todo    Style .warning
     */
    $pagehref = Blox::getPageHref();        
        
	if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
    
	if ($pageOldInfo = Router::getPageInfoByUrl($pagehref)) {
        $pageId = $pageOldInfo['id'];
        if ($pageOldInfo['is-pseudopage']) {
            $pageOldInfo['id'] = $pageId;        
        } else {   # regular page
            if ($pageId == 1) {
                $pageOldInfo['phref'] = '';
                $pageOldInfo['parent-phref'] = $terms['home-page-has-not-parent'];
                $pageOldInfo['alias'] = $terms['home-page-has-not-alias'];
            } else {
                $pageOldInfo['phref'] = '?page='.$pageId;
                if ($pageOldInfo['parent-page-id'] == 1)
                    $pageOldInfo['parent-phref'] = '';
                else
                    $pageOldInfo['parent-phref'] = '?page='.$pageOldInfo['parent-page-id'];
            }        
        }

        if ($pageOldInfo['phref']) {
            $aa = Router::convert($pageOldInfo['phref']);
            if ($aa != $pageOldInfo['phref']) # TODO It's not always true
                $pageOldInfo['hhref'] = $aa;
        }

        if ($pageOldInfo['changefreq'] != 'always' && $pageOldInfo['changefreq'] != 'never')
            $pageOldInfo['changefreq'] = 'auto';
        
        if ($pageOldInfo['priority'] === null)
            $pageOldInfo['priority'] = 0.5;

        /**
         * @kludge Russian format of decimal numbers (comma instead of point)
         * @todo Use locale
         */        
        if ('ru' == Blox::info('site', 'lang'))
            $pageOldInfo['priority'] = str_replace('.' ,',', $pageOldInfo['priority']);
        
        $template->assign('pageOldInfo', $pageOldInfo);
        include Blox::info('cms','dir')."/includes/buttons-submit.php";
    } else {
        include Blox::info('cms','dir')."/includes/button-cancel.php";
    }
    include Blox::info('cms','dir')."/includes/display.php";