<?php
# DEPRECATED. NOT USED
if (!Blox::info('user','user-is-admin'))
    Blox::execute('?error-document&code=403');
$pagehref = Blox::getPageHref();
Url::redirect($pagehref);
Tree::renewParentsIds();