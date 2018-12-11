<?php

    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    $pagehref = Blox::getPageHref();
    Url::redirect($pagehref);
    if (empty($_POST['users'])) 
        Url::redirect($pagehref,'exit');
    $objectId = Sql::sanitizeInteger($_GET['obj']);
    /*
    if ('user-is-editor' == $_GET['formula']) {
        $objectId = NULL;
        # to-do. Delete propositions with the formula user-sees-hidden-page и user-is-editor-of-block
    }
    */
    if ('user-is-editor-of-block' == $_GET['formula']) {
        # Update for descendants
        $refTypes = ['block', 'page'];
        $blockTreeParams = Tree::get($objectId, $refTypes);
        $refData = $blockTreeParams['ref-data'];
        foreach ($_POST['users'] as $userId => $action)
            accessToDescendants($userId, $refData, $action);
    }
    /*
    elseif ('user-is-editor-of-records' == $_GET['formula']) {
        $objectId =$blockInfo['src-block-id'];
    }
    */
    elseif ('user-sees-hidden-page' == $_GET['formula'])
        ;//$objectId = page
    elseif ('user-is-subscriber' == $_GET['formula'])
        ;//$objectId =$blockInfo['src-block-id'];
    else
    	return;# To prevent clogging of the propositions.

    if ($objectId || is_null($objectId)) {
        updateUsersOfObjectDirectly($objectId);
        relieveLowerRights($objectId);
    }


    # -----------------------------------------------------------------
    # Common Functions

    function updateUsersOfObjectDirectly($objectId)
    {
        foreach ($_POST['users'] as $userId => $action)
        {
            if (Proposition::get($_GET['formula'], $userId, $objectId)) {# before access was permitted
               if (empty($action))
                    Proposition::set($_GET['formula'], $userId, $objectId, false);}
            else {# before access was denied
                if ($action)
                    Proposition::set($_GET['formula'], $userId, $objectId, true);
            }
        }
    }



    function relieveLowerRights($objectId)
    {
        if ('user-is-editor-of-block' == $_GET['formula']) {
            $delegators = Admin::getDelegators($objectId);
            $delegators[] = $objectId;
            foreach ($delegators as $aa) {
                # WARNING: it Seems come from wrong pages. Here is a list of only native pages, but we need all pages where you block is used.
                # Native pages where these blocks are located
                $delegatorPageId = Blox::getBlockPageId($aa);
                foreach ($_POST['users'] as $userId => $action)
                    if ($action)
                        Proposition::set('user-sees-hidden-page', $userId, $delegatorPageId, false);
            }
        }
    }

    # -----------------------------------------------------------------
    # Functions for user-is-editor-of-block

    function accessToDescendants($userId, $refData, $action)
    {
        if (empty($refData)) 
            return;
        foreach ($refData as $i => $arr ){
            if ('blocks'== $i) {
                foreach ($arr as $blockInfo)
                    accessToBlock($userId, $blockInfo, $action);}
            elseif ('pages'== $i){
                foreach ($arr as $pageInfo) 
                    accessToDescendants($userId, $pageInfo['ref-data'], $action);}
        }
    }






    function accessToBlock($userId, $blockInfo, $action)
    {
        if (empty($blockInfo['delegated-id'])) {
            Proposition::set('user-is-editor-of-block', $userId, $blockInfo['block-id'], $action);
            accessToDescendants($userId, $blockInfo['ref-data'], $action);
        }
    }
