<?php
    
    /**
     * Accepts or exclude one user from groups
     *
     * @todo To make the search take the code from groups.php
     */
    
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    
    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);

    if (isset($_GET['selected-user-id'])) {
        $selectedUserId = (int)$_GET['selected-user-id'];
        $aa = Sql::select('SELECT * FROM '.Blox::info('db','prefix')."users WHERE id=?",[$selectedUserId]);
        $template->assign('userInfo', $aa[0]);

        $groups = Sql::select('SELECT * FROM '.Blox::info('db','prefix')."groups ORDER BY name");
        $template->assign('groups', $groups);
        
        foreach (Proposition::get('group-has-user', 'all', $selectedUserId) as $aa)
            $groupsOfUser[$aa['object-id']] = true;
        $template->assign('groupsOfUser', $groupsOfUser);
    }
       
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
