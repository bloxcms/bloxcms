<?php

/**
 * @uses fancybox 2
 */
 
Blox::addToFoot(Blox::info('cms','url').'/assets/jquery.mousewheel.js');
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
//qq($_GET);
if (!isset($_GET['add-new-rec'])) {
    echo'<div class="blox-edit">';
    
    //if (!isset($_GET['rec']))
    include Blox::info('cms','dir').'/includes/output-multirec-buttons.php';
    echo'      
    <div class="heading" style="margin-bottom:5px">';
        if ($xprefix) {
            if ($params['heading'])
                echo $params['heading'];
            else
                echo $terms['block-xdat'].' <b>'.$blockInfo['id'].'</b>';
        } else {
            if ($params['heading'])
                echo $params['heading'];
            else {
                echo $terms['block'].' <b>'.$blockInfo['id'].'</b>. ';    
                if (isset($_GET['rec'])) # Edit single record
                    echo sprintf($terms['single-editing']['headline'], '<b'.$markedStyle.'>'.$dat['rec'].'</b>');
                else
                    echo $terms['multi-editing']['headline'];
            }   
        }
        echo'
    </div>';

    $markedStyle = ($_GET['rec']=='new') ? ' class="blox-marked-yellow"' : '';
    # public
    if (!Blox::info('user','id') && $params['public']['editing-allowed'] && ($_GET['rec'] || $_GET['rec']=='new')) {
        if ($params['multi-record'])
            echo'<div>'.$terms['record'].' <b'.$markedStyle.'>'.$dat['rec'].'</b></div>';
        $onlyPublic = true; # see below
    } elseif (!$params['no-edit-bar'] || Blox::info('user','user-is-admin')) {
        echo'
        <table>
        <tr>
        <td style="padding-left:0">
            <div class="small">';
                if ($params['heading']) {
                    if (isset($_GET['rec']))
                        echo'<div>'.$terms['record'].' <b>'.$dat['rec'].'</b></div>';
                    echo'<div>'.$terms['block'].' <b>'.$blockInfo['id'].'</b></div>';
                }
                if ($blockInfo['delegated-id']) {
                    echo'
                    <div>
                        '.$terms['delegated-block'].' <b>'.$blockInfo['src-block-id'].'</b>';
                        echo' ('.$terms['num-of-delegations'].')
                    </div>';
                }
                echo'
                <div>'.$terms['tpl'].' <b>'.$blockInfo['tpl'].'</b></div>';
                if ($params['version'])
                    echo'<div>'.$terms['version'].' <b>'.$params['version'].'</b></div>';
                echo'
                <div>'.$params['description'].'</div>';//'.$terms['description'].' 
                if ($editorOfRecordInfo) {
                    echo'
                    <div>';
        	        	if ($editorOfRecordInfo['personalname']) {
                            $bb = $editorOfRecordInfo['personalname']; 
                            if ($editorOfRecordInfo['familyname']) 
                                $bb .= ' ';
                        }
        	        	if ($editorOfRecordInfo['familyname']) 
                            $bb .= $editorOfRecordInfo['familyname'];
        	        	if ($bb) 
                            $bb = '('.$bb.')';
        	        	echo $terms['editor-of-rec'].': <b>'.$editorOfRecordInfo['login'].'</b> '.$bb;
                        echo'
                    </div>';
                }
                echo'
            </div>
        </td>';
        if ($delegatedContainerParams['id'] && $delegatedContainerParams['block-page-id'] != Blox::getPageId()) {
            echo'
            <td class="blox-vert-sep">&nbsp;</td>
            <td class="warnings">';
                echo $terms['is-inc-in-delegated1'].' <b>'.$delegatedContainerParams['id'].'</b> ('.$delegatedContainerParams['tpl'].')';
                echo',<br />'.$terms['is-inc-in-delegated2'].' <a href="?page='.$delegatedContainerParams['block-page-id'].'&bound-block='.$delegatedContainerParams['id'].'#bound-block'.$delegatedContainerParams['id'].'" target="_blank"><b>'.$delegatedContainerParams['block-page-id'].'</b> ('.$delegatedContainerParams['block-page-title'].')</a>';
                echo Admin::tooltip('', $terms['block-is-delegated-from']);
            echo'
            </td>';
        }
        echo'
        </tr>
        </table>';
    } else {
        echo'<table><tr><td style="padding-left:0">'.$params['description'].'</td></tr></table>';
    }
    echo'<br />';
}

if ($noTddTypes) {
    echo $terms['no-tdd-types'];
    echo $cancelButton;
} else {
    
    $nonHiddenableTypes = ['bit','block','bool','enum','file','longtext','mediumtext','page','select','set','text']; # Add? 'blob','tinyblob','mediumblob','longblob'
    #$hiddenableTypes = ['bigint','date','datetime','decimal','double','float','int','mediumint','smallint ','time','timestamp','tinyint','varchar','year'];
    
    if (isset($_GET['rec']) || $xprefix) # Edit single record
        include Blox::info('cms','dir').'/scripts/edit.tpl.form.single.inc';
    else # Multiedit
        include Blox::info('cms','dir').'/scripts/edit.tpl.form.multi.inc';
}

if (!isset($_GET['add-new-rec'])) {
    echo'
    </div>';
    if ($description)
        echo '<div class="description">'.$description.'</div>';
}
