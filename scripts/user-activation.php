<?php
$user = unserialize(Url::decode($_GET['code']));
$login =  trim($user[0]);
$password = trim($user[1]);
$userInfo = Auth::get(['login'=>$login,'password'=>$password, ''], $errors);
if ($userInfo['id']) {
    Proposition::set('user-is-activated', $userInfo['id'], null, true);
    if (Proposition::get('user-is-activated', $userInfo['id'])) {
        $template->assign('userIsActivated', true);
        Blox::setSessUserId($userInfo['id']);
    	$path = dirname(Url::punyDecode($_SERVER['HTTP_HOST']).$_SERVER['REQUEST_URI']."x");  # "x" is fakefile
    	$mdat = sprintf($terms['user-is-activated'], "<b>$path</b>", "<b>$login</b>");
        foreach (Acl::getUsers(['user-is-admin'=>true]) as $adminInfo){
            $noAdminEmail = false;
            if ($adminInfo['email']) {
            	$data = [
            		'from'=> Acl::getFromEmail($adminInfo['email']),
            		'to'=> $adminInfo['email'],					
            		'subject'=> $terms['bar-title'].' '.$login,
            		'htm'=> $mdat,
            	];
                Email::send($data);
            } else
                $noAdminEmail = true;
        }
        if ($noAdminEmail)
            Blox::error('There is no admin email');
    } else
        $template->assign('errors', ['Error-643']);
} else {
    $template->assign('errors', $errors);
}
include Blox::info('cms','dir')."/includes/button-cancel.php";
include Blox::info('cms','dir')."/includes/display.php";