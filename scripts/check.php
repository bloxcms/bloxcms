<?php
    # Check assigned template
    if (!Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');
    $pagehref = Blox::getPageHref();
    if (isset($_GET['tpl'])) {
        ;
        if ($_GET['tpl'] = Sql::sanitizeTpl(urldecode($_GET['tpl'])))
            ;
        else
            Url::redirect($pagehref,'exit');
    }
    $regularId = Sql::sanitizeInteger($_GET['block']);
    $blockInfo = Blox::getBlockInfo($regularId);
    $pageId = Blox::getPageId();
    $pagehrefQuery = '&pagehref='.Url::encode($pagehref);
    Query2::capture('old-tpl&tpl&instance&instance-option');
    # Step 1 (before-tpl-selected)
    if ('before-tpl-selected' == $_GET['what'])
    {
        if ($defaultTpl = getDefaultTpl($blockInfo, $pageId)) {
            # If it is a sublist of nested navigation block, then do not assign as sublists are not always needed
            if (isset($_GET['assign-default-tpl'])) { # Come from the edit window
                if (templateHasPage($defaultTpl)) { # This is nav.block
                    $pBlockInfo = Blox::getBlockInfo($blockInfo['parent-block-id']);
                    # If parent block is is nav.block
                    if (templateHasPage($pBlockInfo['tpl'])) {
                        Url::redirect($pagehref,'exit');}}
            }

            # If there is a delegated block with the same template, then show it as an instance and mark the option "delegate"
            if ($lastDelegated = Admin::getLastDelegatedBlock($defaultTpl)) {
                Query2::add('tpl='.urlencode($defaultTpl).'&instance='.$lastDelegated.'&instance-option=delegate');
                $aa  = $terms['last-delegated1']. " <b>$defaultTpl</b> ";
                $aa .= $terms['last-delegated2']. " <b>$lastDelegated</b>.";
                Blox::prompt($aa); 
            }
            # No delegated blocks with the same template
            else {
                Query2::add('tpl='.urlencode($defaultTpl));
                if (empty($blockInfo['parent-block-id']))
                    $defaultTplPrompt = $terms['default-tpl-for-outer-block'];
                else
                    $defaultTplPrompt = $terms['default-tpl-for-block'];
                $aa = "$defaultTplPrompt <b>$defaultTpl</b>";
                # No such a template on the website # NOTTESTED: his was done in Blox::replaceBlockIdsByHtm()!
                $asError = false;
                if (!file_exists(Blox::info('templates', 'dir').'/'.$defaultTpl.'.tpl')) {
                    $aa .= '. '.sprintf($terms['no-tpl'], '<b>'.$defaultTpl.'</b>');
                    $asError = true;
                }
                Blox::prompt($aa, $asError);
            }
        }
        Url::redirect(Blox::info('site','url').'/?change&block='.$regularId.'&'.Query2::build().$pagehrefQuery,'exit');
    }
    # Step 2 (after-tpl-selected)
    elseif ('after-tpl-selected' == $_GET['what'])
    {
        $tpl = $_GET['tpl'];
        $changeUrl = Blox::info('site','url').'/?change&block='.$regularId;//.'&tpl='.$tpl; # tpl comes from changebar
        # For the outer block this check is not needed
        if (empty($blockInfo['parent-block-id'])) {
            Url::redirect($changeUrl.'&'.Query2::build().$pagehrefQuery);
            return;
        }
        
        # Determine "is-inc-in-delegated" again as we can come, not only from edit.php but from getBlock, we getBlock
        # NOTTESTED: May be this branche interrupted by branche "before-tpl-selected"
        $blockPage = Blox::getBlockPageId($regularId, $delegatedAncestorId);
        if ($delegatedAncestorId && $blockPage != $pageId) { #Block is nested within a delegated block
            $template->assign('blockPage', $blockPage);
            # If this is tested nav.block, it is useless to send to the parent page.
            if (templateHasPage($tpl)) {
                # Second pass of nav.block
                # If this is tested nav.block, then it must be assigned as new in any case (automatically or manually)
    			$changeUrl .= '&instance-option=change';
                Url::redirect($changeUrl.'&'.Query2::build().$pagehrefQuery);
                return;
            } else  { # ordinary block
                $pageInfo = Router::getPageInfoById($blockPage);
                $template->assign('pageTitle', $pageInfo['title']);
                $template->assign('regularId', $regularId);
                $template->assign('changeUrl', $changeUrl); # Is it necessary?
            }
        } else { # Block is not nested within a delegated block
            Url::redirect($changeUrl.'&'.Query2::build().$pagehrefQuery,'exit');
        }
    }
    # Step 3 (afterInstanceSelected)
    elseif ('before-assign' == $_GET['what']) {
        $instanceOption = $_GET['instance-option'];
        Url::redirect(Blox::info('site','url').'/?assign&block='.$regularId.'&'.Query2::build('instance-option='.$instanceOption).$pagehrefQuery);
        return;
    }
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";



    function templateHasPage($tpl)
    {
        
        $tdd = Tdd::get(['tpl'=>$tpl]); # KLUDGE: ['tpl'=>$tpl]
        $typesNames = Tdd::getTypesDetails($tdd['types'], ['page'], 'only-name');
        if ($typesNames) {
            foreach ($typesNames as $field => $typeNames) {
                if ('page' == $typeNames['name'])
                    return true;
            }
        }
    }



    # All blocks of the page, except specified block and its nested blocks.
    function pageBlocksExcept($pageId, $regularId=0)
    {
        $pageInfo = Router::getPageInfoById($pageId);
        $blockTreeParams = Tree::get($pageInfo['outer-block-id'], ['block']);
        $allBlocks = Tree::getBlocks($blockTreeParams['ref-data']['blocks']);
        $allBlocks[] = $pageInfo['outer-block-id'];

        # Blocks nested in the current block
        if (empty($regularId))
            return $allBlocks;
        else {
            # Delete from array the block and its nested blocks
            $blockTreeParams = Tree::get($regularId, ['block']);
            $incBlocks = Tree::getBlocks($blockTreeParams['ref-data']['blocks']);
            $incBlocks[] = $regularId;
            return array_diff($allBlocks, $incBlocks);
        }
    }



    function getDefaultTpl($blockInfo, $pageId)
    {
        if ($_GET['tpl']) # i.e. default assigned from getBlock
            return $_GET['tpl'];
        # Deferred default assignment. Determine anew, as maybe there was the default interrupted assignment
        else {
            if (empty($blockInfo['parent-block-id'])) # Default template for outer block
            {  
                # It doesn't work right! Wrong $pageInfo['parent-page-id']
                # Default template for outer block is determined in #autoassignment-of-the-page. This additional safety code.
                $pageInfo = Router::getPageInfoById($pageId);
                if ($pageInfo['parent-page-is-adopted']) {
                    #TODO
                    if (Blox::info('user','user-is-admin'))
                        Blox::prompt('TODO: Find default tpl for adopted pages', true);
                } else {
                    # Find the page-datum of the current page on the parent page
                    if ($pBlocks = pageBlocksExcept($pageInfo['parent-page-id'])) { # All blocks of the page
                        foreach ($pBlocks as $pBlockId) {
                            if ($foundDatumAddress = Tree::get($pBlockId, ['page'], ['page'=>$pageId]))
                                break;
                        }
                        # Is there default template?
                        if ($foundDatumAddress['tpl']) {
                            $quasiParentTdd = Tdd::get(['tpl'=>$foundDatumAddress['tpl']]);
                            $typeParams = Tdd::getTypeParams($quasiParentTdd['types'][$foundDatumAddress['field']]);
                            if ('/' == $typeParams['template'][0]) # absolute
                                return substr($typeParams['template'][0], 1);
                            else 
                                return $typeParams['template'][0]; # $defaultTpl
                        }
                    }
                }
            } 
            # Non outer block # TODO Use Tree::get() as for non outer block (see above)
            else {
                # Get fields of type "block" in the parent template
            	$pBlockInfo = Blox::getBlockInfo($blockInfo['parent-block-id']);
                $pTpl = $pBlockInfo['tpl'];
                $pTdd = Tdd::get($pBlockInfo);
                if ($typesDetailsB = Tdd::getTypesDetails($pTdd['types'], ['block'])) {
                    foreach ($typesDetailsB as $field => $aa) {
                        if ($defaultTpl = $aa['params']['template'][0])
                            $defaultTpls[$field] = Files::normalizeTpl($defaultTpl, $pTpl);
                    }
                    # Whether the block is "block" datum of one of fields?
                    if ($defaultTpls) {
                        $getUnfilteredTab = function ($tpl, $srcBlockId) {
                            $tbl = Blox::getTbl($tpl);
                            $sql = 'SELECT * FROM '.$tbl.' WHERE `block-id`=?';
                            if ($result = Sql::query($sql, [$srcBlockId])) {
                                while ($row = $result->fetch_row()) {
                                    $blockId = array_pop($row); # Remove last element [block-id]
                                    $row = ['rec'=>$row[0]] + $row; # Substitute '0' by assoc 'rec'
                                    unset($row[0]);
                                    $row['block'] = $blockId;
                                    $tab[] = $row;
                                }
                                $result->free();
                                return $tab;
                            }
                        };
                        #
                        if ($pTab = $getUnfilteredTab($pTpl, $pBlockInfo['src-block-id'])) {
                            foreach ($pTab as $dat)
                                foreach ($dat as $field=>$value)
                                    if ($defaultTpls[$field])
                                        if ($value == $blockInfo['id'])
                                            return $defaultTpls[$field]; # $defaultTpl

                        }
                    }
                }
            }
        }
    }


    /*
    # Is there the specified page in array of blocks
    function blocksHaveThePage($blocks, $searchPageId)
    {
        if (empty($blocks))
            return;
        foreach ($blocks as $regularId) {
            $blockInfo = Blox::getBlockInfo($regularId);
            $srcBlockId = $blockInfo['src-block-id'];
            if (Tree::get($srcBlockId, ['page'], ['page'=>$searchPageId]))
                return true;
        }
    }
    */