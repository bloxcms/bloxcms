<?php
    if (!(Blox::info('user','user-is-admin') || !Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
    $editBlock = Sql::sanitizeInteger($_GET['edit-block']);
    $editRec = Sql::sanitizeInteger($_GET['edit-rec']);
    $editBlockInfo = Blox::getBlockInfo($editBlock);
    $editSrcBlockId = $editBlockInfo['src-block-id'];
    $_POST['edit-field'] = Sql::sanitizeInteger($_POST['edit-field']);
    $_POST['parent-field'] = Sql::sanitizeInteger($_POST['parent-field']);
    $_POST['parent-list-rec-id'] = Sql::sanitizeInteger($_POST['parent-list-rec-id']);
    $_POST['select-list-block-id'] = Sql::sanitizeInteger($_POST['select-list-block-id']);

    # Cascade transition from one edit window to the previous one when editing select-given
    if ($_GET['direction'] == 'left') { 
        if ($aa = end($_SESSION['Blox']['edit-referrers']))
            $bb = $aa;
        else
            $bb = Blox::getPageHref();
    } else {
        $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
        $bb = '?edit&block='.$editBlock.'&rec='.$editRec.$pagehrefQuery;
    }

    if (empty($_POST['select-list-block-id']))
        Url::redirect($bb,'exit');
    else
        Url::redirect($bb);
    # Need?
    if ($_POST['select-list-block-id'] == $_SESSION['Blox']['select-lists'][$editRec][$_POST['edit-field']]['select-list-block-id']) {
        Blox::prompt($terms['same-list-assigned']);
    } else {
        # Replace independent list
        if (empty($_POST['parent-field'])) {
            $sTbl = Blox::info('db','prefix').'selectlistblocks';
            $sql = 'DELETE FROM '.$sTbl.' WHERE `edit-block-id`=? AND `edit-field`=?';
            Sql::query($sql, [$editSrcBlockId, $_POST['edit-field']]);
            $sql = 'INSERT '.$sTbl.' (`edit-block-id`, `edit-field`, `select-list-block-id`) VALUES (?, ?, ?)';
            Sql::query($sql, [$editSrcBlockId, $_POST['edit-field'], $_POST['select-list-block-id']]);
            # Delete old data
            # Not done. See 'Рассуждения/Select-данные/..'
            #deleteOldSelectData();
        }
        # Replace the dependent list
        else {
            $dsTbl = Blox::info('db','prefix').'dependentselectlistblocks';
            $parentListBlockId = $_SESSION['Blox']['select-lists'][$editRec][$_POST['parent-field']]['select-list-block-id'];

            # Delete old data
            # Not done. See 'Рассуждения/Select-данные/..'
            /*
                $sql = "SELECT `select-list-block-id` FROM ".Blox::info('db','prefix')."dependentselectlistblocks WHERE `parent-list-block-id`=$parentListBlockId AND `parent-list-rec-id`={$_POST['parent-list-rec-id']}";
                $result = Sql::query($sql);
                if ($row = $result->fetch_assoc())
                    $oldSelectListBlockId = $row['select-list-block-id'];
                $result->free();

                if ($oldSelectListBlockId)
                    deleteOldDependentSelectData($oldSelectListBlockId);
            */

            Sql::query(
                'DELETE FROM '.$dsTbl.' WHERE `parent-list-block-id`=? AND `parent-list-rec-id`=?', 
                [$parentListBlockId, $_POST['parent-list-rec-id']]
            );
            Sql::query(
                'INSERT '.$dsTbl.' (`parent-list-block-id`, `parent-list-rec-id`, `select-list-block-id`) VALUES (?, ?, ?)', 
                [$parentListBlockId, $_POST['parent-list-rec-id'], $_POST['select-list-block-id']]
            );
        }
        Blox::prompt($terms['delete-manualy'],  true);
    }



# Not done. See 'Рассуждения/Select-данные/..'
# Working, but not fully understood
/*

    function deleteOldSelectData()
    {
        # Determine if there are dependent lists. The data directly to the dependent list, it is better to remove as it is false information. The next generations not yet touched, because the relationship is there prividenie.
        $dependentFieldsSet="";
        $tdd = Tdd::get($blockInfo);
        
        $typesDetailsS = Tdd::getTypesDetails($tdd[XXX'types'], ['select']);
        if ($typesDetailsS)
        {
            foreach ($typesDetailsS as $field=>$aa)
            {
                # The dependent field must be declared below
                # Dependent field should be bolshy key. That is $_POST ['edit-field'] < $field. But while this is not taken into account.
                # Some field refers to an editable field as a parent option.
                if ($_POST['edit-field'] != $field && $_POST['edit-field'] == $aa['params']['parentfield'][0])
                    $dependentFieldsSet=", dat$field=0";
            }
        }
        # In the current field and in the dependent fields of the current block, reset the data.
        $tbl = "`".Blox::getTbl($_POST['edit-tpl'])."`";
        $sql = "UPDATE $tbl SET dat{$_POST['edit-field']}=0 $dependentFieldsSet WHERE `block-id`={$editSrcBlockId}";
        Sql::query($sql);
    }

    function deleteOldDependentSelectData($oldSelectListBlockId)
    {
        # On the first call to f-AI, when $parent List Block Id is known, but $old Select List Block Id is unknown
        # # On a deeper call, by contrast, $parent List Block Id is unknown and $old Select List Block Id is known.
        # # You can take this into account and make two arguments, but we will not complicate the f-th

        # In the dependentselectlistblocks table find 'parent-list-block-id' via select-list-block-id
        $sql = "SELECT `parent-list-block-id`, `parent-list-rec-id` FROM ".Blox::info('db','prefix')."dependentselectlistblocks WHERE `select-list-block-id`=$oldSelectListBlockId";
        $result = Sql::query($sql);
        if ($row = $result->fetch_assoc()) {
            $parentListBlockId = $row['parent-list-block-id'];
            $parentListRecId = $row['parent-list-rec-id'];
        }
        $result->free();
        if ($parentListBlockId) {
            # In the table selectlistblocks find 'edit-block-id` through `select-list-block-id` which is equal to the previous' parent-list-block-id`
            $sql = "SELECT `edit-block-id`, `edit-field` FROM ".Blox::info('db','prefix')."selectlistblocks WHERE `select-list-block-id`=".$parentListBlockId;
            $result = Sql::query($sql);
            # If this entry is in selectlistblocks
            if ($row = $result->fetch_assoc()) {      
                $result->free();         
                $editBlockInfo = Blox::getBlockInfo($row['edit-block-id']);
                $tdd = Tdd::get($editBlockInfo);
                $tbl = "`".Blox::getTbl($editBlockInfo['tpl'])."`";
                $typesDetailsS = Tdd::getTypesDetails($tdd[XXX'types'], ['select']);
                # searching in the descriptor field in which there is a parameter equal parentField `edit-field`
                foreach ($typesDetailsS as $field=>$aa) {
                    if ($aa['params']['parentfield'][0] == $row['edit-field']) {
                        # all data in this field is reset
                        $sql = "UPDATE $tbl SET dat$field=0 WHERE dat{$aa['params']['parentfield'][0]}=$parentListRecId AND `block-id`={$row['edit-block-id']}";
                        //Sql::query($sql);//Off tepmorary
                        //Blox::error("Deleting the data. (deleteOldDependentSelectData)");
                    } else {
                        # consider `parent-list-block-id` to be `select-list-block-id ' and repeat (1)
                        //deleteOldDependentSelectData($row['parent-list-block-id']);// Off tepmorary
                    }
                }
            } else {
                //deleteOldDependentSelectData($parentListBlockId); # If there is no rec in selectlistblocks not, then check in dependentselectlistblocks
            }
        }
    }
*/
