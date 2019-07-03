<?php
    if (!Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');

    $template->assign('description', Admin::getDescription('', true));
    
    (function(&$tplParams=[], &$titlesOfPages=[])
    {
        # Find all the template files, even unassigned
        $tplFiles = Files::glob(Blox::info('templates', 'dir').'/*.tpl');
        if ($tplFiles) {
            sort($tplFiles, SORT_NATURAL);
            $prelength = mb_strlen(Blox::info('templates', 'dir').'/');
            foreach ($tplFiles as $tplF) {
                if (mb_strpos($tplF, '--') !== false) # Garbage file
                    continue;
                $aa = substr_replace($tplF, '', -4) ; # Remove '.tpl' in the end
                $tpl = substr($aa, $prelength) ;  # To remove from the string the path to the folder "templates"
                $bb = []; # For non-editable templates
                $bb['tpl-exists'] = true;
                if (file_exists($aa.'.tdd')) {
                    $tdd = Tdd::get(['tpl'=>$tpl]);
                    $bb['description'] = $tdd['params']['description'];
                    if ($tdd['types'] || $tdd['xtypes'])
                        $bb['editable'] = true; 
                }
                $tplParams[$tpl] = $bb;
            }
        }
        # All blocks
        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'blocks';
        if ($result = Sql::query($sql)) {
            while ($row = $result->fetch_assoc()) {
                # Blocks of a template 
                if ($row['tpl']) {
                    $tplParams[$row['tpl']]['assigned'] = true;
                    $tplOfblock[$row['id']] = $row['tpl'];
                    $blockPageId = Blox::getBlockPageId($row['id']); # Find the page with this regular block.
                    #titlesOfPages
                    if (!isset($titlesOfPages[$blockPageId])) {
                        $pageInfo = Router::getPageInfoById($blockPageId);
                        $titlesOfPages[$blockPageId] = $pageInfo['title'];
                    }
                    $tplParams[$row['tpl']]['pages-of-blocks'][$row['id']] = $blockPageId;
                } elseif ($row['delegated-id']) {
                    if (!in_array($row['delegated-id'], $delegatedBlocks))
                        $delegatedBlocks[] = $row['delegated-id'];
                }
            }
            $result->free();
        }
        # Templates assigned to delegated blocks
        if ($delegatedBlocks)
            foreach ($delegatedBlocks as $dBlock)
                $tplParams[$tplOfblock[$dBlock]]['assigned-to-delegated'] = true;
    })($tplParams, $titlesOfPages); # return $tplParams and $titlesOfPages
    //$treatArrays($tplParams, $titlesOfPages); 
    
    $template->assign('tplParams', $tplParams);
    $template->assign('titlesOfPages', $titlesOfPages);
    
    include Blox::info('cms','dir').'/includes/button-cancel.php';
    include Blox::info('cms','dir').'/includes/display.php';