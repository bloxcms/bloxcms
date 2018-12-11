<?php

    if (!Blox::info('user','id')) 
        Blox::execute('?error-document&code=403');

    if ($_GET['selected-user-id']){
        $selectedUserId =  Sql::sanitizeInteger($_GET['selected-user-id']);
        $template->assign('mode', 'editByAdmin');
        $userInfo = Acl::getUsers(['user-id'=>$selectedUserId])[0];
    } else {
        $userInfo = Blox::info('user');
        $selectedUserId = $userInfo['id'];
    }

    if ('user-is-editor-of-records' == $_GET['formula']) {
        $aa = Admin::getBlocksWithUserIdField();
        foreach ($aa as $a)
            $userObjects[] = ['object-id'=> $a, 'subject-id'=>$selectedUserId];
    } else # 'subject-id' need?
        $userObjects = Proposition::get($_GET['formula'], $selectedUserId, 'all');


    # user-is-editor-of-block
    # Additional special element for the array $userObjects
    if ('user-is-editor-of-block' == $_GET['formula'] || 'user-is-editor-of-records' == $_GET['formula'] || 'user-is-subscriber' == $_GET['formula']) {
        $addElementsForBlocks = function(&$userObjects)
        {
            foreach ($userObjects as $i => $aa) {
                $objectId = $aa['object-id'];
                $blockInfo = Blox::getBlockInfo($objectId);
                $userObjects[$i]['tpl'] = $blockInfo['tpl'];
                $userObjects[$i]['delegated-id'] = $blockInfo['delegated-id'];
                
                $pageInfo = Router::getPageInfoById(Blox::getBlockPageId($objectId));
                $userObjects[$i]['container-page'] = [
                    'id' => $pageInfo['id'],
                    'title' => $pageInfo['name'] ?: $pageInfo['title']
                ];
            }
        };
        $addElementsForBlocks($userObjects);
    }
    # user-sees-hidden-page
    elseif ('user-sees-hidden-page' == $_GET['formula'])
    {
        (function(&$userObjects) {
            foreach ($userObjects as $i => $aa) {
                $objectId = $aa['object-id'] ;
                $pageInfo = Router::getPageInfoById($objectId);
                $userObjects[$i]['title'] = $pageInfo['title'];
            }
        })($userObjects);
    }

    $headline = $terms['headline_'.$_GET['formula']];
    $template->assign('userObjects', $userObjects);
    $template->assign('headline', $headline);
    $template->assign('login', $userInfo['login']);
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/display.php";





    # TODO redo with parent
    function searchContainerPage($blockId, $refData, $reservedPage)
    {
        if ($refData) {
            foreach ($refData as $i => $arr ) {
                if ('blocks'== $i) {
                    foreach ($arr as $blockInfo) {
                        if ($reservedPage) {
                            if ($blockId == $blockInfo['block-id']) {
                                $GLOBALS['Blox']['container-page'] = $reservedPage;
                                return;
                            }
                        }
                        searchContainerPage($blockId, $blockInfo['ref-data'], $reservedPage);
                    }
                } elseif ('pages'== $i) {
                    foreach ($arr as $pageInfo) {
                        $reservedPage['id'] = $pageInfo['id'];
                        $reservedPage['title'] = $pageInfo['title'];
                        searchContainerPage($blockId, $pageInfo['ref-data'], $reservedPage);
                    }
                }
            }
        }
    }


