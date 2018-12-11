<?php

    if (empty($_POST['subject']) && empty($_POST['message'])) 
        Blox::execute('?error-document&code=403');
    $pagehref = Blox::getPageHref();
    Url::redirect($pagehref);
    # from
    $sql = 'SELECT email  FROM '.Blox::info('db','prefix').'users WHERE id=?';
    if ($result = Sql::query($sql, [Blox::info('user','id')])) {
        $row = $result->fetch_row();
        $result->free();
        $from = $row[0];
    }
    # to
    $sql = 'SELECT email  FROM '.Blox::info('db','prefix').'users WHERE login=?';
    if ($result = Sql::query($sql, ['admin'])) {
        $row = $result->fetch_row();
        $result->free();
        $to = $row[0];
    }

    $mdat['message'] = $_POST['message'];
    
	$data = [
		'from'=> $from,
		'to'=> $to,					
		'subject'=> $_POST['subject'],
		'htm'=> $mdat,
		'txt'=> $terms['message-to-admin-send'],
	];
                    
    if (Email::send($data))
        Blox::prompt($terms['sent']);
    else
        Blox::prompt($terms['not-sent'],  true);