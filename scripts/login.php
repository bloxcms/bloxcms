<?php
/**
 * @todo github monolog/monolog
 */
if (!isset($_SESSION)) {
    Blox::execute('?error-document&code=403&note=no-session');
    exit;
}
#
if (isset($_POST['data'])) {
    # check the password   
    $data['login'] = trim($_POST['data']['login']);
    $data['password'] = trim($_POST['data']['password']);
    $data['save-password'] = $_POST['data']['save-password'];
    if (!Auth::get(['login'=>$data['login'], 'password'=>$data['password'], 'save-password'=>$data['save-password']], $err)) {
        $errors = [];
        if ($err) {
            foreach ($err as $v)
                $errors['auth'].= $v;            
        }        
    }
    # check the captcha
    if (isset($_POST['data']['captcha'])) {
        $data['captcha'] = $_POST['data']['captcha'];
        if (Captcha::exceeded('data[captcha]')) {
            $errors['captcha'] = $terms['captcha-exceeded'];
        } elseif (!Captcha::check('data[captcha]', $data['captcha'])) {
            $errors['captcha'] = $terms['captcha-incorrect'];
        }
    }
    #
    if (isset($errors)) {
        $template->assign('errors', $errors); //sleep(5); Bad idea
    } else { # auth data are correct
        if (Blox::ajaxRequested()) {
            echo'<script>location.reload()</script>';
            exit;
        } elseif (Blox::getScriptName() == 'page') { # This is custom login page
            if ($_GET['pagehref'])
                Url::redirect(Url::decode($_GET['pagehref']), 'exit'); # Do 'exit' otherwise returns to login page
        } elseif ($pagehref = Blox::getPageHref())
            Url::redirect($pagehref);
        else
            Url::redirect('');
    }
} elseif (isset($_COOKIE['blox'])) { # loginAndPassword
    if ($user = unserialize(Url::decode($_COOKIE['blox']))) {
        $data['login'] = $user[0];
        $data['password'] = $user[1];
        $data['save-password'] = true;
    }
}
#
$template->assign('data', $data);
if (Store::get('allow-outer-registration'))
    $template->assign('allowOuterRegistration', true);
include Blox::info('cms','dir')."/includes/buttons-submit.php";
include Blox::info('cms','dir')."/includes/display.php";
