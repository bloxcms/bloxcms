<?php
    # NOT FOR EXTRADATA!
    
    if (!Blox::info('user','id'))
        Blox::execute('?error-document&code=403');

    $pagehref = Blox::getPageHref();
    Request::set(); # It must be above Tdd::get() for $defaults = Request::get()

    $regularId = Sql::sanitizeInteger($_GET['block']);
    $recId = Sql::sanitizeInteger($_GET['rec']);
    
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);
    $tdd = Tdd::get($blockInfo);

    $template->assign('dataTypes', $tdd['types']);
    $template->assign('dataTitles', $tdd['titles']);
    $template->assign('params', $tdd['params']);

    $template->assign('editingFields', Admin::getEditingFields($tdd['titles'], $tdd['types']));

    
    $settings = Store::get('editSettings'.$blockInfo['src-block-id']);//$xprefix.
    $query = [];
    # Sow all records of the block
    if (Blox::info('user','user-is-admin')) 
        include Blox::info('cms','dir')."/includes/disable-multirec-filters.php";

    $tab = Request::getTab($blockInfo, $tdd);
	$template->assign('tab', $tab);
    //$template->assign('filtersQuery', urldecode(Request::convertToQuery(Request::get($regularId)))); #497436375
    $template->assign('filtersQuery', Request::convertToQuery(Request::get($regularId)));
    
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";