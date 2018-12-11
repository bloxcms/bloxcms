<?php

    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    $pagehref = Blox::getPageHref();
    Url::redirect($pagehref);
    if (empty($_POST['groups'])) 
        Url::redirect($pagehref,'exit');
    $objectId = Sql::sanitizeInteger($_GET['obj']);
    
    /**
     * @todo Delete propositions with formulas: group-sees-hidden-page and group-is-editor-of-block
    if ('group-is-editor' == $_GET['formula']) {
        $objectId = NULL;
    }
     */
    if ('group-is-editor-of-block' == $_GET['formula']) {
        # Update descendants
        $refTypes = ['block', 'page'];
        $blockTreeParams = Tree::get($objectId, $refTypes);
        $refData = $blockTreeParams['ref-data'];
        foreach ($_POST['groups'] as $groupId => $action)
            accessToDescendants($groupId, $refData, $action);
    } elseif ('group-sees-hidden-page' == $_GET['formula'])
        ;#$objectId = page
    elseif ('group-is-subscriber' == $_GET['formula'])
        ;#$objectId =$blockInfo['src-block-id'];  # Delegated block
    else
    	return;# To prevent clogging of propositions

    if ($objectId || is_null($objectId)) {
        updateGroupsOfObjectDirectly($objectId);
        # Free from rights of lower rank
        relieveLowerRights($objectId);
    }






    # Common Functions

    function updateGroupsOfObjectDirectly($objectId)
    {
        foreach ($_POST['groups'] as $groupId => $action) {
            if (Proposition::get($_GET['formula'], $groupId, $objectId)) {# before access was permitted
               if (empty($action))
                    Proposition::set($_GET['formula'], $groupId, $objectId, false);}
            else {# before access was denied
                if ($action)
                    Proposition::set($_GET['formula'], $groupId, $objectId, true);
            }
        }
    }






    # Free from rights of lower rank
    function relieveLowerRights($objectId)
    {
        if ('group-is-editor-of-block' == $_GET['formula']) {
            # Open hidden pages that have that blocks 
            $delegators = Admin::getDelegators($objectId);
            $delegators[] = $objectId;
            foreach ($delegators as $aa) {
                # @todo Calculated only native pages, but should be all pages where this block is used
                # Native page, which have these blocks
                $delegatorPageId = Blox::getBlockPageId($aa);
                foreach ($_POST['groups'] as $groupId => $action)
                    if ($action)
                        Proposition::set('group-sees-hidden-page', $groupId, $delegatorPageId, false);
            }
        }
    }







    # Functions for group-is-editor-of-block

    function accessToDescendants($groupId, $refData, $action)
    {
        if (empty($refData)) 
            return;
        foreach ($refData as $i => $arr ) {
            if ('blocks'== $i) {
                foreach ($arr as $blockInfo)
                    accessToBlock($groupId, $blockInfo, $action);}
            elseif ('pages'== $i){
                foreach ($arr as $pageInfo) 
                    accessToDescendants($groupId, $pageInfo['ref-data'], $action);}
        }
    }




    function accessToBlock($groupId, $blockInfo, $action)
    {
        if (empty($blockInfo['delegated-id'])) {
            Proposition::set('group-is-editor-of-block', $groupId, $blockInfo['block-id'], $action);
            accessToDescendants($groupId, $blockInfo['ref-data'], $action);
        }
    }
