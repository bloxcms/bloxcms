<?php
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    
    # 1D permissions
    $formulas1 = ['user-is-admin', 'user-is-activated', 'user-is-editor'];
    # 2D permissions. See the same in users-of-object.php
    $formulas2 = ['user-is-editor-of-block', 'user-sees-hidden-page', 'group-has-user'];
    $formulas3 = ['user-is-subscriber'];

    Request::set();
    Query2::capture();
    
    if (isset($_POST['search'])) {
        Request::remove('users', 'part');
        Request::remove('users', 'sort');
        Query2::remove('part&sort');
    }

    if (!Request::get('users', 'sort'))
        Request::add('block=users&sort[id]=desc');
    Request::add('block=users&limit=50');
    $users = Request::getTable(Blox::info('db','prefix').'users', 'users');
    
    Query2::add('block=users');
    if (!$_REQUEST['search'])
        Query2::remove('highlight&fields&search&what&where');

    if (Sql::select('SELECT * FROM '.Blox::info('db','prefix').'groups LIMIT 1'))
        $template->assign('groupsExist', true);

    # Check user permissions
    foreach ($users as $i => $user) {
        $users[$i] = $user;
        foreach ($formulas1 as $formula)          
            if (Proposition::get($formula, $user['id']))
                $users[$i][$formula] = true;
        
        if (!$users[$i]['user-is-admin'])
            foreach ($formulas2 as $formula)
                if (Proposition::get($formula, $user['id'], 'any'))
                        $users[$i][$formula] = true;

        foreach ($formulas3 as $formula)
            if (Proposition::get($formula, $user['id'], 'any'))
                    $users[$i][$formula] = true;
    }


    # Parts
    if (Request::get('users','part','current'))
        $request['part']['current'] = Request::get('users','part','current');

    if (Request::get('users','part','num-of-parts'))
    {
        $request['part']['num-of-parts'] = Request::get('users','part','num-of-parts');
        if (Request::get('users','backward')) {
            for ($i=Request::get('users','part','num-of-parts'); $i >= 1 ; $i--)
               $parts[] = $i;
        }
        else {
            for ($i=1; $i <= Request::get('users','part','num-of-parts'); $i++)
               $parts[] = $i;
        }

        $request['part']['parts']  = $parts;
        # prev-next parts
        $currPartKey = array_search(Request::get('users','part','current'), $parts);
        $aa = $currPartKey - 1;
        $request['part']['prev'] = $parts[$aa];
        $aa = $currPartKey + 1;
        $request['part']['next'] = $parts[$aa];
    }
    # End of Parts

    $template->assign('users', $users);

    if (Store::get('allow-outer-registration'))
        $template->assign('allowOuterRegistration', true);

    $template->assign('request', $request);

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
