<?php
    
    /**
     * Display system admin window, i.e. used for all scripts except page.php and change.php
     * SYSTEM SCRIPT TEMPLATE VARIABLES SHOULD NOT INTERFERE WITH THE CUSTOM TEMPLATE VARIABLES!
     */

    if ($terms)
        $template->assign('terms', $terms);

    if ($options['untemplatize'])
        return;
        
    if (!$options['only-body']) {  
        Blox::addToFoot(Blox::info('cms','url').'/assets/jquery-1.js', ['action'=>'replace']); # Applied only in scripts: ?edit, ?page-info, but linked in all scripts (for the future)

        # Favicon. This is used in two places: display.php, display-page.php
        if ($faviconFileName = Blox::info('site','favicon','file-name'))
            if (file_exists(Blox::info('site','dir').'/datafiles/'.$faviconFileName))
                $faviconFile = Blox::info('site','url').'/datafiles/'.$faviconFileName;    
        if (empty($faviconFile))
            $faviconFile = Blox::info('cms','url').'/assets/blox-favicon.ico';
        $template->assign('faviconFile', $faviconFile);

        # logoIcon
        $logoIcon = '<img src="'.$faviconFile.'" class="blox-favicon" alt="logo" />';
        $template->assign('logoIcon', $logoIcon);
        
        $barTitle = $barTitle ?: ($terms['bar-title'] ?: 'Blox CMS');
        $template->assign('barTitle', $barTitle);
        if ($script != 'maintenance') {
            $template->assign('cmsPublicStyled', true);
            $template->assign('cmsPrivateStyled', true);
        }

        # Bar
        if ($noBar)
            ;
        else {  
            $barTplFile = Blox::info('cms','dir').'/scripts/'.$script.'-bar.tpl';        
            if (file_exists($barTplFile))
                $barHtm = $template->fetch($barTplFile);
            else {# The panel is always needed
                if (!$tdd[$xprefix.'params']['no-edit-bar']) # KLUDGE condition
                    $barHtm ='<div class="blox-bar"><div class="blox-menubar" style="font-weight:bold">'.$logoIcon.$barTitle.'</div></div>';//.Admin::tooltip().
            }

            # Output prompts to the bar
            if (!in_array($script, ['login','authenticate','user-info','user-activation','install']))
                $barHtm .= Admin::getPromptsHtm();
            $template->assign('barHtm', $barHtm);
        }    
    }

    if (file_exists($tplFile)) # Is it been tested?
        $outerBlockHtm = $template->fetch($tplFile);

    if ($options['only-body']) {
        if ($options['get'])
            return $outerBlockHtm;
        else
            echo $outerBlockHtm;
    } else {
        $template->assign('outerBlockHtm', $outerBlockHtm);
        $template->assign('script', $script);
        $tplFile = Blox::info('cms','dir')."/scripts/page.tpl";
        if (file_exists($tplFile))
            $template->display($tplFile); 
    }