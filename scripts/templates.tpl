<?php 
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['heading'].'</div>
    <table>
    <tr>
    <td>
        <table  class="hor-separators">
        	<tr class="small center middle">
                <td><b>'.$terms['tpl-name'].'</b></td>
                <td><b>'.$terms['tpl-description'].'</b></td>
            </tr>';
            foreach ($tplParams as $tpl => $params) {
                echo'
                <tr>
                <td'; if($params['assigned-to-delegated']) echo' style="color:#900"'; echo'>'.$tpl.'&#160;</td>
                <td class="small" style="padding-left:5px;">';
                    if ($params['assigned']) {
                        if ($params['tpl-exists']) {
                            if (!$params['editable']) echo'<span class="gray">'.$terms['non-editable'].'</span>';
                            if ($params['description']) echo $params['description']; }
                        else
                            echo'<span class="red">'.$terms['assigned-but-no-tpl'].'</span>'; }
                    else
                        echo'<span class="gray">'.$terms['not-assigned'].'</span>';
                    $listOfPages = '';
                    foreach ($params['pages-of-blocks'] as $blockId => $blockPageId)
                        $listOfPages .= '| <a href="?page='.$blockPageId.'&bound-block='.$blockId.'#bound-block'.$blockId.'" target="_blank" title="'.$blockPageId.' ('.$titlesOfPages[$blockPageId].'). '.$terms['block'].' '.$blockId.'">'.$blockPageId.'</a> ';
                    if ($listOfPages)
                        echo'<div class="smaller grayer">'.substr($listOfPages, 1).'</div>';
                    if (empty($params['description']) && empty($listOfPages)) echo'&#160;';
                    echo'
                </td>
                </tr>';
            }
            echo'
        </table>
        <br />';
        echo $cancelButton;
        if ($description)
            echo'<div style="margin:60px 0px;">'.$description.'</div>';
        echo'
    </td>
    </tr>
    </table>
</div>';