<?php

    if (empty($_POST['subject']) || empty($_POST['message'])) {
        $pagehref = Blox::getPageHref();
        Url::redirect($pagehref,'exit');
    }

    $from = Blox::info('site','emails','from') 
        ?: $from = Acl::getUsers(['user-is-admin'=>true])[0]['email']; # Message to the first admin
    $sentCounter = 0;        
    $notSentCounter = 0;
    # All users
    $sql = "SELECT * FROM ".Blox::info('db','prefix')."users ORDER";
    if ($result = Sql::query($sql)) {
        # Check users permissions
        while ($row = $result->fetch_assoc()) {    
            if (Proposition::get('user-is-activated', $row['id'])) {
                $mdat['message'] = $_POST['message'];
            	$data = [
            		'from'=> $from,
            		'to'=> $row['email'],					
            		'subject'=> $_POST['subject'],
            		'htm'=> $mdat,
            	];
                if (Email::send($data))
                    $sentCounter++;
                else
                    $notSentCounter++;
            }
        }
        $result->free();
    }
    
    if (empty($sentCounter) && $notSentCounter)
        Blox::prompt($terms['no-user'],  true);
    else {               
        if ($sentCounter)
            Blox::prompt("{$terms['sent']} <b>$sentCounter</b>");
        if ($notSentCounter)
            Blox::prompt("{$terms['not-sent']} <b>$notSentCounter</b><span>. ",  true);
    }