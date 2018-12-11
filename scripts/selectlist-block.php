<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');

    $limit = 10;
    $editRec = Sql::sanitizeInteger($_GET['edit-rec']);
    $selectListTpl = $_SESSION['Blox']['select-lists'][$editRec][$_GET['edit-field']]['tpl'];
    $selectListField = $_SESSION['Blox']['select-lists'][$editRec][$_GET['edit-field']]['edit'];

    if ($selectListTpl && $selectListField)
    {
        # Get blocks which have the same template
        $sql = 'SELECT id FROM '.Blox::info('db','prefix').'blocks WHERE tpl=?';
        if ($result = Sql::query($sql, [$selectListTpl])) {
            $selectListBlocks = [];
            $tbl = Blox::getTbl($selectListTpl);
            while ($row = $result->fetch_assoc()) {
                $selectListBlockId = Sql::sanitizeInteger($row['id']);
                if ($selectListBlockId) {
                    $limitplus = $limit + 1;
                    $fieldList = ($selectListField == 'rec') ? '`rec-id`' : 'dat'.$selectListField;                    
                    $sql = 'SELECT '.$fieldList.' FROM '.$tbl.' WHERE `block-id`=? LIMIT ?';
                    $items = [];
                    $i = 0;
                    if ($result2 = Sql::query($sql, [$selectListBlockId, $limitplus])) {
                        while ($row = $result2->fetch_row()) {
                            $i++;
                            $items[$i] = $row[0];
                        }
                        $result2->free();
                    }

                    if ($i == $limitplus)
                        $items[$i] = '&middot&middot&middot';
                    else {
                        for ($j=$i+1; $j<=$limitplus ;$j++)
                            $items[$j] = '';
                    }

                    $blockPageId = Blox::getBlockPageId($selectListBlockId);
                    $pageInfo = Router::getPageInfoById($blockPageId);
                    # If select-datum for which the list is assigned is dependent, then deactivate busy lists.
                    $parentListParams = getParentListParams($selectListBlockId, $editRec, $terms);
                    $selectListBlocks[$selectListBlockId] = ['items'=>$items, 'parentListParams'=>$parentListParams, 'block-page' => ['id'=>$blockPageId, 'title'=>$pageInfo['title']]];
                }
            }
            $result->free();
        }

        $selectListTdd = Tdd::get(['tpl'=>$selectListTpl]); # KLUDGE: ['tpl'=>$selectListTpl]
        $template->assign('selectListTpl', $selectListTpl);
        $template->assign('selectListField', $selectListField);
        $template->assign('selectListFieldName', $selectListTdd[titles][$selectListField]);
        $template->assign('editTpl', $_GET['edit-tpl']);
        $template->assign('editBlock', Sql::sanitizeInteger($_GET['edit-block']));
        $template->assign('editRec', $editRec);
        $template->assign('editField', Sql::sanitizeInteger($_GET['edit-field']));
        $template->assign('editFieldTitle', $_GET['edit-field-title']);
        $template->assign('selectListBlocks', $selectListBlocks);
    }

    # Edit select-datum without leaving the edit window
    if ($_GET['direction'] == 'right') {
        if ($aa = Str::getStringBeforeMark($_SERVER['HTTP_REFERER'], '&direction')) # Remove the tail, not to duplicate
            $_SESSION['Blox']['edit-referrers'][] = $aa;
        else
            $_SESSION['Blox']['edit-referrers'][] = $_SERVER['HTTP_REFERER'];
        $cancelUrl = $_SERVER['HTTP_REFERER'].'&direction=left';
        $template->assign('direction', '&direction=left');
    }
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";


    function getParentListParams($selectListBlockId, $editRec, $terms)
    {
        $parentListField = $_SESSION['Blox']['select-lists'][$editRec][$_GET['parent-field']]['edit'];
        $sql = "SELECT `parent-list-block-id`, `parent-list-rec-id` FROM ".Blox::info('db','prefix')."dependentselectlistblocks WHERE `select-list-block-id`=?";
        if ($result = Sql::query($sql, [$selectListBlockId])) {
            $passed=false;
            while ($row = $result->fetch_assoc()) {
                if ($passed) {
                    echo sprintf($terms['more-than-one-record'], $selectListBlockId);
                    break;
                }
                $parentListItem = Dat::get(
                    ['src-block-id'=>$row['parent-list-block-id'], 'tpl'=>Blox::getBlockInfo($row['parent-list-block-id'])['tpl']], # KLUDGE: May be 'tpl'=> is same for all
                    ['rec'=>$row['parent-list-rec-id']], 
                    $xprefix # KLUDGE: Not in use!!!
                )[$parentListField];
                    
                $parentListParams = ['block-id'=>$row['parent-list-block-id'], 'rec-id'=>$row['parent-list-rec-id'], 'edit'=>$parentListField, 'item'=>$parentListItem];
                $passed = true;
                return $parentListParams;
            }
            $result->free();
        }
    }
