<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')))
        Blox::execute('?error-document&code=403');
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $terms['heading'] .= " <b>{$regularId}</b> ({$blockInfo['tpl']})";
    $refTypes = ['block'];
    $blockTreeParams = Tree::get($regularId, $refTypes); # Tree of special types
    $refData = $blockTreeParams['ref-data'];
    if ($refData['blocks'])
        $listOfBlocks = Tree::getBlocksHtm($refData['blocks']);
    else 
        $terms['inc-blocks'] = $terms['no-blocks'];
    $template->assign('listOfBlocks', $listOfBlocks);
    $template->assign('terms', $terms);
    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/display.php";  