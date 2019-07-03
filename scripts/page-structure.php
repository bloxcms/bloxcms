<?php

if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')))
    Blox::execute('?error-document&code=403');
$regularId = Sql::sanitizeInteger($_GET['block']);
$blockInfo = Blox::getBlockInfo($regularId);
$terms['heading'] .= ' <b>'.$_GET['page'].'</b> ('.Router::getPageInfoById($_GET['page'])['name'].')';
$refTypes = ['block'];
$blockTreeParams = Tree::get($regularId, $refTypes); # Tree of special types
$refData = $blockTreeParams['ref-data'];

$cmsUrl = Blox::info('cms','url');  
$listOfBlocks .= '<ul style="margin-left:0">';

    if ($blockInfo['delegated-id'])
        $aa = '<span style="color:#900">'.$blockInfo['id'].'</span>';
    else
        $aa = $blockInfo['id'];
    $listOfBlocks .= '<li class="last" style="background:none"><span class="list-item" style="background:none">&nbsp;</span>'.(Admin::getEditButton($blockInfo['id'], ['block-info'=>$blockInfo])).' <b>'.$aa.'</b>';
        if ($blockInfo['tpl'])
            $listOfBlocks .= ' ('.$blockInfo['tpl'].')';            
        if ($refData['blocks'])
            $listOfBlocks .= Tree::getBlocksHtm($refData['blocks']);
        else 
            $terms['inc-blocks'] = $terms['no-blocks'];
    $listOfBlocks .= '</li>';    
$listOfBlocks .= '</ul>';
$template->assign('listOfBlocks', $listOfBlocks);
$template->assign('terms', $terms);
include Blox::info('cms','dir').'/includes/button-cancel.php';
include Blox::info('cms','dir').'/includes/display.php';