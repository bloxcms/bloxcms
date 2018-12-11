<?php

    $pagehref = Blox::getPageHref();
    if (!Blox::info('user','id'))
        Url::redirect($pagehref,'exit');
    else
        Url::redirect($pagehref);    

    //if ($tddFiles = Files::recursiveGlob('assigned/*.tdd')) { Not all files will be aged
    if ($tddFiles = Files::recursiveGlob('templates/*.tdd')) {
        clearstatcache(); # procedure - no return
        foreach ($tddFiles as $tddFile) {
            if (!touch($tddFile)) {
                Blox::error($terms['err0'].' '.$tddFile);
                $nottouched = true;
            } else { # Store tpls
                preg_match('~^templates/(.*?)\.tdd$~', $tddFile, $matches);
                $refreshTpls[$matches[1]] = true; //shop/catalog/goods/description
            }
        }
        if (!$nottouched) 
            Blox::prompt($terms['actualization']);
        
        if ($refreshTpls)
            Store::set('blox-refresh-db-tpls', $refreshTpls);
            
    } else
        Blox::prompt($terms['err2'], true);

    if ($nottouched) 
        Blox::prompt($terms['err1'], true);


    