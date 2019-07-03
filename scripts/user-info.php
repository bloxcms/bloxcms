<?php
/** 
 * Attention! 
 * When you use custom template for this script then add the parameter &user-activation-href=<?=Url::encode(...)?> to the action attribute of the form element in that custom template.
 * Write the home page relative URL of the activation page instead of dots. This parameter must be added only at the condition: if ($selectedUserId == 'new').
 *
 * @todo Remake this script via the class User:: like group-info.php that is made via class Group::    
 * @todo Replace $updateUserParams by Data::update()  
 */

$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);

if (isset($_GET['selected-user-id'])) {
    if ($selectedUserId = $_GET['selected-user-id'])
        $template->assign('selectedUserId', $selectedUserId); 
} else
    $selectedUserId = Blox::info('user','id');
    
$allowed = false;
# Access
if (Blox::info('user','user-is-admin'))
    $allowed = true;        	
elseif ($selectedUserId == Blox::info('user','id')) # Prohibit calling the script by nonadmin user with an ID that not match the requested ID
    $allowed = true;
elseif ($selectedUserId == 'new') { # This is not the admin who is trying register, as a regular user from the website but not from the menu.
    if (Store::get('allow-outer-registration'))
        $allowed = true;
    elseif (!isset($_GET['not-outer']))
        Url::redirect(Blox::info('site','url').'/?registration-denied'.$pagehrefQuery,'exit');
}

if (!$allowed) 
    Blox::execute('?error-document&code=403');
    
$fields = [
    'login'       => ['represent'=> $terms['login'], 'validation'=>'login not-empty'],
    'email'       => ['represent'=> $terms['email'], 'validation'=>'email not-empty'],
	'personalname'=> ['represent'=> $terms['personalname']],
	'familyname'  => ['represent'=> $terms['familyname']],
    'ip'          => ['represent'=> $terms['ip']],
    'regdate'     => ['represent'=> $terms['regdate']],
    'visitdate'   => ['represent'=> $terms['visitdate']],
	'notes'       => ['represent'=> $terms['request']],
];    
if ($selectedUserId == 'new') {
    $fields['password'] = ['represent'=> $terms['password'], 'validation'=>'password not-empty'];
    $fields['captcha'] = ['represent'=> $terms['captcha'], 'validation'=>'captcha'];
}
    
if (Blox::info('user','user-is-admin')) {
    $fields['notes']['represent'] = $terms['notes'];        
    unset($fields['notes']['validation']);
}        

# Check the data from form
if (isset($_POST['fields'])) 
{
    foreach ($_POST['fields'] as $name=>$value)
        $fields[$name]['value'] = $value;        
    $accepted = true; # TODO false
    foreach ($fields as $name => $field) {
        $validation = $field['validation'];
        # To be validated
        if ($validation) {
            if ($validation == 'captcha') {
                if (Captcha::exceeded('fields[captcha]')) {
                    $accepted = false;
                    $fields['captcha']['invalid-message'] = $terms['captcha-exceeded'];
                } elseif ($_POST['fields']) {
                    if (Captcha::check('fields[captcha]', $_POST['fields']['captcha']))
                        unset($fields[$name]['validation']);
                    else {
                        $accepted = false;
                        $fields['captcha']['invalid-message'] = $terms['captcha-incorrect'];
                    }
                } else {
                    $accepted = false;
                    $fields['captcha']['invalid-message'] = 'error';
                }
            } else {
                if (Str::isValid($field['value'], $validation, $invalidMessage))
                    unset($fields[$name]['validation']); // is valid. In the $fields[][validation] remain only not valid
                else {
                    $accepted = false;
                    $fields[$name]['invalid-message'] = $invalidMessage;
                }
            }
        }
    }

    $anotherUserExists = function($login, $selectedUserId) {
        $sqlValues = [];
        $sql = "SELECT login FROM ".Blox::info('db','prefix')."users WHERE login=?";
        $sqlValues[] = $login;
        if ($selectedUserId && 'new' != $selectedUserId) {
            $sql .= " AND id != ?";       
            $sqlValues[] = $selectedUserId;
        }         
        $sql .= " LIMIT 1";
        if ($result = Sql::query($sql, $sqlValues)) {
            if ($result->fetch_row()) {
                $result->free();
                return TRUE;
            }
        }
    };

    # i.e. by admin. Only admin can change the login 
    if (isset($selectedUserId) && $anotherUserExists($fields['login']['value'], $selectedUserId)) {
        $accepted = false;
        $fields['login']['invalid-message'] .=  $terms['invalid-message-1'];
    }               
    if ($accepted) {
        foreach ($fields as $name => $field)
            $userInfo[$name] = $field['value'];
        # @todo Replace by Data::update()  
        $updateUserParams = function($userInfo, $selectedUserId) {
            $sqlValues = [];
            $sql = 'UPDATE '.Blox::info('db','prefix').'users SET ';
            # i.e. by admin or selfreg user
            if (isset($selectedUserId)) {
                if ('new' == $selectedUserId) {                        
                    $userInfo['id'] = (function() {
                        $num = Sql::query("INSERT ".Blox::info('db','prefix')."users () VALUES ()");
                        if ($num > 0) {
                            $userId = Sql::getDb()->insert_id;
                            if (Blox::info('user','user-is-admin'))
                                Proposition::set('user-is-activated', $userId, null, true); # A new user created by admin, is initially activated.
                            # regdate
                            Sql::query("UPDATE ".Blox::info('db','prefix')."users SET regdate='".date('Y-m-d')."' WHERE id=?", [$userId]);
                            return $userId;
                        } else
                        	Blox::error('User-id was not generated');                            
                    })();
                    //$getNewUserId();
                } else
                    ;
                $sql .= 'login=?, notes=?, ';
                $sqlValues[] = $userInfo['login'];            
                $sqlValues[] = $userInfo['notes'] ?: '';
            }
            # by all users
            $sql .= 'email=?, personalname=?, familyname=?';
            $sqlValues[] = $userInfo['email'];
            $sqlValues[] = $userInfo['personalname'] ?: '';
            $sqlValues[] = $userInfo['familyname'] ?: '';
            if ('new' == $selectedUserId) {
                $sql .= ', password=?';
                $sqlValues[] = password_hash($userInfo['password'], PASSWORD_DEFAULT);
            }                
            $sql .= ' WHERE id=?';
            $sqlValues[] = $userInfo['id'];
            if ($userInfo['id'] && $userInfo['login']) // && $userInfo['password']
                return Sql::query($sql, $sqlValues);
            else {
                Blox::error('Error-1 in user-info.php');
                return false;
            }
        };
        if (false !== $updateUserParams($userInfo, $selectedUserId)) {   
            if (Blox::info('user','id')){   
                if (Blox::info('user','user-is-admin')) {
                    if ($_POST['button-ok'] == 'submit-and-return')
                        Url::redirect(Blox::info('site','url').'/?users'.$pagehrefQuery);
                    else
                        Url::redirect(Blox::getPageHref());
                } else
                    $acceptMessage = "<b>{$terms['editor-accept-message']}</b>";
            } else {
                # The registrant accepted and now will be sent an email to him with instructions for activation
                $arr = [$fields['login']['value'], $fields['password']['value']];                        
                $loginAndPassword = Url::encode(serialize($arr));
                $path = dirname($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."x"); # "x" is fakefile
                $subject = sprintf($terms['registered'], $path);
                $to = [$userInfo['email'] => $userInfo['login']];
                
                # "From" address
                if ($from = Blox::info('site','emails','from'))
                    ;
                else {
                    $activationAddress = 'activation';
                    $props = Proposition::get('user-is-editor', 'any');
                    $aa =  Acl::getUsers(['user-id'=>$props[0]['subject-id']])[0];	   
                    if ($aa['email']) { # The message to the first editor
                        $from = [$aa['email'] => $activationAddress];
                    } else {# The message to the first admin
                        $props = Proposition::get('user-is-admin', 'any');
                        $aa =  Acl::getUsers(['user-id'=>$props[0]['subject-id']])[0];	   
                        if ($aa['email'])
                            $from = [$aa['email'] => $activationAddress];
                        elseif (Blox::info('user','user-is-admin'))
                            Blox::prompt("Nor editor nor admin have emails",  true);
                    } 
                }
                

            	$body = sprintf($terms['user-activation'], '<b>'.$userInfo['login'].'</b>', '<b>'.$path.'</b>');
                # user-activation-href
                $url = Blox::info('site','url').'/';
                if ($_GET['user-activation-href']) { # Use a custom template
                    $h = Url::decode($_GET['user-activation-href']);
                    $url.= $h.(mb_strpos($h, '?')===false ? '?' : '&');
                } else
                    $url.='?user-activation&';
                $url.= 'code='.$loginAndPassword;
                #
            	$body .= ' <a href="'.$url.'">'.$url.'</a>'; # BUG 2017-09-06: The "$url" "192.168.1.98" is transfered to "192..168.1.98" in received email after Email::send(), only in html part of email body. Used: Open Server
                #
            	$data = [
            		'from'=> $from,
            		'to'=> $to,					
            		'subject'=> $subject,
            		'htm'=> $body,
            	];
                if (Email::send($data)) {
                    $acceptMessage = sprintf($terms['registrant-accept-message'], $fields['email']['value']);
            	} else {               
                    $errorMessage .= $terms['error-message3'];                            
    	            # The message to the first admin
                    $props = Proposition::get('user-is-admin', 'any');                            
                    $adminParams =  Acl::getUsers(['user-id'=>$props[0]['subject-id']])[0];	   
                    $errorMessage .= '<br />';
                    $errorMessage .= ($adminParams['email'])
                        ? '<a href="mailto:'.$adminParams['email'].'">'.$terms['let-admin-know'].'</a>'
                        : $terms['let-admin-know']
                    ;
                }
            }
        } else
            $errorMessage .= $terms['error-message2'];            
        # todo "if" ?
        $template->assign('acceptMessage', $acceptMessage); 
        $template->assign('errorMessage', $errorMessage); # @todo Send a message to the admin about this error
        include Blox::info('cms','dir')."/includes/button-cancel.php";
    } else { # not accepted
        $notAcceptMessage .= $terms['not-accept-message'];
        $template->assign('notAcceptMessage', $notAcceptMessage);  
    }
}
# Form is not submitted, that is the transition from the list
else {
    if ($selectedUserId){   
        if ('new' != $selectedUserId)
            $userInfo =  Acl::getUsers(['user-id'=>$selectedUserId])[0];
    } else {
        $userInfo = Blox::info('user'); # Curren user
    }

    # The initial data of the form
    if ($userInfo){
        foreach ($userInfo as $name => $value){
            $fields[$name]['value'] = $value;  
            unset($fields[$name]['validation']);}
    }
}


$template->assign('fields', $fields); 
if (!Blox::info('user','user-is-admin'))
    $terms['submit-and-return'] = '';
$template->assign('terms', $terms); # need?
$template->assign('mode', $mode);

# For password-update button
if ($userInfo2 = Blox::info('user')) { # Curren user
    if ($userInfo2['login'] == $fields['login']['value']) { # Only own passord
        $url = Blox::info('site','url').'/';
        if ($_REQUEST['password-update-href']) {
            $h = Url::decode($_REQUEST['password-update-href']);
            $url.= $h.(mb_strpos($h, '?')===false ? '?' : '&');
        } else
            $url.='?password-update&';
        $z = $_SESSION['Blox']['password-update-codes'][$userInfo2['id']] = Str::genRandomString(8);
        $url.= 'code='.Url::encode(serialize(['id'=>$userInfo2['id'], 'login'=>$userInfo2['login'], 'u-code'=>$z]));
        $template->assign('passwordUpdateUrl', $url);
    }
}


if (!$accepted)
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";
