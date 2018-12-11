<?php
if (!Blox::info('user','user-is-admin'))
    Blox::execute('?error-document&code=403');
$pagehref = Blox::getPageHref();
Url::redirect($pagehref);
foreach ($_POST['hidden-pages'] as $pageId => $isHidden) {
    if (empty($isHidden)) {
        $pageInfo = Router::getPageInfoById($pageId);
        if ($pageInfo['page-is-hidden']) {
            $sql = "UPDATE ".Blox::info('db','prefix')."pages SET `page-is-hidden`=0 WHERE id=?";
            Sql::query($sql, [$pageId]);
        }
    }
}