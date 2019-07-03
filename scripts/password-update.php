<?php
/*
 * We need $_SESSION['Blox']['password-update-codes'] for limit the time of password restore for non authenticated users.
 * $_GET['code'] - comes from a restore letter or from ?user-info. Outer password restore
 * $_POST['data']['code'] - comes from this form
 * @todo Do not create a url-request "&code=" and an array $_SESSION['Blox']['password-update-codes'] in other files if it is an authed user and &code is generated not for himself
 */
if ($u = Blox::info('user')) { # Emulate outer password restore for an authenticated user
    $z = $_SESSION['Blox']['password-update-codes'][$u['id']] = Str::genRandomString(8); 
    $userInfo = ['id'=>$u['id'], 'login'=>$u['login'], 'u-code'=>$z];
    #KLUDGE
    $data['code'] = Url::encode(serialize($userInfo));
    $template->assign('data', $data);
    $authed = true;
} elseif ($_GET['code'] || $_POST['data']['code'])
    $userInfo = unserialize(Url::decode($_GET['code'] ?: $_POST['data']['code'])); 
#
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
} elseif ($authed) { #KLUDGE
    ;
} else {
    #$errors[] = $terms['error'];
    Blox::execute('?error-document&code=403');
}
#
$template->assign('errors', $errors);
$template->assign('userInfo', $userInfo);
include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";
