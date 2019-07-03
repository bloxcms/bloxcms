<?php

$pagehref = Blox::getPageHref();
if (Blox::info('user','user-is-admin'))
    Url::redirect($pagehref);
else
    Url::redirect($pagehref,'exit');

if (file_exists('cached-blocks'))
    if (!Files::delete('cached-blocks'))
        Blox::prompt($terms['err1'], true);
