<?php
    /*
     * Ping-pong algorithm.
     * Script returns a reports $scriptResult.
     * Template do dispatching (redirects).
     * Same as mail-block-send.php
     * 
     * @todo Rename temporary table newslettersrecipients to subscribers
     * @todo Rename "temp/newsletters/" to "temp/newsletters/subscription-letters"
     */


    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')))
        Blox::execute('?error-document&code=403');


    ############# Phase 1. Make a list of recipients  ( table newslettersrecipients) #############
    if ('get-recipients'==$_GET['phase'])
    {
        if (isset($_POST))
        {
            # Activate and deactivate of subscriptions
            foreach ($_POST['activated'] as $blockId => $value) {
                $sql = "UPDATE ".Blox::info('db','prefix')."subscriptions SET `activated`=? WHERE `block-id`=?";
                Sql::query($sql, [$value, $blockId]);
            }
            # save settings of letters                        
            Store::set(newsletter-params, $_POST['newsletter-params']);
            if (Sql::tableExists(Blox::info('db','prefix').'newslettersrecipients')) {
                $sql = "DELETE FROM ".Blox::info('db','prefix')."newslettersrecipients";
                Sql::query($sql);
            } else {
                $sql = "CREATE TABLE IF NOT EXISTS ".Blox::info('db','prefix')."newslettersrecipients (
                    `user-id` int(6) NOT NULL PRIMARY KEY,
                    `newsletter-code` varchar(332) NOT NULL DEFAULT '',
                    unsent tinyint(1) unsigned NOT NULL default 0
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
                Sql::query($sql);
            }

            # List of all subscriptions
            $sql = "SELECT `block-id` FROM ".Blox::info('db','prefix')."subscriptions";
            if ($result = Sql::query($sql)) {
                while ($row = $result->fetch_row())
                    $subscriptionsStates[$row[0]] = false;
                $result->free();
            }

            # The recipients list
            if ($_POST['test-sending']){
                $props[0]=['subject-id'=>Blox::info('user','id'), 'object-id'=>0];
                Store::set('testUserParams', Blox::info('user'));
            } else {
                $props = Proposition::get('user-is-subscriber', 'all', 'any');
                Store::delete('testUserParams');
            }

            foreach ($props as $subjobj)
            {
                if ($_POST['test-sending']) {
                    # Generate of mailing code 
                    $code = '';
                    foreach ($subscriptionsStates as $v)
                        $code .= '1';
                } else {
                    # List of user's subscriptions
                    $props2 = Proposition::get('user-is-subscriber', $subjobj['subject-id'], 'all');
                    # List all user's subscriptions
                    $states = $subscriptionsStates;
                    foreach ($props2 as $aa) {
                        if (isset($subscriptionsStates[$aa['object-id']]))
                            $states[$aa['object-id']] = true;
                    }
                    # Generate a mailing code 
                    $code = '';
                    foreach ($states as $v) {
                        if ($v)
                            $code .= '1';
                        else
                            $code .= '0';
                    }
                }

                $sql = "INSERT ".Blox::info('db','prefix')."newslettersrecipients SET `user-id`=?, `newsletter-code`=?";
                Sql::query($sql, [$subjobj['subject-id'], $code]);
            }

            Report::reset();
            $scriptResult = 0;
        } else {
            $scriptResult = 1;
        }
        Report::add($terms['get-recipients'], $scriptResult);
    }

    ############# Phase 2. Write the letters in the folder temp/newsletters #############
    elseif ('create-newsletters'==$_GET['phase'])
    {           
        $newsletterParams = Store::get('newsletter-params');
        # List of all subscriptions
        $sql = "SELECT * FROM ".Blox::info('db','prefix')."subscriptions";//	`block-id` 	`last-mailed-rec` 	`activated`
        if ($result = Sql::query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $pageInfo = Router::getPageInfoById(Blox::getBlockPageId($row['block-id']));
                $row['page-title'] = $pageInfo['title'];
                $subscriptions[] = $row;
            }
            $result->free();
        }

        # A list of unique letter codes
        $newslettersDir = Files::makeTempFolder('newsletters');
        $sql = "SELECT `newsletter-code` FROM ".Blox::info('db','prefix')."newslettersrecipients GROUP BY `user-id`";
        if ($result2 = Sql::query($sql)) {
            Blox::addInfo(['user' => ['user-as-visitor' => true]]);
            # For each letter code
            while ($row2 = $result2->fetch_row()) {
                $letterHtm = '';
                $newsletterCode = $row2[0];
                $bits = str_split($newsletterCode);
                # Build the body of the news letter
                foreach ($subscriptions as $i=>$subscription){
                    if ($subscription['activated'] && (int)$bits[$i]) {
                        Request::add([$subscription['block-id']=>['limit'=>999]]);# KLUDGE: Otherwise taken tdd limit will be taken
                        Request::add([$subscription['block-id']=>['pick'=>['rec'=>['gt'=>$subscription['last-mailed-rec']]]]]);
                        if ($blockHtm = Blox::getBlockHtm($subscription['block-id']))
                            $letterHtm .= "<h2>{$subscription['page-title']}</h2>\n<br />$blockHtm\n<br /><br /><br />\n";
                    }
                }

                # Complete building of letter (with the document head)
                #Replace relative links by absolute
                $pageHtm = Email::createHtmlDoc($letterHtm);
                # TODO Use Url::convertToAbsolute() for https
                $pageHtm = preg_replace(
                    "/((href|src)\s*?=\s*?('|\")?)(?!http:)(\.\/)?/iu",
                    "$1".Blox::info('site','url'), 
                    $pageHtm
                );
                $email = makeEmail($newsletterParams['email-from'], $newsletterParams['name_from'], $pageHtm);
                file_put_contents($newslettersDir.'/'.$newsletterCode.'.body',  $email['body']);
                file_put_contents($newslettersDir.'/'.$newsletterCode.'.header',$email['header']);
            }
            $result2->free();
        }

        # Update before the mailing, not to update after repeated attempts of failed mailings, as during this time there may be new records.
        # If it is non-test mailing
        if (!Store::get('testUserParams'))
            updatelastMailedRecs();
        Report::add($terms['create-newsletters']);
        $scriptResult = 0;
    }

    ############# Phase 3. Mailing #############
    elseif ('send-letters'==$_GET['phase'])
    {
        $newsletterParams = Store::get('newsletter-params');
        # Create emails list from the folder: newsletters
        $newslettersFiles = glob(Blox::info('site','dir').'/temp/newsletters/*.body');
        # If the emails were deleted then re-create them
        foreach ($newslettersFiles as $bodyFile) {
            list($aa, $bodyFileName) = Str::splitByMark($bodyFile, '/', true);
            $ebody = file_get_contents($bodyFile);
            if (isset($_POST['send-again']))
                $sendAgain = $_POST['send-again'];
            else
                $sendAgain = ['failed'=>true, 'untouched'=>true];

            if ($sendAgain['untouched'] || $sendAgain['failed']) {
                if (!$sendAgain['untouched'])
                    $where = "AND unsent=1";
                elseif (!$sendAgain['failed'])
                    $where = "AND unsent=0";
                else
                    $where = "";

                # Create the list of recipients of this letter
                $unsent = false;
                list($bodyFileRootName, $aa) = Str::splitByMark($bodyFileName, '.', true);
                $headerFile = Blox::info('site','dir')."/temp/newsletters/$bodyFileRootName.header";
                $eheader = file_get_contents($headerFile);
                $sql = "SELECT `user-id` FROM ".Blox::info('db','prefix')."newslettersrecipients WHERE `newsletter-code`=? $where";
                if ($result = Sql::query($sql, [$bodyFileRootName])) {
                    # For each recipient
                    while ($row = $result->fetch_assoc()) {
                        $sql = 'SELECT login, email FROM '.Blox::info('db','prefix').'users WHERE id=?';
                        if ($result2 = Sql::query($sql, [$row['user-id']])) {
                            if ($row2 = $result2->fetch_assoc()) {
                                $to = '"'.headerEncode($row2['login'], 'utf-8').'" <'.$row2['email'].'>';
                                if (mb_send_mail($to, $newsletterParams['subject'], $ebody, $eheader)) {
                                    # Remove a user from the recipient list
                                    Sql::query('DELETE FROM '.Blox::info('db','prefix').'newslettersrecipients WHERE `user-id`=?', [$row['user-id']]);
                                } else {
                                    # mark as unsent
                                    Sql::query('UPDATE '.Blox::info('db','prefix').'newslettersrecipients SET unsent=1 WHERE `user-id`=?', [$row['user-id']]);
                                    $unsent = true;
                                }
                            }

                        }
                        # TODO: If there are 10 unsent letters, then stop sending and report
                    }
                    $result->free();
                }
                # delete the letter if it is sent to all
                if (!$unsent) {
                    unlink($bodyFile);
                    unlink($headerFile);
                }
            }


        }
        Report::add($terms['mailing-completed']);
        $scriptResult = 0;

    }
    ############# Phase 4. Report #############
    elseif ('report'==$_GET['phase']) {
        # Still not sent
        $sql = "SELECT `user-id`, unsent FROM ".Blox::info('db','prefix')."newslettersrecipients WHERE unsent=0";
        if ($result = Sql::query($sql)) {
            $numOfunsent['untouched'] = $result->num_rows;
            $result->free();
        }

        # Failed to send
        $sql = "SELECT `user-id`, unsent FROM ".Blox::info('db','prefix')."newslettersrecipients WHERE unsent=1";
        if ($result = Sql::query($sql)) {
            $numOfunsent['failed'] = $result->num_rows;
            $result->free();
        }

        # Successful mailing
        if (empty($numOfunsent['failed']) && empty($numOfunsent['untouched'])) {
            # Updating subscriptions, specify which records were sent last time
            include Blox::info('cms','dir')."/includes/button-cancel.php";
        # Failed to send
        } else {
            $template->assign('numOfunsent', $numOfunsent);
            include Blox::info('cms','dir')."/includes/buttons-submit.php";
        }
                
        $template->assign('newsletters_sums', Store::get('newsletters_sums'));
        $template->assign('testUserParams', Store::get('testUserParams'));
    } elseif ('delete-letters'==$_GET['phase']) {
        $sql = "DROP TABLE IF EXISTS ".Blox::info('db','prefix')."newslettersrecipients";
        Sql::query($sql);
        include Blox::info('cms','dir')."/includes/button-cancel.php";
    }

    $template->assign('scriptResult', $scriptResult); # need?
    $template->assign('reportsHtm', Report::get());
    include Blox::info('cms','dir')."/includes/display.php";

    ############# End  #############








    function updatelastMailedRecs()
    {
        # TODO. take not the last record but the last retrieved record  (see above Request::add([$subscription['block-id']=>['limit'=>999]]);)
        $sql = "SELECT * FROM ".Blox::info('db','prefix')."subscriptions WHERE `activated`=1";
        if ($result = Sql::query($sql)) {
            # For each subscription
            Request::add('block='.$row['block-id'].'&backward&limit=1');
            # subscriptionId `block-id` title last-mailed-rec
            while ($row = $result->fetch_assoc()) {
                # Take the last record from each sibscribed block
                $blockInfo = Blox::getBlockInfo($row['block-id']);
                $tab = Request::getTable(Blox::getTbl($blockInfo['tpl']), $row['block-id'], '', "AND `block-id`=".$row['block-id']);
                Sql::query('UPDATE '.Blox::info('db','prefix').'subscriptions SET `last-mailed-rec`=? WHERE `block-id`=?', [$tab[0]['rec'], $row['block-id']]);
            }
            $result->free();
        }
    }




    /**
     * Build the body and header of the letter 
     * Link css and js files in the body but not in the <header>
     * Links (href, src) should be absolute, i.e. http://...
     * Images are automatically downloaded according to the links and embedded in the body.
     *
     * @return array ['body'=>.., 'header'=>..];
     */
    function makeEmail($email_from='', $name_from='',  $html='')
    {
        if (empty($email_from))
            $email_from = ' ';

        if (empty($name_from))
            $name_from = $email_from;

        $imageFiles = getImageFiles($html);

        if (mb_strtolower(mb_substr(PHP_OS,0,3))=='win')
            $eol="\r\n";
        elseif (mb_strtolower(mb_substr(PHP_OS,0,3))=='mac')
            $eol="\r";
        else
            $eol="\n";


        if (mb_strlen(Text::stripTags($html))==mb_strlen($html))
           $html = str_replace($eol, '<br />',$html);
        $html = preg_replace("#(?<!\r)\n#siu", "\r\n", $html);
        $uId = mb_strtolower(uniqid(time()));
        $charset = 'utf-8';

        $eheader = '';
        $eheader .= 'X-Priority: 3'.$eol;
        $eheader .= 'X-MSMail-Priority: Normal'.$eol;
        $eheader .= 'X-Mailer: PHP v'.phpversion().$eol;
        $eheader .= 'From: '.headerEncode($name_from, $charset).' <'.$email_from.'>'.$eol;
        $eheader .= 'Return-path: <'.$email_from.'>'.$eol;
        $eheader .= 'Reply-To: '.headerEncode($name_from, $charset).' <'.$email_from.'>'.$eol;
        $eheader .= 'Mime-Version: 1.0'.$eol;
        $eheader .= 'Content-Type: multipart/related'.$eol; # If you use multipart/mixed - then all files wiil be shown  in the bottom of the letter as attached (attachment)
        $eheader .= 'boundary=----------'.$uId.$eol.$eol;
        
        $ebody  = '------------'.$uId.$eol;
        $ebody .= 'Content-Type:text/html; charset='.$charset.$eol;
        $ebody .= 'Content-Transfer-Encoding: 8bit'.$eol.$eol.$html.$eol.$eol;

        if ($imageFiles)
        {
            if (is_array($imageFiles)) {
                foreach($imageFiles as $k=>$v)
                    $files[$k] = $v;
            } else { # if a single file is attached
                $files[] = $imageFiles;
            }
            
            $imageMimeTypes = [
                'jpg'=>'image/jpeg',
                'jpeg'=>'image/jpeg',
                'gif'=>'image/gif',
                'png'=>'image/png'
            ];


            foreach($files as $k=>$v)
            {
                $mime = 'application/octet-stream';
                $content = null;
                if (is_array($v)) {
                    if ($v['src'] && $v['content']) {
                        $ex = array_reverse(explode('.', $v['src']));
                        if ($ex['0'] && $ex['1'] && $imageMimeTypes[ $ex['0'] ]) {
                            $mime = $imageMimeTypes[ $ex['0'] ];
                            $file_name = $ex['1'].'.'.$ex['0'];
                            $content = $v['content']; # Get the content of a file to be attached
                        }
                    }
                } else {
                    $lines = explode($eol, $v);
                    if (count($lines)>1) {
                        $file_name = $k;
                        $content = $v;
                    } else {
                        $ex_name = array_reverse(explode('/', $v));
                        $file_name = Text::stripTags($ex_name['0'],'strip-quotes');
                        $content = file_get_contents($v); # Get the content of a file to be attached
                    }
                }

                if ($content) {
                    # Build the header
                    $ebody .= "------------".$uId."\n";
                    $ebody .= "Content-Type: ".$mime."; ";
                    $ebody .= "name=\"".basename($file_name)."\"\n";
                    if ($v['src']) {
                        $ebody .= "Content-Location: ".$v['src']."\n";
                        $ebody .= "Content-Transfer-Encoding:base64\n\n";
                    } else {
                        $ebody .= "Content-Transfer-Encoding:base64\n";
                        $ebody .= "Content-Disposition:attachment;";
                        $ebody .= "filename=\"".basename($file_name)."\"\n\n";
                    }
                    $ebody .= chunk_split(base64_encode($content))."\n";
                }
            }
        }
        return ['body'=>$ebody, 'header'=>$eheader];
    }







    function getImageFiles($html='')
    {
        # Find image tags. Returns an array ['html'=>..., 'files'=>...]
        if ($html) {
            if (ini_get('magic_quotes_gpc')=='1')
               $html = str_replace("\'", "'", str_replace('\"', '"', $html));
            $imgUrls = getImgUrls($html);
            if ($imgUrls) {
                foreach($imgUrls as $src) {
                    if ($src) {
                        $content = file_get_contents($src);
                        if ($content)
                            $files[] = ['src'=>$src, 'content'=>$content];
                    }
                }
                if ($files)
                   return $files;
            }
        }
    }


    /**
     * @return array 
     */
    function getImgUrls($html='')
    {
        if ($html) {
            if (ini_get('magic_quotes_gpc')=='1')
                $html = str_replace("\'", "'", str_replace('\"', '"', $html)); 

            if ($escape_script = preg_replace('/<script(.+)<\/script>/sUi', '', $html))
                $html = $escape_script;

            # <img>
            $patterns[] = "/<(img|input).+?src=('|\"|)(http(.+?))(\s|>)/siu";
            # url()
            $patterns[] = "/(url)\s*?\(\s*?('|\"|)(http(.+?))(\s|\))/siu";
            foreach ($patterns as $pattern) {
                $matches = [];
                if (preg_match_all($pattern, $html, $matches)) {
                    # The value of the src attribute and string before space or the end of the img tag
                    if ($matches[3]) {
                        foreach($matches[3] as $k=>$v)
                        {
                            if ($matches[2][$k]) # if there were quotation marks
                                $delim = $matches[2][$k];
                            else # If there were attributes too
                                $delim = ' ';
                            $aa = explode($delim, $v);
                            $v = $aa[0];
                            $imgUrls[] = $v;
                        }
                    }
                }
            }
            array_unique($imgUrls);
            return $imgUrls;
        }
    }




    function headerEncode($str, $charset)
    {
        return '=?'.$charset.'?B?'.base64_encode($str).'?=';
    }

