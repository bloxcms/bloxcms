<?php

    /**
     * Activates, deactivates and removes the group
     */
     
    if (!Blox::info('user','user-is-admin'))
        Blox::execute('?error-document&code=403');
    //$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    Url::redirect(Blox::getPageHref());
    foreach ($_POST['groups'] as $groupId => $commands) {       
        # Remove
        if ($commands['delete']){   
            $sql = "DELETE FROM ".Blox::info('db','prefix')."groups WHERE id=?";
            Sql::query($sql, [$groupId]);
			# 2D
            Proposition::delete('group-has-user', $groupId, 'all'); }
        # Update
        else {   
            # group-is-editor
            $aa = $commands['group-is-editor'] ? true : false;
            Proposition::set('group-is-editor', $groupId, null, $aa);
            $sql = "UPDATE ".Blox::info('db','prefix')."groups SET activated=? WHERE id=?";
            Sql::query($sql, [$commands['activated'], $groupId]);
        }
    }