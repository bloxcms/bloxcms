<?php
    
    /*
    SIMILAR: 
        groups-of-object.php    
    TODO: 
        Combine both
    */
            
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');

    $objectId = Sql::sanitizeInteger($_GET['obj']);
    
    # SIMILAR: in users.php

    # All users
    $sql = "SELECT * FROM ".Blox::info('db','prefix')."users ORDER BY login";
    if ($result = Sql::query($sql)) {
        while ($row = $result->fetch_assoc())
            $users[] = $row;
        $result->free();
    }

    if ($users && $_GET['formula'])
    {
        if ('user-is-editor-of-block' == $_GET['formula'] || 'user-is-subscriber' == $_GET['formula']) {            
            $blockInfo = Blox::getBlockInfo($objectId);
            $objectName = $blockInfo['tpl'];
        }
        
        if ('user-is-subscriber' == $_GET['formula']) {
            $tbl = Blox::info('db','prefix')."subscriptions";
            if (Sql::tableExists($tbl)) {
                $sql = "SELECT `block-id` FROM $tbl WHERE `block-id`=?";
                if ($result = Sql::query($sql, [$blockInfo['src-block-id']])) {
                    $row = $result->fetch_row();
                    $result->free();
                    if ($row[0]) {
                        # SIMILAR in 'user-is-editor-of-block'                        
                        $blockInfo = Blox::getBlockInfo($objectId);
                        $objectName = $blockInfo['tpl'];
                    } else
                        $objectIsntLiable = true;
                }
            } else
                $objectIsntLiable = true;

        } elseif ('user-sees-hidden-page' == $_GET['formula']) {
            $pageInfo = Router::getPageInfoById($objectId);
            $objectName = $pageInfo['title'];
            # Find all blocks on this page to check on editors
            $blockInfo2 = Blox::getBlockInfo($pageInfo['outer-block-id']);
            $refTypes = ['block'];
            $blockTreeParams = Tree::get($blockInfo2['block-id'], $refTypes);
            $refData = $blockTreeParams['ref-data'];
            if ($refData['blocks'])
                $arrayOfBlocksFromTree = Tree::getBlocks($refData['blocks']);
        }

        $template->assign('objectId', $objectId);
        $template->assign('objectName', $objectName);

        if (!$objectIsntLiable) # objectIsntLiable - not subject to application of this law
        {
            # Check user permissions
            foreach ($users as $i => $user) {
                # user-is-admin
                if (Proposition::get('user-is-admin', $user['id']))
                    $users[$i]['user-is-admin'] = true;
                # user-is-activated
                if (Proposition::get('user-is-activated', $user['id']))
                    $users[$i]['user-is-activated'] = true;
                # user-is-editor
                if (Proposition::get('user-is-editor', $user['id']))
                    $users[$i]['user-is-editor'] = true;
                # Main proposition
                if (Proposition::get($_GET['formula'], $user['id'], $objectId))
                    $users[$i][$_GET['formula']] = true;
                # Additional
                # If you are retrieving a guest list, then you need to check the editors of the blocks on this page
                if ('user-sees-hidden-page' == $_GET['formula']){
                    if ($arrayOfBlocksFromTree){
                        foreach ($arrayOfBlocksFromTree as $blockId){                            
                            if (Proposition::get('user-is-editor-of-block', $user['id'], $blockId)){
                                # @todo Retrieve all the `block-id`s of this user to accelerate
                                $users[$i]['user-is-editor-of-block'] = true;
                                break;
                            }
                        }
                    }
                }
            }
            $template->assign('users', $users);
        }
        $template->assign('objectIsntLiable', $objectIsntLiable);
    }

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
