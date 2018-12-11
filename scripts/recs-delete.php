<?php
	# Removes recs of the block (deletes nested blocks, pages, files too)

//qq($_POST['recs']);
//return;
    if ($_GET['xprefix'])
        $xprefix = 'x';
    else
        $xprefix = '';
    Request::set(); # For Request::get() in tdd
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $recId = Sql::sanitizeInteger($_GET['rec']);    
    $blockInfo = Blox::getBlockInfo($regularId);
    $srcBlockId = $blockInfo['src-block-id'];
    $tpl = $blockInfo['tpl'];    
    $pagehref = Blox::getPageHref();

    $tdd = Tdd::get($blockInfo);

    if (Blox::info('user','id'))
        ;
    else {
        if ($tdd[$xprefix.'params']['public']['editing-allowed'] && ($_SESSION[$xprefix.'fresh-recs'][$srcBlockId][$_GET['which']]))   # public
            ;
        elseif (!Blox::ajaxRequested())
            Url::redirect($pagehref,'exit');
    }

    # Are there special data types in the template
    $typesNames1 = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['page','block'], 'only-name');
    if (!Blox::info('user','user-is-admin') && $typesNames1 && !Blox::ajaxRequested()){
        Url::redirect($pagehref,'exit');
        Blox::prompt($terms['spec-data-types'],  true);
    }

    $tbl = Blox::getTbl($tpl, $xprefix);
    $toAddAnchor = false;

    # Record Id is passed via delete button 
    if (preg_match("/^\d+$/", $_GET['which'], $matches))
    {
        if (!Admin::removeRec($tpl, $matches[0], $srcBlockId, $tbl))
            Blox::error(sprintf($terms['failed-to-delete-rec'], $matches[0], $srcBlockId.'('.$tpl.')'));
        unset($_SESSION[$xprefix.'fresh-recs'][$srcBlockId][$matches[0]]);
    }
    else
    {
        switch ($_GET['which'])
        {
            case 'all':
                # Are there special data types in the template
                $typesNames2 = Tdd::getTypesDetails($tdd[$xprefix.'types'], ['page','block','file','select'], 'only-name');
                # No special data types
                if (empty($typesNames2)) {
                    # Delete without check
                    $sql = "DELETE FROM $tbl WHERE `block-id`=?";
                    if (isEmpty(Sql::query($sql, [$srcBlockId])))
                        Blox::error(sprintf($terms['failed-to-delete-all-recs'], $srcBlockId));
                    
                    if (empty($xprefix)) {
                        $sql = 'DELETE FROM '.Blox::info('db','prefix').'pseudopages WHERE `key` LIKE ?';
                        if (isEmpty(Sql::query($sql, [$srcBlockId.'-%'])))
                            Blox::error(sprintf($terms['failed-to-delete-from-pseudopages'], $srcBlockId.'-%'));
                    }
                }
                # line by line
                else {
                    $recs = [];
                    # There is block Id
                    $sql = "SELECT `rec-id` FROM $tbl WHERE `block-id`=?";
                    if ($result = Sql::query($sql, [$srcBlockId])) {
                        while ($row = $result->fetch_row())
                            if (!Admin::removeRec($tpl, $row[0], $srcBlockId, $tbl))
                                Blox::error(sprintf($terms['failed-to-delete-rec'], $row[0], $srcBlockId.'('.$tpl.')'));
                        $result->free();
                    }
                }
                unset($_SESSION['Blox']['fresh-recs'][$srcBlockId]);
                break;
            case 'current':
                if (!Admin::removeRec($tpl, $recId, $srcBlockId, $tbl))
                    Blox::error(sprintf($terms['failed-to-delete-rec'], $recId, $srcBlockId.'('.$tpl.')'));
                unset($_SESSION['Blox']['fresh-recs'][$srcBlockId][$recId]);
                break;
            case 'selected':
                foreach ($_POST['recs'] as $recId){
                    if (!Admin::removeRec($tpl, $recId, $srcBlockId, $tbl))
                        Blox::error(sprintf($terms['failed-to-delete-rec'], $recId, $srcBlockId.'('.$tpl.')'));
                    unset($_SESSION['Blox']['fresh-recs'][$srcBlockId][$recId]);
                }
                break;
            case 'range':
                $sql = "SELECT `rec-id` FROM $tbl WHERE `block-id`=? ";
                $sqlParams = [$srcBlockId];
                $rangeExists = false;
                foreach ($_POST['dat'] as $field => $dat){
                    $field = Sql::sanitizeInteger($field);
                    if ($dat['from'] or ("0" === $dat['from'])){
                        if ($dat['to'] or ("0" === $dat['to'])){
                            if ($_POST['typeName'][$field] == 'datetime') {
                                $sql .= " AND STR_TO_DATE(dat{$field},'%Y-%m-%d %H:%i:%s') >= ? AND STR_TO_DATE(dat{$field},'%Y-%m-%d %H:%i:%s') <= ?";   // quote values in order to accept both numbers and strings
                                $sqlParams[] = $dat['from'];
                                $sqlParams[] = $dat['to'];
                            } else {
                                $sql .= " AND dat{$field} >= ? AND dat{$field} <= ?";   // quote values in order to accept both numbers and strings
                                $sqlParams[] = $dat['from'];
                                $sqlParams[] = $dat['to'];
                            }
                            $rangeExists = true;
                        }
                    }
                }
                if ($rangeExists){
                    if ($result = Sql::query($sql, $sqlParams)) {
                        while ($row = $result->fetch_row()) {
                            if (!Admin::removeRec($tpl, $row[0], $srcBlockId, $tbl))
                                Blox::error(sprintf($terms['failed-to-delete-rec'], $row[0], $srcBlockId.'('.$tpl.')'));
                            unset($_SESSION[$xprefix.'fresh-recs'][$srcBlockId][$row[0]]);
                        }
                        $result->free();
                    }
                }
                break;
        }
    }

    if (!Blox::ajaxRequested()) {
        if (isset($_GET['edit-field']))
            Url::redirect($_SERVER['HTTP_REFERER'].'&mode=delete&edit-field='.$_GET['edit-field']);
        else
            Url::redirect($pagehref);
    }

    # Template Data Update Prehandler 
    $templateDeleteRecsPosthandlerFile = Blox::info('templates', 'dir').'/'.$tpl.'.tdph'; # deliberately lengthened
    if (file_exists($templateDeleteRecsPosthandlerFile)) {
        $_SESSION['Blox']['dpdat'][$regularId] = (function($templateDeleteRecsPosthandlerFile) {// not $regularId !! // same func is in Blox::getBlockHtm.php
            include $templateDeleteRecsPosthandlerFile;
            return $dpdat;
        })($templateDeleteRecsPosthandlerFile); # send to page.php
        //$_SESSION['Blox']['dpdat'][$regularId] = $getHandledData($templateDeleteRecsPosthandlerFile); // 
    }

    if (Blox::ajaxRequested()) {
        /**
         * If you want get json response by $.ajax({url:'?recs-delete&block=..., dataType: 'json'}), then add here:
         * header('Content-type: application/json; charset=utf-8');
         * echo '{}';
         */
        session_cache_limiter('nocache'); // Simpler way of making sure all no-cache headers get sent and understood by all browsers, including IE.
        header('Expires: ' . gmdate('r', 0));
        header('Content-type: application/json; charset=utf-8');
        echo json_encode('{}'); 
    }




