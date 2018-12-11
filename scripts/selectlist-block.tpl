<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?edit&block='.$editBlock.'&rec='.$editRec.$pagehrefQuery.'" title="'.$terms['back-to-edit'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>
    <div class="heading">'.$terms['changing-select-list'].$terms['bar-title'].Admin::tooltip('select.htm').'</div>';
    if ($_GET['parent-list-item']) 
        echo sprintf($terms['bind-option'], '<b>'.$_GET['parent-list-item'].'</b>');
    echo'
  	<form action="?selectlist-block-update&edit-block='.$editBlock.'&edit-rec='.$editRec.$direction.$pagehrefQuery.'" method="post">
    <input name="edit-field" type="hidden" value="'.$editField.'" />
    <input name="parent-field" type="hidden" value="'.$_GET['parent-field'].'" />
    <input name="parent-list-rec-id" type="hidden" value="'.$_GET['parent-list-rec-id'].'" />
    <input name="edit-tpl" type="hidden" value="'.$editTpl.'" />
    <table>
    	<tr>
    	<td>';
            foreach ($selectListBlocks as $selectListBlockId => $selectList) {
                echo'
                <div style="float:left; margin: 9px 9px 9px 9px; border:solid 1px #ede9e0; border-color:#fff #808080 #808080 #fff; font-size:11px">
                    <div style="padding: 0px 9px 5px 9px;">
                        <div align="center" style="height:17px">
                            <input name="select-list-block-id" type="radio" value="'.$selectListBlockId.'"'; 
                                if ($selectListBlockId == $_SESSION['Blox']['select-lists'][$editRec][$_GET['edit-field']]['select-list-block-id']) 
                                    echo' checked'; 
                                echo' />
                        </div>';
                        foreach ($selectList['items'] as $item) 
                            echo $item.'<br />';
                        echo'
                    </div>
                    <div align="right" style="padding: 0px 1px 2px 0px; margin: 0 1px 1px 0">
                        <a target="_blank" class="button button-small" href="?page='.$selectList['block-page']['id'].'" title="'.$terms['see-block-on-page'].' '.$selectList['block-page']['id'];
                            if ($selectList['block-page']['title']) 
                                echo'('.$selectList['block-page']['title'].')';
                        echo'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-right.png" alt="&gt;" /></a>
                    </div>
                </div>';
            }
            echo'
            <div style="clear:both"></div>
            <div class="small">
                '.sprintf($terms['all-blocks-with-tpl'], '<b>'.$selectListTpl.'</b>').' <br />
                '.$terms['free-lists'].'
            </div>
        </td>
        </tr>
        <tr>
    	<td colspan="1">'.$submitButtons.'</td>
        </tr>
    </table>
    </form>
</div>';