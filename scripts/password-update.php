<?php
  
/*
check the user login
do not pass code and take directly from Blox:: info('user')
No need: $_SESSION ['Blox'] ['password-update-codes']
*/
    
$userInfo = unserialize(Url::decode($_GET['code'] ?: $_POST['data']['code']));
if (!$userInfo) {
    $errors[] = $terms['error'];
} elseif (!isset($_SESSION['Blox']['password-update-codes'][$userInfo['id']])) {
    $errors[] = $terms['expired'];
} elseif ($userInfo['u-code'] <> $_SESSION['Blox']['password-update-codes'][$userInfo['id']]) {
    $errors[] = $terms['wrong-code'];
} elseif (isset($_GET['code'])) {
    ;
} elseif ($_GET['step'] == 'update') {
    $data = $_POST['data'];
    $template->assign('data', $data);
    foreach (['new-password', 'new-password-2'] as $v) {
        if (!Str::isValid($data[$v], 'password', $errorMessage))
            $notes[$v] = $errorMessage;
    }
    if ($notes) {
        $template->assign('notes', $notes);
        $errors[] = $terms['invalid-password'];
    } elseif ($data['new-password'] <> $data['new-password-2'])
        $errors[] = $terms['different-passwords'];
    # Save the password
    if (
        !$errors && 
        $userInfo['id'] &&
        $data['new-password']
    ) {
        $sql = 'UPDATE '.Blox::info('db','prefix').'users SET password=? WHERE id=?';
        $sqlValues = [
            password_hash($data['new-password'], PASSWORD_DEFAULT),
            $userInfo['id'],
        ];
        if (false === Sql::query($sql, $sqlValues))
            $errors[] = $terms['sql-fail'];
        else {
            if (!Blox::info('user'))
                Auth::get(['login'=>$userInfo['login'],'password'=>$data['new-password']]);
            Url::redirect(Blox::getPageHref());
        }
    }
} else
    Blox::execute('?error-document&code=404');
#
$template->assign('errors', $errors);
$template->assign('userInfo', $userInfo);
include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";