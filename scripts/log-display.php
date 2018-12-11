<?php

    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    
    $logsFile = Blox::info('cms','dir').'/misc/logs.txt';
    if (file_exists($logsFile)) {
        $logsTxt = file_get_contents($logsFile);
        # Check permission
        if (mb_strpos($logsTxt, $_GET['file']) !== false)
            $content = file_get_contents($_GET['file']);# Get file content
        else
            $errorTxt = $terms['error1'];
    } else
        $errorTxt = $terms['error2'];

    # Output
    if (empty($content))
        $content = $errorTxt;

    $content = htmlentities($content); 
    # Before PHP5.4: 
    #$content = htmlentities($content, ENT_COMPAT | ENT_HTML401, "UTF-8");
    
    $content = "\n<pre style='font: 13px Verdana; padding:11px'>\n".$content."\n</pre>";    

    $template->assign('outerBlockHtm', $content);
    $tplFile = Blox::info('cms','dir')."/scripts/page.tpl";
    if (file_exists($tplFile))
        $template->display($tplFile);
