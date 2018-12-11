<?php

    /** 
     * NOT FOR EXTRADATA!
     * @todo Do multi-column sorting
     */

    if (!Blox::info('user','id'))
        Blox::execute('?error-document&code=403');
        
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $recId = Sql::sanitizeInteger($_GET['rec']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);

    $pagehref = Blox::getPageHref();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
    $tdd = Tdd::get($blockInfo);

    $template->assign('dataTypes', $tdd['types']);
    $template->assign('dataTitles', $tdd['titles']);
    $template->assign('editingFields', Admin::getEditingFields($tdd['titles'], $tdd['types']));
    $template->assign('backUrl', "?edit&block={$blockInfo['src-block-id']}&rec={$recId}".$pagehrefQuery);

    $pickKeyFields = $tdd['params']['pick']['key-fields'];
    if ($tdd['params']['backward']) {
        $backwardParam = true;
        $template->assign('backwardParam', $backwardParam);
    }
    $tdd['params'] = [];
    $tdd['params']['pick']['key-fields'] = $pickKeyFields;
    $template->assign('pickKeyFields', $pickKeyFields);
    
    # reverse and sort are mutually exclusive
    # Instead of backward use reverse, as there is $tdd['params']['backward']
    if ($_GET['reverse']) {
        $_GET['sort'] = '';
        if ($backwardParam)
            $_GET['backward']='';
        else
            $_GET['backward']='1';
    } elseif ($_GET['sort']) {
        $_GET['reverse'] = '';
        $_GET['backward']= '';
    } else {
        $_GET['sort'] = '';
        if ($backwardParam)
            $_GET['backward']='1';
        else
            $_GET['backward']='';
    }

    $_GET['block'] = $regularId;


    # We need backward and sort
    Request::set();

    # If there is the default sort in  tdd, then set it to request
    if (Request::get($regularId,'sort') === null && $tdd['params']['sort'])
        foreach ($tdd['params']['sort'] as $k=>$v)
            if (Str::isInteger($k)) # Digits                
                Request::add([$regularId=>['sort'=>[$k=>$v]]]);

    if (!Admin::checkSortByColumnsFilters($regularId, $tdd['params']['pick']['key-fields']))
        Blox::error("error in Admin::checkSortByColumnsFilters() sort.php");

    # Create a request to pass it to the script sort-update.php
    if ($_GET['reverse'])
        $reverseSortQuery = "&backward={$_GET['backward']}";
    elseif ($_GET['sort']) {
        foreach (Request::get($regularId,'sort') as $field => $order)
            break;
        $reverseSortQuery = "&sort[$field]=$order";
    }

    $template->assign('reverseSortQuery', $reverseSortQuery);
    $tab = Request::getTab($blockInfo, $tdd);
	$template->assign('tab', $tab);

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";