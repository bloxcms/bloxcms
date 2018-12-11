<?php
    #NOTTESTED in v.13
    
    /**
     * Mailing of a blocks (not subscribed)
     */
      
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);
    $pagehref = Blox::getPageHref();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
    Report::reset();

	Permission::addBlockPermits($regularId);
    if (Permission::ask('record', [$regularId])['']['edit'])//get
        Url::redirect($pagehref,'exit');
        
    # Check the old mailing list?
    if (Sql::tableExists(Blox::info('db','prefix').'blockrecipients')) {
        ### DEPRECATED: Since v.9.4.30 appeared column "`block-id`". If no `block-id`, then drop the table.
        $tableColsInfo = Admin::getTableColsInfo(Blox::info('db','prefix').'blockrecipients');
        if (!isset($tableColsInfo['block-id'])) {
            $sql = "DROP TABLE IF EXISTS ".Blox::info('db','prefix')."blockrecipients";
            Sql::query($sql);
        } 
    }


    # New mailing
    $template->assign('blockletterParams', Store::get('blockletter-params'.$regularId));

    # Don't bother, pass both sets of form validation
    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
