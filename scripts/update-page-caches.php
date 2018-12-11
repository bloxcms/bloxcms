<?php

$pagehref = Blox::getPageHref();
if (!Blox::info('user','id'))
    Url::redirect($pagehref,'exit');
else
    Url::redirect($pagehref);    


if (Cache::delete())
    Blox::prompt($terms['success']);
else
    Blox::prompt($terms['error']);
