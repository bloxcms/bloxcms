<?php
/** 
 *
 *  @todo The letter "ы" in $toEmails cause an error - does not send letters
 *  @todo 
 *      http://www.phpclasses.org/blog/package/13/post/2-Did-You-Mean-Advanced-Email-Validation-in-PHP.html
 *      github.com/PHPMailer
 */

class Email
{
    //private static $options = [];
    
    
    /** 
     * @param array $data {
     *     @var mixed $to Emails of recipients
     *     @var mixed $from Emails of senders. Format is the same as for $to
     *     @var string $subject
     *     @var string $htm The text with html-tags (content of <body> element i.e. without doctype and head sections)
     *     @var string $txt Alternative plain text. You can leave $hyperText empty.
     *     @var bool $together Send an email to all recipients at once
     *     @var array $attachments Array of paths to files to be attached 
     *     @var string $transport ssl|tls|starttls
     * }
     * @param array $errors
     * @return bool Returns true if the letter was delivered to at least one recipient
     *     
     * @examples
     *      $data['to'] = 
     *           RFC 2822 string
     *               'john@doe.com';
     *               'John Doe <john@doe.com>';
     *               'john@doe.com, Bill Smith <bill@smith.com>';
     *           Swift Mailer's array
     *               ['john@doe.com', ...];
     *               ['john@doe.com'=>'John Doe', ...];
     *               ['john@doe.com', 'bill@smith.com'=>'Bill Smith'];
     *
     *      $data['attachments'] = [
     *          'templates/aaa.xlsx',
     *          Blox::info('templates','dir').'/aaa.xlsx',
     *      ];
     */
    public static function send($data=[], &$errors=null) //$from, $to, $subject=null, $hyperText=null, $plainText=null, $together=null, &$errors=null, $attachFiles=null
    {   
        try {
            # Defaults
            $data += ['from'=>'', 'to'=>'', 'subject'=>'', 'htm'=>'', 'txt'=>'', 'together'=>false, 'attachments'=>[], 'transport'=>[]];
            
            if (empty($data['to'])) {
                $errors['no-recipients'] = true;
                return false;
            } else {
                $data['to'] = self::format($data['to'], $invalids);
                $errors['invalid-emails']['to'] = $invalids;
            }

            if (empty($data['from']))
                Blox::prompt(sprintf(Blox::getTerms('no-from-email'), '<a href="?site-settings&pagehref='.Blox::getPageHref(true).'">', '</a>'), true);
            else {
                $data['from'] = self::format($data['from'], $invalids);
                $errors['invalid-emails']['from'] = $invalids;
            }
            
            require_once Blox::info('cms','dir').'/vendor/swiftmailer/swiftmailer/lib/swift_required.php';
            $transport = self::getTransport($data['transport']);
            $mailer = Swift_Mailer::newInstance($transport);

            # From
            if (is_array($data['from'])) {
                $aa = each($data['from']);
                if (is_numeric($aa['key']))
                    $from_address = $aa['value'];
                else
                    $from_address = $aa['key'];
            } else
                $from_address = $from;
                
            $message = Swift_Message::newInstance()->setSubject($data['subject']);
            if ($data['from'])
                $message->setFrom($data['from']);
            #
            if ($from_address) 
                $message->setReturnPath($from_address);
            #
            if ($data['reply-to'])
                $message->setReplyTo($data['reply-to']);
            elseif ($from_address) 
                $message->setReplyTo($from_address);
            #
            if (empty($data['htm'])){
                if (empty($data['txt'])){
                    $errors['no-message'] = true;
                    return false;}
            } else {
                $doc = self::createHtmlDoc($data['htm']);
                if (empty($data['txt']))
                    $data['txt'] = Text::stripTags($data['htm']);
            }

            if (empty($doc))
                $message->setBody($data['txt'], 'text/plain');
            else {
                $message->setBody($doc, 'text/html');
                $message->addPart($data['txt'], 'text/plain'); # If email client has not HTML mode
            }

            # Attach Files
            if ($data['attachments']) {
                foreach($data['attachments'] as $fl) {
                    $f = [];
                    unset($attachment);
                    if (is_array($fl))
                        $f = $fl;
                    else
                        $f['path'] = $fl;
                    #
                    if ($f['data']) { # Dynamic data
                        $attachment = new Swift_Attachment($f['data'], $f['name'], $f['type']);  // 'type'=>'application/pdf'
                        //$message->attach($attachment);
                    } elseif ($f['path']) { # File on disk
                        if (file_exists($f['path'])) {
                            $attachment = Swift_Attachment::fromPath($f['path']);
                            if ($f['name'])
                                $attachment->setFilename($f['name']);
                            //$message->attach($attachment);
                        } else
                            Blox::prompt(sprintf(Blox::getTerms('no-attach-file'), $f['path']), true);
                    }
                    if ($attachment)
                        $message->attach($attachment);
                }
            }

            $failedRecipients = [];
            $countRecipients = 0;
            $countSent = 0;
            if ($data['together']) {
                $countRecipients++;
                $message->setTo($data['to']);
                $countSent += $mailer->send($message, $failedRecipients);
            } else { # separate message for each recipient
                foreach ($data['to'] as $address => $name) {
                    $countRecipients++;
                    if (is_int($address))
                        $message->setTo($name);
                    else
                        $message->setTo([$address=>$name]);
                    $countSent += $mailer->send($message, $failedRecipients);
                }
            }

            if ($failedRecipients)
                $errors['failed-recipients'] = $failedRecipients;
            if ($countRecipients > $countSent) {
                $errors['count-recipients'] = $countRecipients;
                $errors['count-sent'] = $countSent;
            }
           
            if ($countSent)
                return true;
            else
                return false;
        } catch (Swift_TransportException $e) {
            //$errors['other'] = $e->getMessage();
        	Blox::prompt($e->getMessage(),  true);
            Blox::error($e->getMessage());
            return false;
        }
    }



    /*
    detects transport automaticaly
    $options = ['type'=>'sendmail'];
    $options = ['type'=>'sendmail', 'path'=>'/usr/sbin/sendmail -bs'];
    $options = ['type'=>'smtp', 'host'=>'','port'=>'','user'=>'','password'=>''];
    */
    private static function getTransport($options)
    {
        
        foreach (['type', 'encryption'] as $k) {
            if ($options[$k])
                $options[$k] = strtolower($options[$k]);
        }
        require_once Blox::info('cms','dir').'/vendor/swiftmailer/swiftmailer/lib/swift_required.php';
        # sendmail
        if ($options['type'] == 'sendmail') {
            if (!$options['path'])
            {
                $path = ini_get('sendmail_path'); # '/usr/sbin/sendmail -bs';
                if (empty($path))
                    $transport = Swift_MailTransport::newInstance();
                else
                    $transport = Swift_SendmailTransport::newInstance($path);
            }
            else
                $transport = Swift_SendmailTransport::newInstance($options['path']);
        } 
        # smtp
        elseif ($options['type'] == 'smtp') {
            $encryption = ($options['encryption']=='starttls') ? 'tls' : $options['encryption'];
            $transport = new Swift_SmtpTransport($options['host'], $options['port'], $encryption);    
            if ($options['encryption']=='starttls') 
                $transport->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false]]);
            if (isset($options['user']))
                $transport->setUsername($options['user']);
            if (isset($options['password']))
                $transport->setPassword($options['password']);
            //$transport->start();
        } 
        # mail()
        else {
            $transport = Swift_MailTransport::newInstance();
        }
        return $transport;
    }
    
    





    private static function createHtmlDoc($hyperText)
    {
        $doc = '<!DOCTYPE html><html><head>';
        $doc.= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
        $doc.= $hyperText;
        $doc.= '</body></html>';
        return $doc;
    }

    
    
    /**
     * @param mixed $emails 
     * @param array $invalids Invalid emails
     * @return array Emails in format:
            ['bill@smith.com'] => 'Bill Smith',
            ['john@doe.com'] => '',
     */
    public static function format($emails, &$invalids=null)
    {
        $arr = [];
        $invalids = [];
        if (is_array($emails)) {
            foreach ($emails as $k=>$v) {
                if (is_string($k)) {
                    $addr = $k;
                    $name = $v;
                } elseif (is_int($k)) {
                    $addr = $v;
                    $name = '';
                }
                if (Str::isValid($addr, 'email'))
                    $arr[$addr] = $name;
                else
                    $invalids[] = $addr;
                    
            }
        } else { # string
            if ($z = explode(',', $emails)) {
                foreach ($z as $email) {
                    $name = '';
                    if ($parts = Str::splitByMark($email, '<')) { # With name
                        if ($parts[1]) { # with name
                            $addr = trim(Str::getStringBeforeMark($parts[1], '>'));
                            $name = trim($parts[0]);
                        } else
                            $addr = trim($parts[0]);
                    } else
                        $addr = trim($email);
                    if (Str::isValid($addr, 'email'))
                        $arr[$addr] = $name;
                    else
                        $invalids[] = $addr;
                }
            }
        }
        return $arr;
    }

}