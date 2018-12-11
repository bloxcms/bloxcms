<?php

    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    
    $instanceOption = $_GET['instance-option'];
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $pagehref = Blox::getPageHref();
    
    if ($instanceOption != 'delete') {
        if (empty($_GET['tpl']))
            Url::redirect($pagehref,'exit');// protection against stray reload of the browser with assign query 
        $tpl = Sql::sanitizeTpl(urldecode($_GET['tpl']));
        $instanceId = Sql::sanitizeInteger($_GET['instance']);
        if (isEmpty($tpl) or empty($regularId) or empty($instanceOption)) {
            Blox::prompt(sprintf($terms['not-enough-data'], $tpl, $regularId, $instanceOption), true);
            return false;
        }
    }
    
    # Reload page for autoassign of dafault templates
    if (Router::hrefIsParametric($pagehref)) {
        Query2::set($pagehref);
        Url::redirect('?'.Query2::build('reload=page')); # For autoassign of dafault templates
    } else
        Url::redirect($pagehref); # this will never happen

    # New assignments
    switch ($instanceOption)
    {
        case 'delete':
            if (!Admin::resetBlock($regularId))
                Blox::prompt(sprintf($terms['block-is-not-reset'], $regularId), true);        
            $sql = "UPDATE ".Blox::info('db','prefix')."blocks SET tpl='', `delegated-id`=0 WHERE id=?"; # parent-block-id remains the same
            Sql::query($sql, [$regularId]);  
            break;
        case 'change':
            if (!Admin::resetBlock($regularId))
                Blox::prompt(sprintf($terms['block-is-not-reset'], $regularId), true);
            if (!Admin::assignNewTpl($regularId, $tpl)) { # $regularId == $srcBlockId
                Blox::prompt(sprintf($terms['failed-to-assign'], $tpl), true);
                return false;
            }
            unmarkLastDelegated($tpl);                
            break;
        case 'delegate': 
            if (!Admin::delegate($regularId, $instanceId)) {
                Blox::prompt(sprintf($terms['failed-to-delegate'], $instanceId.'('.$tpl.')', $regularId), true);
                return false;
            }
            markLastDelegated($tpl, $instanceId);
            break;
    }
    Blox::updateBlockCache($regularId);#block-caching
    return true;

    
      
    function markLastDelegated($tpl, $instanceId)
    {        
        $sql = 'REPLACE '.Blox::info('db','prefix')."lastdelegated (tpl, `block-id`) VALUES (?, ?)";
        Sql::query($sql, [$tpl, $instanceId]);
    }



    function unmarkLastDelegated($tpl)
    {        
        $sql = "DELETE FROM ".Blox::info('db','prefix')."lastdelegated WHERE tpl=?";        
        Sql::query($sql, [$tpl]);
    }