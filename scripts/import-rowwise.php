<?php

# NOT DEBUGGED
/* This script presents very fast import of data in csv format
 * But it uses the mysql's LOAD DATA LOCAL INFILE operation that requires special privileges
 * So this script ceased to be used.
 */
/*
    TODO:
    1.
    Before downloading the file, take one line of the file and analyze it by the number of columns, etc.and issue error messages, or automatically analyze the next line and stop if necessary.
    2.
    to do the export using SELECT statement ... INTO OUTFILE similar to importing with LOAD DATA INFILE
    3.
    For a more correct ping-pong algorithm, see scripts / mail-subscriptions-send.php
*/



        
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $template->assign('blockInfo', $blockInfo);
    $srcBlockId = $blockInfo['src-block-id'];
    $pagehref = Blox::getPageHref();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
    $recId = Sql::sanitizeInteger($_GET['rec']);
   
    Permission::addBlockPermits($srcBlockId);
    if (Permission::ask('record', $srcBlockId)['']['edit'])//get
        Url::redirect($pagehref,'exit');

    if ($srcBlockId) {
        $tbl = Blox::getTbl($blockInfo['tpl']);
        if (!Sql::tableExists($tbl)) {
            import_rowwise_log($regularId, 'no-data-table','red');
    } else {
        Blox::prompt(sprintf($terms['not-editable'], $regularId.'('.$blockInfo['tpl'].')'), true);
        Url::redirect($pagehref,'exit');
    }

    if ($_GET['phase']=='upload')
    {
        Store::set('import-rowwise-settings'.$srcBlockId, $_POST);
        $_SESSION['Blox']['import-rowwise'] = $_POST;
        if (empty($_FILES)) {
            import_rowwise_log($regularId, 'no-file-to-upload','red');
        }
        else
        {
            Files::cleanDir(Blox::info('site','dir').'/temp');
            if ($_FILES['import_file']['name'])
            {
                if ($_FILES['import_file']['error']===0)
                {
                    if ($_FILES['import_file']['type'] == 'application/zip')
                    {
                        $zip = new ZipArchive;
                        if ($zip->open($_FILES['import_file']['tmp_name']) === true)
                        {
                            if ($zip->extractTo(Blox::info('site','dir')."/temp"))
                            {
                                $zip->close();
                                $files = glob(Blox::info('site','dir')."/temp/*");
                                list($aa, $fileName) = Str::splitByMark($files[0], '/', true);
                                $renamedFileName = Upload::reduceFileName($fileName);
                                if ($renamedFileName != $fileName)
                                    rename($files[0], Blox::info('site','dir')."/temp/$renamedFileName");
                                chmod(Blox::info('site','dir')."/temp/$renamedFileName", 0644);

                                $_SESSION['Blox']['import-rowwise']['renamed-file-name'] = $renamedFileName; # not used
                                import_rowwise_log($regularId, 'file-uploaded-and-unzipped');
                            }
                            else {
                                $zip->close();
                                import_rowwise_log($regularId, 'could-not-unzip-uploaded-file','red');
                            }
                        }
                        else {
                            import_rowwise_log($regularId, 'could-not-unzip-uploaded-file','red');
                        }
                    }
                    # Move file $_FILES [ " ] ['tmp_name'] to temp with rename
                    else {
                        $renamedFile = Blox::info('site','dir')."/temp/".Upload::reduceFileName($_FILES['import_file']['name']);
                        if (move_uploaded_file($_FILES['import_file']['tmp_name'], $renamedFile)) {
                            chmod($renamedFile, 0644);
                            $_SESSION['Blox']['import-rowwise']['renamed-file'] = base64_encode($renamedFile);
                            import_rowwise_log($regularId, 'file-uploaded');
                        } else
                            import_rowwise_log($regularId, 'could-not-move-uploaded-file','red');
                    }
                }
                else {
                    import_rowwise_log($regularId, "uploadError{$_FILES['import_file']['error']}",'red');
                }
            }
            else
                import_rowwise_log($regularId, 'no-file-to-upload','red');

        }
    }
    elseif ('deleteBlockData' == $_GET['phase'])
    {
        if (!Admin::deleteChilds($blockInfo['tpl'], 'all', 'all', $srcBlockId))
            Blox::prompt(sprintf($terms['unable-to-delete-1'], $srcBlockId.'('.$blockInfo['tpl'].')'), true);
        $sql = 'DELETE FROM '.$tbl.' WHERE `block-id`=?';
        if (isEmpty(Sql::query($sql, [$srcBlockId])))
            Blox::error(sprintf($terms['unable-to-delete-2'], $srcBlockId.'('.$blockInfo['tpl'].')'));
        $sql = 'DELETE FROM '.Blox::info('db','prefix').'pseudopages WHERE key LIKE ?';
        if (isEmpty(Sql::query($sql, [$srcBlockId.'-%'])))
            Blox::prompt(sprintf($terms['unable-to-delete-3'], $srcBlockId.'-... ('.$blockInfo['tpl'].')'), true);
        import_rowwise_log($regularId, 'block-data-deleted');
    }
    elseif ('loadData' == $_GET['phase'])
    {
        # Emulate a HTTP client (to work with 'LOAD DATA LOCAL INFILE'), which calls the script.
        # script - public (not to login), obtained by renaming the file extension import-rowwise-load.aphp
        # The script must be separate because the HTTP client has a completely different session.
        # Tried to convey the basic session to the HTTP client. Session variables are passed, but after returning to the main script, the redirect does not work. It is better not to confuse two sessions.

        $loadFile = Blox::info('cms','dir')."/scripts/import-rowwise-load.";
        $output = '';
        if (file_exists($loadFile.'aphp'))
        {
            if (!copy($loadFile.'aphp', $loadFile.'php'))
                Blox::error("Could not copy file {$loadFile}aphp");
            else {
                $url = Blox::info('site','url')."/?import-rowwise-load";
                $url .= "&tpl=".urlencode($blockInfo['tpl']);
                $url .= "&src-block-id={$srcBlockId}";
                $url .= "&charset-of-file={$_SESSION['Blox']['import-rowwise']['charset-of-file']}";
                $url .= "&csv-ignore-lines={$_SESSION['Blox']['import-rowwise']['csv-ignore-lines']}";
                $url .= "&csv-enclosed={$_SESSION['Blox']['import-rowwise']['csv-enclosed']}";
                $url .= "&csv-escaped={$_SESSION['Blox']['import-rowwise']['csv-escaped']}";
                $url .= "&csv-new-line={$_SESSION['Blox']['import-rowwise']['csv-new-line']}";
                $url .= "&csv-terminated={$_SESSION['Blox']['import-rowwise']['csv-terminated']}";
                $url .= "&renamed-file={$_SESSION['Blox']['import-rowwise']['renamed-file']}";
                $url .= "&sorted-fields-list={$_SESSION['Blox']['import-rowwise']['sorted-fields-list']}";
                if ($_SESSION['Blox']['import-rowwise']['add-recid-column'])
                    $url .= "&add-recid-column=1";
                if ('replace' != $_SESSION['Blox']['import-rowwise']['insert-mode']) {
                    # Max rec-id
                    if ($_SESSION['Blox']['import-rowwise']['add-recid-column']) {
                        $sql = 'SELECT MAX(`rec-id`) AS maxRecId FROM '.$tbl.' WHERE `block-id`=? GROUP BY `block-id`';
                        if ($result = Sql::query($sql, [$srcBlockId])) {
                            if ($row = $result->fetch_assoc())
                                $url .= '&max-rec-id='.$row['maxRecId'];
                            $result->free();
                        }
                    }                    
                    # Max sort number
                    $sql = "SELECT MAX(sort) AS maxSortNum FROM $tbl WHERE `block-id`=$srcBlockId GROUP BY `block-id`";
                    if ($result = Sql::query($sql)) {
                        if ($row = $result->fetch_assoc())
                            $url .= '&max-sort-num='.$row['maxSortNum'];    
                        $result->free();
                    }
                }

                if ('replace' != $_SESSION['Blox']['import-rowwise']['insert-mode'])
                    Sql::query("ALTER TABLE $tbl DISABLE KEYS");
                
                /* Remade. See (@recCounter := @recCounter + 1)
                # Do temporary AUTO_INCREMENT for rec-id
                if ($_SESSION['Blox']['import-rowwise']['add-recid-column'])
                    Sql::query("ALTER TABLE $tbl MODIFY `rec-id` INT UNSIGNED NOT NULL AUTO_INCREMENT");
                */

                $output = fetchPage($url);

                /* Remade. See (@recCounter := @recCounter + 1)
                # make back the correct column type
                if ($_SESSION['Blox']['import-rowwise']['add-recid-column'])
                {
                    $type = Admin::reduceToSqlType('rec-id');
                    Sql::query("ALTER TABLE $tbl MODIFY `rec-id` $type");
                }
                */

                if ('replace' != $_SESSION['Blox']['import-rowwise']['insert-mode'])
                    Sql::query("ALTER TABLE $tbl ENABLE KEYS");

                if (!unlink($loadFile.'php'))
                    Blox::error("Could not unlink file: {$loadFile}php. Do it manualy!");
            }
        }
        else
            Blox::error("Temporary file {$loadFile}aphp does not exist");

        if (mb_strpos($output, 'dataAreLoaded') === false)
            import_rowwise_log($regularId, 'could-not-load-data','red');
        else
            import_rowwise_log($regularId, 'data-loaded','green', $note);
    }
    else # Form output
    {
        if (isset($_GET['reset'])) {
            unset($_SESSION['Blox']['import-rowwise']);
            Store::delete('import-rowwise-settings'.$srcBlockId);
        } else {
            if (empty($_SESSION['Blox']['import-rowwise']))
                $_SESSION['Blox']['import-rowwise'] = Store::get('import-rowwise-settings'.$srcBlockId);
        }
        #
        if ($blockInfo['tpl']) {
            $tdd = Tdd::get($blockInfo);
            $template->assign('tdd', $tdd);
        }

    }

    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    $template->assign('backUrl', "?edit&block={$srcBlockId}&rec={$recId}".$pagehrefQuery);
    include Blox::info('cms','dir')."/includes/display.php";


    function import_rowwise_log($regularId, $phase, $color=null, $note=null)
    {
        $_SESSION['Blox']['import-rowwise']['log'][] = ['phase'=>$phase, 'color'=>$color, 'note'=>$note];
        Url::redirect(Blox::info('site','url').'/?import-rowwise&block='.$regularId.'&phase='.$phase.$pagehrefQuery,'exit');
    }




    function fetchPage($url)
    {
        $curlCookieFile = Blox::info('site','dir')."/temp/cookies.txt";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $curlCookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $curlCookieFile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true); // return into a variable
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
