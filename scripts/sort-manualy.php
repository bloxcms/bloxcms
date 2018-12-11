<?php
    # NOT FOR EXTRADATA!
    
    if (!Blox::info('user','id')) 
        Blox::execute('?error-document&code=403');
    Request::set(); # Place it higher than Tdd::get() for the $defaults = Request::get()
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $recId = Sql::sanitizeInteger($_GET['rec']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);
    $tpl = $blockInfo['tpl'];
    $pagehref = Blox::getPageHref();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
    $tdd = Tdd::get($blockInfo);
    $template->assign('dataTypes', $tdd['types']);
    $template->assign('dataTitles', $tdd['titles']);

    ############################## fetch the table ##############################
    # Cancel all the parameters, except backward
    $aa = $tdd['params'];
    $tdd['params'] = [];
    $tdd['params']['backward'] = $aa['backward'] ?: false;
    $tdd['params']['no-edit-bar'] = ($aa['no-edit-bar'] && !Blox::info('user','user-is-admin')) ? true : false;
    $params['heading'] = $aa['heading'] ?: '';
    $template->assign('params', $params);
    
    $settings = Store::get('editSettings'.$blockInfo['src-block-id']);//$xprefix.
    $query = [];
    # Show all records of the block
    if (Blox::info('user','user-is-admin')) 
        include Blox::info('cms','dir')."/includes/disable-multirec-filters.php";
    $tab = Request::getTab($blockInfo, $tdd);
	$template->assign('tab', $tab);
    $template->assign('editingFields', Admin::getEditingFields($tdd['titles'], $tdd['types']));    

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";