<?php
            
    # KLUDGE
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');

    $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
    if (isset($_GET['selected-group-id'])) {
        if ($selectedGroupId = $_GET['selected-group-id'])
            $template->assign('selectedGroupId', $selectedGroupId); 
    }
	
    $fields = [
        'name'=>	['represent'=> $terms['name'], 'validation'=>'login'],
		'description'=>    ['represent'=> $terms['description']],
        'regdate'=> ['represent'=> $terms['regdate']],
    ];    
     

    # Form is submitted. Check
    if (isset($_POST['fields'])) 
    {
        foreach ($_POST['fields'] as $name=>$value)
            $fields[$name]['value'] = $value;        
        $accepted = true;
        foreach ($fields as $name => $field){
            $validation = $field['validation'];
            if ($validation) {
                if (Str::isValid($field['value'], $validation, $invalidMessage))
                    unset($fields[$name]['validation']); // is valid. In the $fields[][validation] remain only not valid
                else {
                    $accepted = false;
                    $fields[$name]['invalid-message'] = $invalidMessage;}
            }
        }
            
        if (isset($selectedGroupId)) {
            if ('new' != $selectedGroupId)
                $excludes = ['id'=>$selectedGroupId];
            else
                $excludes = [];
            # i.e. by admin. Only admin can change the login 
            if (Group::exist(['name'=>$fields['name']['value']], $excludes)){
                $accepted = false;
                $fields['name']['invalid-message'] .=  "<br />{$terms['invalid-message-1']}";
            }       
        } 

        if ($accepted)
        {
            foreach ($fields as $name => $field)
                $groupInfo[$name] = $field['value'];

            $updated = false;
            if ('new' == $selectedGroupId) {
                if (Group::create($groupInfo))
                    $updated = true;
            } elseif (Group::updateInfo($selectedGroupId, $groupInfo))
                $updated = true;

            if ($updated) {   
                if (Blox::info('user','id')){   
                    if (Blox::info('user','user-is-admin'))
                        Url::redirect(Blox::info('site','url').'/?groups'.$pagehrefQuery); 
                    else
                        $acceptMessage = "<b>{$terms['editor-accept-message']}</b>";
                }
            } else
                $errorMessage .= $terms['error-message2'];            
            # Do if ?
            $template->assign('acceptMessage', $acceptMessage); 
            $template->assign('errorMessage', $errorMessage); # TODO Send email to admin about the error 
            include Blox::info('cms','dir')."/includes/button-cancel.php";
        }
        else { # Not accepted
            $notAcceptMessage .= $terms['not-accept-message'];
            $template->assign('notAcceptMessage', $notAcceptMessage);  
        }
    } else { # Form is not submitted. This is a transition from the list
        if ($selectedGroupId){   
            if ('new' != $selectedGroupId)
                $groupInfo = Acl::getGroups(['group-id'=>$selectedGroupId])[0];
            $selectedGroupId = $_GET['selected-group-id'];
        } else
            $groupInfo = Blox::info('group');# The data of the current group

        # The initial data of the form
        if ($groupInfo){
            foreach ($groupInfo as $name => $value){
                $fields[$name]['value'] = $value;  
                unset($fields[$name]['validation']);}
        }
    }
    $template->assign('fields', $fields); 
    $template->assign('terms', $terms); # need?
    $template->assign('mode', $mode);      
    if (empty($accepted))
        include Blox::info('cms','dir')."/includes/buttons-submit.php";
    include Blox::info('cms','dir')."/includes/display.php";
