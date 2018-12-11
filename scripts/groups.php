<?php
    
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');

    # All users
    Request::set();
    Request::add(['groups'=>['sort'=>['name'=>['asc']]]]);
    Request::add('block=groups&limit=50'); # KLUDGE  To change you need to reset the session
    $groups = Request::getTable(Blox::info('db','prefix').'groups', 'groups');

    # Parts
    if (Request::get('groups','part','current'))
        $request['part']['current'] = Request::get('groups','part','current');

    if (Request::get('groups','part','num-of-parts')) {
        $request['part']['num-of-parts'] = Request::get('groups','part','num-of-parts');
        if (Request::get('groups','backward')) {
            for ($i=Request::get('groups','part','num-of-parts'); $i >= 1 ; $i--)
               $parts[] = $i; }
        else {
            for ($i=1; $i <= Request::get('groups','part','num-of-parts'); $i++)
               $parts[] = $i;
        }
        $request['part']['parts']  = $parts;
        # prev-next parts
        $currPartKey = array_search(Request::get('groups','part','current'), $parts);
        $aa = $currPartKey - 1;
        $request['part']['prev'] = $parts[$aa];
        $aa = $currPartKey + 1;
        $request['part']['next'] = $parts[$aa];
    }

    $template->assign('groups', $groups);
    if (Request::get('groups','search','words')) # words is searchWords
        $request['search'] = [
            'words'=>Request::get('groups','search','words'), 
            'what'=>Request::get('groups','search','what'),
            'where'=>Request::get('groups','search','where'),
        ];
    $template->assign('request', $request);

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
