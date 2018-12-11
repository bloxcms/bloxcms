<?php

    # SIMILAR: users-of-object.php    TODO: Combine both
            
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');

    $objectId = Sql::sanitizeInteger($_GET['obj']);
    
    # See same in users.php

    # All users
    $sql = "SELECT * FROM ".Blox::info('db','prefix')."groups ORDER BY name";
    if ($result = Sql::query($sql)) {
        while ($row = $result->fetch_assoc())
            $groups[] = $row;
        $result->free();
    }

    if ($groups && $_GET['formula'])
    {
        if ('group-is-editor-of-block' == $_GET['formula'] || 'group-is-subscriber' == $_GET['formula']) {            
            $blockInfo = Blox::getBlockInfo($objectId);
            $objectName = $blockInfo['tpl'];
        }
        
        if ('group-is-subscriber' == $_GET['formula']) {
            $tbl = Blox::info('db','prefix')."subscriptions";
            if (Sql::tableExists($tbl)) {
                # Is this block subscribed?
                $sql = 'SELECT `block-id` FROM '.$tbl.' WHERE `block-id`=?';
                if ($result = Sql::query($sql, [$blockInfo['src-block-id']])) {
                    $row = $result->fetch_row();
                    $result->free();
                    if ($row[0]) {
                        # As at 'user-is-editor-of-block'                        
                        $blockInfo = Blox::getBlockInfo($objectId);
                        $objectName = $blockInfo['tpl'];
                    } else
                        $objectIsntLiable = true;
                }
            } else 
                $objectIsntLiable = true;
        }
        /*
        elseif ('groupIsEditorOfRecords' == $_GET['formula']) {   
            $blockInfo = Blox::getBlockInfo($objectId);
            $objectName = $blockInfo['tpl'];
			$tdd = Tdd::get($blockInfo);
        	if ($tdd['params']['group-id-field'])
                $noGroupIdField = false;
            else
                $noGroupIdField = true;
            $template->assign('noGroupIdField', $noGroupIdField);
        }
        */
        elseif ('group-sees-hidden-page' == $_GET['formula']) {
            $pageInfo = Router::getPageInfoById($objectId);
            $objectName = $pageInfo['title'];
            # Find the list of blocks on this page to check editors
            $blockInfo2 = Blox::getBlockInfo($pageInfo['outer-block-id']);
            $refTypes = ['block'];
            $blockTreeParams = Tree::get($blockInfo2['block-id'], $refTypes);
            $refData = $blockTreeParams['ref-data'];
            if ($refData['blocks'])
                $arrayOfBlocksFromTree = Tree::getBlocks($refData['blocks']);
        }

        $template->assign('object-id', $objectId);
        $template->assign('objectName', $objectName);

        if (!$objectIsntLiable) {
            # Check user permissions
            foreach ($groups as $i => $group)
            {
                # user-is-editor
                if (Proposition::get('group-is-editor', $group['id']))
                    $groups[$i]['group-is-editor'] = true;
                if (Proposition::get($_GET['formula'], $group['id'], $objectId))
                    $groups[$i][$_GET['formula']] = true;
                # If you are retrieving a guest list, then you need to check the editors of the blocks on this page # to-do: axcept delegated blocks
                if ('group-sees-hidden-page' == $_GET['formula']) {
                    if ($arrayOfBlocksFromTree) {
                        foreach ($arrayOfBlocksFromTree as $blockId) {                            
                            if (Proposition::get('group-is-editor-of-block', $group['id'], $blockId)){# to-do # Can be accelerated if you extract all the blocks of this user
                                $groups[$i]['group-is-editor-of-block'] = true;
                                break;
                            }
                        }
                    }
                }
            }
            $template->assign('groups', $groups);
        }
        $template->assign('objectIsntLiable', $objectIsntLiable);
    }

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
