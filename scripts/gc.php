<?php

# NOT USED. NOT DEBUGGED. VERY OLD


    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    
    $pagehref = Blox::getPageHref();
    Url::redirect($pagehref);

    //gc();

    # Обновить таблицы указателей
    //$refTypes = ['block', 'page', 'file', 'select'];
    $refTypes = ['block', 'page', 'file'];
    //renewRefTypeDataTables($refTypes); // это было почему то закоментировано! Раскоментировал, но не проверил


    # Древо указателей
    $pageInfo = Router::getPageInfoById(1);
    $blockTreeParams = Tree::get($pageInfo['outer-block-id'], $refTypes);  // древо указателей
    $refData = $blockTreeParams['ref-data'];

    if (empty($refData))
        return;

    # Списки (блоков, страниц, файлов, шаблонов, таблиц), которые упоминаются в древе    // blocks pages files templates tables
    $GLOBALS['Blox']['correct-things']['blocks'][] = $pageInfo['outer-block-id'];// home page outer block

    
    $blockInfo = Blox::getBlockInfo($pageInfo['outer-block-id']);
    $GLOBALS['Blox']['correct-things']['tables'][] = $blockInfo['tpl'];// home page outer block



    $GLOBALS['Blox']['correct-things']['pages'][] = 1;// home page
    getCorrectThings($refData);// outputs $GLOBALS['Blox']['correct-things']
    order2dArr($GLOBALS['Blox']['correct-things']);

    # Все объекты сайта (блоки, страницы, файлы, шаблоны, таблицы)
    $allThings = getAllThings($refTypes);

    # Объекты, подлежашие удалению
    foreach ($allThings as $aa=>$bb)
    {
        if ($GLOBALS['Blox']['correct-things'][$aa])
            $thingsToDel[$aa] = array_diff ($allThings[$aa], $GLOBALS['Blox']['correct-things'][$aa]);
        else
            $thingsToDel[$aa] = $allThings[$aa];
    }
    order2dArr($thingsToDel);
    $deletedThings = deleteThings($thingsToDel, $refTypes);



    # prompt
    if (empty($deletedThings))
        Blox::prompt($terms['no-trash']);
    else
    {
        foreach ($deletedThings as $cl=>$arr)
        {
            sort($arr);
            $aa = '<br /><b>'.$terms['deleted'.$cl] .':</b> ';
            if ($arr) foreach ($arr as $item) $aa .= $item.', ';
            Blox::prompt(substr_replace($aa,'',-2));
        }
    }


//end





    function getCorrectThings($refData)// outputs $GLOBALS['Blox']['correct-things']. Корректный - значит попадает в древо
    {
        if (empty($refData)) return;

        foreach ($refData as $refType => $refs)
        {
            if ('blocks' == $refType)
            {
                foreach ($refs as $blockInfo)
                {
                    if ($blockInfo['id'])
                    {
                        $GLOBALS['Blox']['correct-things']['blocks'][] = $blockInfo['id'];
                        if ($blockInfo['src-block-id'])
                            $GLOBALS['Blox']['correct-things']['tables'][] = $blockInfo['tpl'];
                        if ($blockInfo['tpl'])
                            $GLOBALS['Blox']['correct-things']['templates'][] = $blockInfo['tpl'];
                    }
                    getCorrectThings($blockInfo['ref-data']);
                }
            }
            elseif ('pages' == $refType)
            {
                foreach ($refs as $pageInfo)
                {
                    if ($pageInfo['id'])
                        $GLOBALS['Blox']['correct-things']['pages'][] = $pageInfo['id'];
                    getCorrectThings($pageInfo['ref-data']);
                }
            }
            elseif ('files' == $refType)
            {
                foreach ($refs as $fl)
                {
                    if ($fl)
                        $GLOBALS['Blox']['correct-things']['files'][] = $fl;
                }
            }
        }
    }


    function order2dArr(&$arr)
    {
        if (empty($arr)) return;

        foreach ($arr as $aa=>$bb)
        {
            $cc = array_unique($bb);
            sort($cc);
            $arr[$aa] = $cc;
        }
    }




    function getAllThings($refTypes)
    {
        # Списки (блоков, страниц, файлов, шаблонов, таблиц), которые упоминаются где-либо
        $allThings = [];
        # blocks:
        $sql = 'SELECT id FROM '.Blox::info('db','prefix').'blocks';    //!!!
        $result = Sql::query($sql);
        while ($row = $result->fetch_assoc())
        {
            $allThings['blocks'][] = $row['id'];
        }
        $result->free();
        /*
        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'providers';
        $result = Sql::query($sql);
        while ($row = $result->fetch_assoc())
        {
            $allThings['blocks'][] = $row['id'];
            $allThings['blocks'][] = $row['provider-id'];
        }
        */

        # pages:
        $sql = 'SELECT id, `outer-block-id` FROM '.Blox::info('db','prefix').'pages'; $result = Sql::query($sql);
        while ($row = $result->fetch_assoc())
        {
            $allThings['pages'][] = $row['id'];
            if ($row['outer-block-id']) $allThings['blocks'][] = $row['outer-block-id'];
        }
        $result->free();


        # tabN
        $sql = "SHOW TABLES LIKE '".Blox::info('db','prefix')."tab_%'";
        $pattern = '/^'.Blox::info('db','prefix').'tab_([a-z0-9$_]+)$/i';
        $result = Sql::query($sql);
        while ($row = $result->fetch_row())
        {
            if (preg_match($pattern, $row[0], $matches))
                $allThings['tables'][] = $matches[1];// tabId
        }
        $result->free();
        # {ref}data
        foreach ($refTypes as $refType)
        {
            $sql = 'SELECT tdd FROM '.Blox::info('db','prefix').$refType.'data';  $result = Sql::query($sql);
            while ($row = $result->fetch_assoc())  $allThings['templates'][] = $row['tpl'];
            $result->free();
        }


        # checkRefSubjCountTables
        foreach (['pages', 'updates', 'downloads'] as $subj)
          if (Sql::tableExists(Blox::info('db','prefix')."count".$tbl))
              $refSubjCountTables[$subj] = true;
          
        # count{subj}
        $stats = ['blocks'=>'updates', 'pages'=>'pages', 'files'=>'downloads'];
        foreach ($stats as $obj=>$subj)
        {
            if ($refSubjCountTables[$subj])
            {
                $sql = 'SELECT obj FROM '.Blox::info('db','prefix').'count'.$subj;
                $result = Sql::query($sql);
                while ($row = $result->fetch_assoc())
                    $allThings[$obj][] = $row['obj'];
                $result->free();
            }
        }
        /*
        # cachedblocks:
        // Просто удалить все
        $sql = 'SELECT page, block FROM '.Blox::info('db','prefix').'cachedblocks'; $result = Sql::query($sql);
        while ($row = $result->fetch_assoc())
        {
            $allThings['pages'][] = $row['page'];
            $allThings['blocks'][] = $row['block'];
        }
        */
        # Files
        if ($handle = opendir(Blox::info('site','dir').'/datafiles'))
        {
            while (false !== ($fle = readdir($handle)))
                if ($fle != '.' && $fle != '..')
                    $allThings['files'][] = $fle;
            closedir($handle);
        }
        //foreach ($allThings as $aa=>$bb) $allThings[$aa] = array_unique($bb);

        order2dArr($allThings);

        return $allThings;
    }






    function deleteThings($things, $refTypes)
    {
        $dblocks = [];
        $dpages = [];
        $dfiles = [];
        $dtables = [];

        // Может быть еще проверить таблицы tabN без записей

        # blocks:
        if ($things['blocks'])
        {
            foreach ($things['blocks'] as $blockId)
            {
                $sql = 'DELETE FROM '.Blox::info('db','prefix')."blocks WHERE id={$blockId}";
                Sql::query($sql);
                

                $sql = 'DELETE FROM '.Blox::info('db','prefix').'pseudopages WHERE key LIKE \''.$blockId.'\'';
                if (isEmpty(Sql::query($sql)))
                    Blox::error('Не удалось удалить строку таблицы pseudopages с key LIKE =\''.$blockId.'\'');
                
                
                $deletedThings['blocks'][] = $blockId;
                $sql = 'DELETE FROM '.Blox::info('db','prefix')."usersofblocks WHERE `block-id`={$blockId}"; //TODO: usersofblocks уже не используется
                Sql::query($sql);

                if ($refSubjCountTables['updates'])
                {
                    $sql = 'DELETE FROM '.Blox::info('db','prefix')."countupdates WHERE obj='{$blockId}'";
                    Sql::query($sql);
                }
            }
        }
        # pages:
        if ($things['pages'])
        {
            foreach ($things['pages'] as $pageId)
            {
                $sql = 'DELETE FROM '.Blox::info('db','prefix')."pages WHERE id={$pageId}"; 
                Sql::query($sql);
                $deletedThings['pages'][] = $pageId;
                

                $sql = 'DELETE FROM '.Blox::info('db','prefix')."pseudopages WHERE phref LIKE '?page=$pageId&%'";
                Sql::query($sql);
                

                if ($refSubjCountTables['pages'])
                {
                    $sql = 'DELETE FROM '.Blox::info('db','prefix')."countpages WHERE obj='{$pageId}'";
                    Sql::query($sql);
                }
            }
        }
        # files
        if ($things['files'])
        {
            foreach ($things['files'] as $fl)
            {
                if ($refSubjCountTables['downloads'])
                {
                    $sql = 'DELETE FROM '.Blox::info('db','prefix')."countdownloads WHERE obj='$fl'";
                    Sql::query($sql);
                }
                if (Files::unLink(Blox::info('site','dir').'/datafiles/'.$fl)) 
                    $deletedThings['files'][] = $fl;
            }
        }
        # templates
        if ($things['templates'])
        {
            foreach ($things['templates'] as $tpl)
            {
                //$sql = 'DELETE FROM '.Blox::info('db','prefix')."tddtime WHERE tdd='$tpl'"; Sql::query($sql);//;
                //$sql = 'DELETE FROM '.Blox::info('db','prefix')."tabs WHERE tdd='$tpl'"; Sql::query($sql);//;

                foreach ($refTypes as $refType)
                {// Даже если не было записей в этой таблице, после обновления они появились и теперь лишние будут удалены
                    $sql = 'DELETE FROM '.Blox::info('db','prefix').$refType."data WHERE tdd='$tpl'";
                    // filedata почему -то не удалилось
                }//;
            }
        }
        # tables
        if ($things['tables'])
        {
            foreach ($things['tables'] as $tpl)
            {
                $tbl = Blox::getTbl($tpl);
                $sql = "DROP TABLE IF EXISTS $tbl";
                Sql::query($sql);
                $deletedThings['tables'][] = $tpl;
            }
        }
        Sql::query('TRUNCATE '.Blox::info('db','prefix').'cachedblocks');
        Sql::query('TRUNCATE '.Blox::info('db','prefix').'cachedpages');

        foreach (glob(Blox::info('site','dir').'/cached/*.*') as $fl) Files::unLink($fl);
        foreach (glob(Blox::info('site','dir').'/compiled/*.*') as $fl) Files::unLink($fl);
        foreach (glob(Blox::info('site','dir').'/temp/*.*') as $fl) Files::unLink($fl);

        return $deletedThings;
   }

?>