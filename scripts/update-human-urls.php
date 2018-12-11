<?php

$pagehref = Blox::getPageHref();
if (!Blox::info('user','id'))
    Url::redirect($pagehref,'exit');
else
    Url::redirect($pagehref);    

$sql = 'UPDATE '.Blox::info('db','prefix')."pseudopages SET alias='', phref=NULL, `parent-key`=NULL";
if (false !== Sql::query($sql)) {
    $sql = 'UPDATE '.Blox::info('db','prefix')."pages SET alias=''";
    if (false === Sql::query($sql))
        Blox::prompt($terms['error-regular-pages'], true);
    else
        Blox::prompt($terms['success']);
} else {
    Blox::prompt($terms['error-pseudopages'], true);
}