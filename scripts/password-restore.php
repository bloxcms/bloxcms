<?php

if (isset($_GET['login'])) {
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
} else {
    # Find list of user-id s, that satisfies the query
    $inputs = ['login','email'];
    foreach ($inputs as $input) {   
        if ($_POST[$input]) {
            $sql = "SELECT * FROM ".Blox::info('db','prefix')."users WHERE $input=?";
    	    if ($result = Sql::query($sql, [$_POST[$input]])) {
                while ($row = $result->fetch_assoc()) { # because the email of different users may be the same
                    if (!in_array($row['id'], $userIds)) {
                        $userIds[] = $row['id'];
                        $usersInfo[] = $row;
                    }
                }
                $result->free();
            }
        }
    }

    if ($usersInfo)
    {
        # Use Editors's Email As FromEmail
        $adminInfo = Acl::getUsers(['user-is-admin'=>true])[0];
        $fromUserInfo = Acl::getUsers(['user-is-editor'=>true])[0];
        if (empty($fromUserInfo['email']))
            $fromUserInfo = $adminInfo;


        if ($from2 = Blox::info('site','emails','from'))
            ;
        else
            $from2 = $fromUserInfo['email'];
            
        foreach ($usersInfo as $userInfo)
        {   
            $url = Blox::info('site','url').'/';
            if ($_REQUEST['password-update-href']) { # Use a custom template
                $h = Url::decode($_REQUEST['password-update-href']);
                $url.= $h.(mb_strpos($h, '?')===false ? '?' : '&');
            } else
                $url.='?password-update&';
            $z = $_SESSION['Blox']['password-update-codes'][$userInfo['id']] = Str::genRandomString(8);
            $url.= 'code='.Url::encode(serialize(['id'=>$userInfo['id'], 'login'=>$userInfo['login'], 'u-code'=>$z]));
            $mdat = sprintf($terms['you-requested'], '<b>'.Blox::info('site','url').'</b>', '<a href="'.$url.'"><b>'.$url.'</b></a>');
            $mdat.= '<br>'.$terms['request-from-ip'].$_SERVER['REMOTE_ADDR'].'.';
                  	
            # user-is-activated.
            $props = Proposition::get('user-is-admin', $userInfo['id']);
            if (empty($props)) {# Not admin
                $props = Proposition::get('user-is-activated', $userInfo['id']);
                if (empty($props)) # Not activated           
                    $mdat .= '<br>'.$terms['error-message4'];
            }
                
            if ($userInfo['email']) {
            	$data = [
            		'from'=> $from2,
            		'to'=> $userInfo['email'],					
            		'subject'=> $terms['bar-title'],
            		'htm'=> $mdat,
            	];
            	if (Email::send($data))
                    $messageIsSent = true;
                else
                    $messageIsNotSent = $terms['error-message1'];
        	} else
                $messageIsNotSent = $terms['error-message6'];
        }
        
        if ($messageIsSent)
            $template->assign('acceptMessage', $terms['accept-message']); 
        
        if ($messageIsNotSent)  {
            $template->assign('errorMessage', $messageIsNotSent); # TODO: Send email to admin about the error 
            # From-email for the letter to admin 
            $fromEmail = Blox::info('site','emails','from');
            $mdat2 = $terms['error-message5'].' '.dirname(Url::punyDecode($_SERVER['HTTP_HOST']).$_SERVER['REQUEST_URI']."x").'. '.$messageIsNotSent; # "x" is fakefole
            if (empty($fromUserInfo['email'])) 
                $mdat2 .= ' '.$terms['error-message3'];
            Email::send($fromEmail, $adminInfo['email'], $terms['bar-title'], $mdat2);
        }
    }
    else # No such user
    {
        $errorMessage = sprintf($terms['error-message2'], "<b>$path</b>");                    
        $template->assign('errorMessage', $errorMessage); # TODO: Send email to admin about the error 
    }        
        
    include Blox::info('cms','dir')."/includes/button-cancel.php";
}

include Blox::info('cms','dir')."/includes/display.php";     
