<?php
    
    /**
     * @todo Take the code from groups.php if you want the search 
     */
    
    if (!Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');
    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);

    if (isset($_GET['selected-group-id'])) {
        $selectedGroupId = (int)$_GET['selected-group-id'];
        $aa = Sql::select('SELECT * FROM '.Blox::info('db','prefix')."groups WHERE id=?",[$selectedGroupId]);
        $template->assign('groupInfo', $aa[0]);
        $users = Sql::select('SELECT * FROM '.Blox::info('db','prefix')."users ORDER BY login");
        $template->assign('users', $users);
        foreach (Proposition::get('group-has-user', $selectedGroupId, 'all') as $aa)
            $groupUsers[$aa['subject-id']] = true;
        $template->assign('groupUsers', $groupUsers);
        
        # TODO: after migration 'activated' to the table "users ", the script will be cancelled, as in groups-of-user.php. Remake tpl too
        foreach (Proposition::get('user-is-activated', 'all') as $aa)
            $activatedUsers[$aa['subject-id']] = true;
        $template->assign('activatedUsers', $activatedUsers);
        
        foreach (Proposition::get('user-is-admin', 'all') as $aa)
            $adminUsers[$aa['subject-id']] = true;
        $template->assign('adminUsers', $adminUsers);
    }
       
    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
