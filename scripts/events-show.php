<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');
        
    if (isset($_POST['events-to-del'])) { # deleting
        foreach ($_POST['events-to-del'] as $id => $aa) {
            $sql = "DELETE FROM ".Blox::info('db','prefix')."countevents WHERE id=?";
            Sql::query($sql, [$id]);
        }
    } elseif (isset($_POST['event'])) { # updating
        $event = $_POST['event'];
        $sql = "UPDATE ".Blox::info('db','prefix')."countevents SET `date`=?, description=? WHERE id=?";
        Sql::query($sql, [$event['date'], $event['description'], $event['id']]);
    }

    $sql = "SELECT * FROM ".Blox::info('db','prefix')."countevents ORDER BY `date` DESC, id DESC";  
    if ($result = Sql::query($sql)) {
        while ($row = $result->fetch_assoc())
            $events[] = $row;
        $result->free();
        $template->assign('events', $events);
    }

    include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";