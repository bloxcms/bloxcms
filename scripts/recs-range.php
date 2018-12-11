<?php
    # NOT FOR EXTRADATA!
    
    if (!Blox::info('user','id')) 
        Blox::execute('?error-document&code=403');
    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $recId = Sql::sanitizeInteger($_GET['rec']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);
    $tdd = Tdd::get($blockInfo);
    $typesDetails = Tdd::getTypesDetails($tdd['types'], [], 'only-name');
    $template->assign('typesDetails', $typesDetails);
    $template->assign('dataTitles', $tdd['titles']);
    $template->assign('editingFields', Admin::getEditingFields($tdd['titles'], $tdd['types']));
    $template->assign('backUrl', '?edit&block='.$regularId.'&rec='.$recId.$pagehrefQuery);
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";