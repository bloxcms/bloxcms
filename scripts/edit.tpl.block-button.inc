<?php
# Used to display an edit button in the edit window

if (Blox::info('user','user-is-admin'))
    $blockInfo2 = Blox::getBlockInfo($dat['blocks'][$field]); # if {$fields[$field] = ['none']} then {$dat[$field]} is empty
else
    $blockInfo2 = Blox::getBlockInfo($dat[$field]);  

echo Admin::getEditButton(
    $blockInfo2['id'], 
    ['block-info'=>$blockInfo2, 'pagehref-query'=>$pagehrefQuery]
);
echo'&nbsp;';
if ($blockInfo2['delegated-id'])
    echo'<span style="color:#900">'.$dat[$field].'</span>';
else
    echo $dat[$field];

if ($blockInfo2['tpl'])
    echo'&nbsp;<span class="small">('.$blockInfo2['tpl'].')</span>';
