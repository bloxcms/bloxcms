<?php
# Activate, deactivate and delete the user
if (!Blox::info('user','user-is-admin'))
    Blox::execute('?error-document&code=403');
#
if ($_POST['button-ok'] == 'submit-and-return')
    Url::redirectToReferrer();
else
    Url::redirect(Blox::getPageHref());
foreach ($_POST['users'] as $userId => $commands){
    if ($commands['delete']){   
    	#TODO You can make it easier: DELETE FROM, only to take into account the removal of formulas
    	# dim=1
    	$rights = ['user-is-activated', 'user-is-editor', 'user-sees-block-boundaries', 'user-as-visitor'];//, 'user-dont-see-edit-buttons'
    	foreach ($rights as $right)
            Proposition::delete($right, $userId);
        # dim=2
        $rights = ['user-is-editor-of-block', 'user-sees-hidden-page','group-has-user'];
    	foreach ($rights as $right)
            Proposition::delete($right, $userId, 'all');
        $sql = "DELETE FROM ".Blox::info('db','prefix')."users WHERE id=?";
        Sql::query($sql, [$userId]);
    }
    # Update
    else {   
        Proposition::set('user-is-activated', $userId, null, $commands['user-is-activated']); 
        Proposition::set('user-is-editor', $userId, null, $commands['user-is-editor']);
        # relieve Rights Lower than Editor
        /* Not yet removed the lower right. Suddenly it is necessary to remove back the user from the editor-in-chief
        if ($commands['user-is-editor'])
        {
        	$rights = ['user-is-editor-of-block', 'user-is-editor-of-records', 'user-sees-hidden-page'];
        	foreach ($rights as $right)
        	{
                ---$aa = new Proposition($right, $userId, 'all'); 
                $aa->delete();                      		                		
        	}
        }
        */
    }
}