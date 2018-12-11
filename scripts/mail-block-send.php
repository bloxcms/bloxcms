<?php

    /**
     * Same as mail-subscriptions-send.php
     * @todo Redo everything, as it became too complicated (Ajax, )
     */

    /* $scriptResult codes:
     * 0: Nothing done?
     * 1: Mailing is not ended (there are a list of recipients and a letter)
     * 3: csvFileNotCreated
     * 4: zippedCsvFileIsCreated - download the list of recipients
     * 5: zippedCsvFileNotCreated
     * 6: no-message Error! No letters to send. The letter is re-created
     * 7:
     */

    if ( !(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) ) 
        Blox::execute('?error-document&code=403');
    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    $regularId = Sql::sanitizeInteger($_GET['block']);
    
    if ('get-recipients'==$_GET['phase']) {
        if (isset($_POST)){
            # The email is already checked in the form - here the insurance if JS does not work
            # Although this is not necessary, as phases will not work without JS 
            if (!Str::isValid($_POST['blockletter-params']['email-from'], 'email'))
                unset($_POST['blockletter-params']['email-from']);
            # Save settings of letters
            Store::set('blockletter-params'.$_POST['blockletter-params']['block-id'], $_POST['blockletter-params']);
            $blockletterParams = $_POST['blockletter-params'];}
    } else {
        $blockletterParams = Store::get('blockletter-params'.$regularId);
        if ('send-letter'==$_GET['phase'] && $_POST['send-again']) {
            $blockletterParams['send-again'] = $_POST['send-again'];
            Store::set('blockletter-params'.$regularId, $blockletterParams);
        }
    }

    $blockId = (int)$blockletterParams['block-id'];

    ############# Phase 1. Make a list of recipients (table blockrecipients) #############
    if ('get-recipients'==$_GET['phase'])
    {
        if (isset($_POST))
        {
            Report::reset();

            ################################ Create an array of recipients $recipientsData ################################
            if ($_POST['sending-option'] == 'test'){
                Store::set('testUserParams', Blox::info('user'));
                $userInfoKeys = ['login','personalname','familyname'];
                foreach ($userInfoKeys as $k)
                    $aa[$k] = Blox::info('user',$k);
                $recipientsData[Blox::info('user','email')] = $aa;
                $scriptResult = 0;
            } else {
                Store::delete('testUserParams');
                # Create folder for uploaded files
                $uploadedFolder = 'mail-block-send-uploaded';
                Files::makeTempFolder($uploadedFolder); # TODO: $uploadTempDir = Files::makeTempFolder('mail-block-send-uploaded');
                if (isset($_FILES))
                    $uploadedFiles = Upload::uploadFiles(Upload::format($_FILES), Blox::info('site','dir')."/temp/$uploadedFolder", true);# The input aray $_FILES

                foreach ($_POST['recipients'] as $status=>$value) {
                    if ($value=='inc')
                        $incStatuses[] = $status;
                    elseif ($value=='exc')
                        $excStatuses[] = $status;
                }

                if ($incStatuses)
                    $recipientsData = getRecipientsData($incStatuses, $uploadedFiles, $uploadedFolder);

                if ($excStatuses){
                    if ($excRecipientsData = getRecipientsData($excStatuses, $uploadedFiles, $uploadedFolder))
                        foreach ($excRecipientsData as $email => $aa)
                            unset($recipientsData[$email]);
                }

                # Do not send, but create a file with a list of emails of recipients
                if ($_POST['sending-option'] == 'create-file') {
                    $usersCounter = 0;
                    $emailsList = '';
                    foreach ($recipientsData as $email => $row){
                        if ($email){
                            $recipientsText .= "{$email};{$row['personalname']};{$row['familyname']}\n";
                            $usersCounter++;}}
                    # Create a folder for a recipient list
                    $downloadFolder = 'mail-block-send-download';
                    Files::makeTempFolder($downloadFolder);
                    $csvFile = Blox::info('site','dir').'/temp/'.$downloadFolder.'/recipients.csv';
                    if (file_put_contents($csvFile, $recipientsText)){
                        # Archived version
                        $zippedCsvFile = Blox::info('site','dir').'/temp/'.$downloadFolder.'/recipients.zip';
                        $zip = new ZipArchive;
                        $res = $zip->open($zippedCsvFile, ZipArchive::CREATE);
                        if ($res === TRUE) {//'zippedCsvFileIsCreated'
                            $zip->addFromString('recipients.csv', $recipientsText);
                            $zip->close();
                            $scriptResult = 4;
                        } else//'zippedCsvFileNotCreated'
                            $scriptResult = 5;}
                    else//'csvFileNotCreated'
                        $scriptResult = 3;
                    }
                else
                    $scriptResult = 0;
            }

            ################################ Write an array $recipientsData to DB (to resume in case of failure) ################################
            if ($_POST['sending-option'] != 'create-file'){
                 # Not if (!empty())
                if (Sql::tableExists(Blox::info('db','prefix').'blockrecipients')) {
                    $sql = 'DELETE FROM '.Blox::info('db','prefix').'blockrecipients WHERE `block-id`=?';
                    Sql::query($sql, [$blockId]);
                } else {
                    $sql = "
                    CREATE TABLE IF NOT EXISTS ".Blox::info('db','prefix')."blockrecipients (
                        email VARCHAR(99),
                        login VARCHAR(24),
                        personalname VARCHAR(99) NOT NULL default '',
                        familyname VARCHAR(99) NOT NULL default '',
                        unsent tinyint(1) unsigned NOT NULL default 0,
                        `block-id` ".Admin::reduceToSqlType('block').",
                        PRIMARY KEY (email,`block-id`),
                        INDEX (unsent)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
                    Sql::query($sql);
                }
                $usersCounter = 0;
                foreach ($recipientsData as $email => $data) {
                    if ($email) {
                        $sql = 'INSERT '.Blox::info('db','prefix').'blockrecipients SET email=?, login=?, personalname=?, familyname=?, `block-id`=?';
                        Sql::query($sql, [$email, $data['login'], $data['personalname'], $data['familyname'], $blockId]);
                        $usersCounter++;}
                }
            }
            $aa = sprintf($terms['get-recipients'], "<b>$usersCounter</b>");
            Report::add($aa);}
        else
            Report::add($terms['get-recipients'], true); ##$scriptResult = 1;
    }

    ############# Phase 2. Create an email for mailing  #############
    elseif ('create-letter'==$_GET['phase'])
    {
        Blox::addInfo(['user' => ['user-as-visitor' => true]]);# To not show the edit buttons
        $blockHtm = Blox::getBlockHtm($blockId);
        $pageHtm = Email::createHtmlDoc($blockHtm);
        ##################### Temporary script ##################################
        # TODO: Replace templates by Blox::info('templates', 'dir') in $patterns and $substitutions 
        $defaultTemplatesDir = Blox::info('site','dir')."/templates";
        if (Blox::info('templates', 'dir') != $defaultTemplatesDir)
            Blox::prompt($terms['path-to-templates'], true);
        #######################################################
        # Replace relative links by absolute
        # TODO Use Url::convertToAbsolute() for https
        $patterns = [
            "/((href|src)\s*?=\s*?('|\"))(?!http:)(\.\/)?/usi", # href|src  // ('|\")? and ('|\"|) do not work - use quotation marks
            "/url\s*?\(\s*?('|\"|)(?!http:)(\.\/)?(templates\/)(.*?)('|\"|)\s*?\)/usi",  # url(templates/..) in tpl
            "/url\s*?\(\s*?('|\"|)(?!http:)(\.\/)?(?!templates\/)(.*?)('|\"|)\s*?\)/usi" # url(..)   in css
        ];
        $substitutions = [
            "$1".Blox::info('site','url'),
            "url($1".Blox::info('templates', 'url')."/$4$5)",
            "url($1".Blox::info('templates', 'url')."/$3$4)"
        ];
        $pageHtm= Blox::encodeToUtf8($pageHtm);
        $pageHtm = preg_replace($patterns, $substitutions, $pageHtm);
        ########################### Replace the image's url ##########################
        require_once Blox::info('cms','dir').'/vendor/SwiftMailer/swift_required.php';
        $GLOBALS['Blox']['temp-message'] = Swift_Message::newInstance();
        $pageHtm =  preg_replace_callback(
            "~(src=)('|\"|)(.*?)('|\"|\s+|>)~usi",
            create_function(
                '$matches',
                '$cid = $GLOBALS["Blox"]["temp-message"]->embed(Swift_EmbeddedFile::fromPath($matches[3])); return $matches[1].$matches[2].$cid.$matches[4];' # temp-message NOT USED
            ),
            $pageHtm
        );
        $message = $GLOBALS['Blox']['temp-message'];
        $message->setBody($pageHtm, 'text/html');
        $message->setSubject($blockletterParams['subject']) ;
        if ($blockletterParams['email-from'])
            $message->setFrom([$blockletterParams['email-from'] => $blockletterParams['name_from']]);
        $blockletterDir = Files::makeTempFolder('blockletter'.$blockId);        
        file_put_contents($blockletterDir.'/message.txt', serialize($message));
        Report::add($terms['letter-is-ready']);
    }
    ############# Phase 3. Mailing #############
    elseif ('send-letter'==$_GET['phase'])
    {
        require_once Blox::info('cms','dir').'/vendor/SwiftMailer/swift_required.php';
        $blockletterDir = Files::getTempFolderDir('blockletter'.$blockId);
        if ($aa = file_get_contents($blockletterDir.'/message.txt')) 
        { # There is a letter for mailing
            $message = unserialize($aa);
            $transport = Email::getTransport();
            $mailer = Swift_Mailer::newInstance($transport);
            # From this user
            if (empty($blockletterParams['email-from'])){
                $blockletterParams['email-from'] = Blox::info('user','email');
                if (empty($blockletterParams['name_from']))
                    $blockletterParams['name_from'] = Blox::info('user','login');
            }

            # Create the list of recipients of this letter
            $unsent = false;
            $where = 'WHERE `block-id`='.$blockId;
            if (isset($blockletterParams['send-again'])){
                $sendAgain = $blockletterParams['send-again'];
                if ($sendAgain['untouched'] && $sendAgain['failed'])
                    ;
                elseif (!$sendAgain['untouched'] && !$sendAgain['failed']){
                    Report::add($terms['select-option'], true);
                    Url::redirect(Blox::info('site','url').'/?mail-block-send&block='.$regularId.'&phase=report'.$pagehrefQuery,'exit');
                } elseif ($sendAgain['untouched'])
                    $where .= " AND unsent=0";
                elseif ($sendAgain['failed'])
                    $where .= " AND unsent=1";}
            else
                $where .= " AND unsent=0";
            $aa = Sql::select("SELECT COUNT(email) FROM ".Blox::info('db','prefix')."blockrecipients ".$where);
            # There are more recipients
            if ($remainder = $aa[0]['COUNT(email)']) {
                # There is the number of messages in one sending
                if ($numberOfLetters = $blockletterParams['number-of-letters']) {
                    if ($blockletterParams['randomize'])
                        $numberOfLetters = rand(round($numberOfLetters * 0.7), round($numberOfLetters * 1.4));
                    $limit = 'LIMIT '.$numberOfLetters;
                }
                $sql = "SELECT * FROM ".Blox::info('db','prefix')."blockrecipients ".$where." ".$limit;
                $counter = 0;
                if ($result = Sql::query($sql)) {                    
                    if ($blockletterParams['mail-server-host']){
                        $messageId = $message->getHeaders()->get('Message-ID');                        
                        $messageId->setId(Str::genRandomString((10).'.'.Str::genRandomString(13).'@'.$blockletterParams['mail-server-host']));
                    }
                    $firstEmail = '';
                    # For each recipient
                    while ($row = $result->fetch_assoc()){
                        if (empty($firstEmail))
                            $firstEmail = $row['email'];
                        if (empty($row['login']))
                            $row['login'] = $row['email'];
                        $message->setTo([$row['email'] => $row['login']]);

                        if ($mailer->send($message)){
                            # Remove a user from the recipient list
                            $sql = 'DELETE FROM '.Blox::info('db','prefix').'blockrecipients WHERE email=? AND `block-id`=?';
                            Sql::query($sql, [$row['email'], $blockId]);
                        } else {
                            # mark as unsent
                            $sql = 'UPDATE '.Blox::info('db','prefix').'blockrecipients SET unsent=1 WHERE email=? AND `block-id`=?';
                            Sql::query($sql, [$row['email'], $blockId]);
                            $unsent = true;
                        }
                        $counter++;
                    }
                    $result->free();
                    if ($counter){
                        
                        $aa = $remainder-$counter;
                        if ($aa>0){
                            $bb = '. '.$terms['counter-reports'][0].' '.$aa; # Mailing is not over
                            $scriptResult = 1;}
                        else
                            $scriptResult = 0;

                        if ($unsent){
                            if ($counter == 1)
                                $notSent = '. '.$terms['counter-reports'][1];
                            elseif ($counter > 1)
                                $notSent = '. '.$terms['counter-reports'][2];
                        }
                        $cc = ' ('.$firstEmail;
                        if ($counter > 1)
                            $cc .= ', ...';
                        $cc .= ')';
                        Report::add(date("H:i:s").'. '.$terms['counter-reports'][3].' '.$counter.' '.$cc.$bb.$notSent);
                    }
                    else {
                        Report::add($terms['blockletter-is-sent']);
                        $scriptResult = 0;
                    }
                }
            }
            else {
                Report::add($terms['blockletter-is-sent']);
                $scriptResult = 0;
            }
        } else {
            Report::add($terms['no-message']);
            $scriptResult = 6;
            Url::redirect(Blox::info('site','url').'/?mail-block-send&block='.$regularId.'&phase=create-letter'.$pagehrefQuery,'exit');
        }
    }


    ############# Phase 4. Report #############
    elseif ('report'==$_GET['phase'])
    {
        # Yet wasn't sent
        $sql = "SELECT email, unsent FROM ".Blox::info('db','prefix')."blockrecipients WHERE unsent=0 AND `block-id`=?";
        if ($result = Sql::query($sql, [$blockId])) {
            $numOfunsent['untouched'] = $result->num_rows;
            $result->free();
        }

        # Failed to send
        $sql = "SELECT email, unsent FROM ".Blox::info('db','prefix')."blockrecipients WHERE unsent=1 AND `block-id`=?";
        if ($result = Sql::query($sql, [$blockId])) {
            $numOfunsent['failed'] = $result->num_rows;
            $result->free();
        }

        # If successful newsletter
        if (empty($numOfunsent['failed']) && empty($numOfunsent['untouched']))
            ;
        else {
            $template->assign('numOfunsent', $numOfunsent);
            $showSubmitButtons = true;
        }
    }
    elseif ('delete-letter'==$_GET['phase']){
        $sql = "DELETE FROM ".Blox::info('db','prefix')."blockrecipients WHERE `block-id`=?";
        Sql::query($sql, [$blockId]);
        # Do not delete the letter itself - it will cleaned
    }

    if ($showSubmitButtons)
        include Blox::info('cms','dir')."/includes/buttons-submit.php";
    else
        include Blox::info('cms','dir')."/includes/button-cancel.php";

    $template->assign('scriptResult', $scriptResult);
    $template->assign('reportsHtm', Report::get());
    $template->assign('blockletterParams', $blockletterParams);
    include Blox::info('cms','dir')."/includes/display.php";

    ############# End  #############












    function getRecipientsData($statuses, $uploadedFiles, $uploadedFolder)
    {
        $roles1 = ['admins'=>'user-is-admin','activated'=>'user-is-activated','editors'=>'user-is-editor'];
        $roles2 = ['editors-of-block'=>'user-is-editor-of-block', 'guests'=>'user-sees-hidden-page', 'subscribers'=>'user-is-subscriber'];
        $recipientsData = [];

        # External lists launch first, since they have incomplete data (only email)
        if ($uploadedFiles){
            for ($i=1; $i <= 2 ;$i++){
                if (in_array("uploaded$i", $statuses)) {
                    /*
                     * Compact variant, but it is not working
                     *    str_getcsv() converts to array only a single row, that is, you have to cut the strings on breaks
                     *    $emails = str_getcsv(file_get_contents('...csv'));
                     *
                     * Simple variant (spaces), but not beauty. Accepts only texts in which addresses are separated by a single space. Works
                     *    $emails = explode(' ', file_get_contents(Blox::info('site','dir')."/temp/$uploadedFolder/".$uploadedFiles["uploaded$i"]));
                     *    foreach ($emails as $email)
                     *        if ($email = trim($email))
                     *            $recipientsData[$email] = ['personalname'=>, 'familyname'=>];
                     */
                    # Variant 3 (fgetcsv)
                    $fh = fopen(Blox::info('site','dir')."/temp/$uploadedFolder/".$uploadedFiles["uploaded$i"], 'r');
                    while (($row = fgetcsv($fh, 512, ';')) !== false){
                        $email = trim($row[0]);
                        $recipientsData[$email] = [];
                        if ($row[1]) $recipientsData[$email]['personalname'] = trim($row[1]);
                        if ($row[1]) $recipientsData[$email]['familyname']   = trim($row[2]);
                    }
                    fclose($fh);
                    $k = array_search("uploaded$i", $statuses);
                    unset($statuses[$k]);
                }
            }
        }


        foreach ($statuses as $status){
            if ($status=='registered'){
                $sql = "SELECT login, email, personalname, familyname FROM ".Blox::info('db','prefix')."users";
                if ($result = Sql::query($sql)){
                    while ($row = $result->fetch_assoc()) {
                        $email = $row['email'];
                        unset($row['email']);
                        # email is in the key of array to be unique
                        $recipientsData[$email] = $row;
                    }
                    $result->free();
                }
                
            }
            # Find ID of  refistered users with the role $incUsers
            else {
                if ($role = $roles1[$status])
                    $obj = null;
                elseif ($role = $roles2[$status])
                    $obj = 'all';
                if ($role){                    
                    $props = Proposition::get($role, 'all', $obj);
                    foreach ($props as $prop)
                        $roledUsers[$prop['subject-id']]='';
                }
            }
        }

        # Get personal data only to users with roles.
        if ($roledUsers){
            foreach ($roledUsers as $userId=>$aa){
                $sql = "SELECT login, email, personalname, familyname FROM ".Blox::info('db','prefix')."users WHERE id=?";
                if ($result = Sql::query($sql, [$userId])) {
                    if ($row = $result->fetch_assoc()) {
                        $email = $row['email'];
                        unset($row['email']);
                        $recipientsData[$email] = $row;
                    }
                    $result->free();
                }
            }
        }
        return $recipientsData;
    }


