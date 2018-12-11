<?php
    # NOT FOR EXTRADATA!
        
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);
    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    $recId = Sql::sanitizeInteger($_GET['rec']);
    $template->assign('rec-id', $recId);
    Permission::addBlockPermits($blockInfo['src-block-id']);
    if (Permission::ask('record', [$blockInfo['src-block-id']])['']['edit'])//get
        return; 
    $tdd = Tdd::get($blockInfo);
    $typesDetails = Tdd::getTypesDetails($tdd['types'], [], 'only-name');
    $template->assign('typesDetails', $typesDetails);
    $template->assign('dataTitles', $tdd['titles']);
    if (($tdd['params']['backward'] || Request::get($regularId,'backward')))
        $template->assign('backward', true);
    $template->assign('backUrl', "?edit&block={$regularId}&rec={$recId}".$pagehrefQuery);

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";