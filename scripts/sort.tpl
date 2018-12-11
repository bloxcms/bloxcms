<?php
/**
 * @todo Highlight sorted columns like users.tpl
 */
 
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
# numOfRows
$rowsGroupLimit = 20;
$aa = $rowsGroupLimit*2 + 2;
if (isset($tab[$aa])) {
    $aa = array_keys($tab); # Do not merge!
    $lastRow = end($aa);
    $startSkipKey = $rowsGroupLimit;
    $finishSkipKey = $lastRow - $rowsGroupLimit + 1;
}
echo'
<div class="blox-edit">
    <a class="button" href="'.$backUrl.'" title=""><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>';
    #Reset
    if ($_GET['sort'] || $_GET['reverse']) {
        $arrow = '<span style="color:#D4D0C8">&radic;</span>';
        $title = $terms['reset_title'];
        $name = $terms['reset_name'];
    } else {  
        $arrow = '<span class="red" style="font-weight:bold">&radic;</span>';
        $title = '';
        $name = '<span class="gray">'.$terms['reset_name'].'</span>';
    }
    echo'
    '.$arrow.'
    <a class="button" style="font-size:11px" href="?sort&block='.$blockInfo['id'].'&sort'.$pagehrefQuery.'" title="'.$title.'">&#160;'.$name.'&#160;</a>';

    # Reverse
    if ($_GET['reverse']) {
        $arrow = '<span class="red" style="font-weight:bold">&radic;</span>';
        $title = '';
        $name = '<span class="gray">'.$terms['reverse_name'].'</span>';
    } else {  
        $arrow = '<span style="color:#D4D0C8">&radic;</span>';
        $title = $terms['reverse_title'];
        $name = $terms['reverse_name'];
    }
    echo'
    '.$arrow.'
    <a class="button" style="font-size:11px" href="?sort&block='.$blockInfo['id'].'&reverse=1'.$pagehrefQuery.'" title="'.$title.'">&#160;'.$name.'&#160;</a>
    <div class="heading" style="margin-bottom:5px">'.$terms['heading2'].'</div>
    <div class="small">
        '.$terms['heading1'].': <b>'.$blockInfo['src-block-id'].'</b>';if ($blockInfo['id'] != $blockInfo['src-block-id']) echo' ('.$terms['delegated-from-block'].' '.$blockInfo['id'].')';
        if ($backwardParam) 
            echo'<br />'.$terms['reverse_warning'];
        echo'
    </div><br />
    <table><tr><td>';
        # Sort
        echo'
        <br />
        <table class="hor-separators">
            <tr class="small top">
            <td align="center">'.$terms['rec-id'].'</td>';
            foreach ($editingFields as $field)
            echo'
            <td class="blox-vert-sep">&#160;</td>
            <td>'.$field.'.<br />'.Text::truncate(Text::stripTags($dataTitles[$field],'strip-quotes'), 40, 'plain').'</td>';
            echo'
            </tr>';
            # Sort
            $order = Request::get($blockInfo['id'],'sort','rec');
            if ($order == 'asc') {
                $arrow = '<span class="red">&dArr;</span>';
                $newOrder = 'desc';
                $title = $terms['sort_desc'];
            } elseif ($order == 'desc') {
                $arrow = '<span class="red">&uArr;</span>';
                $newOrder = 'asc';
                $title = $terms['sort_asc'];
            } else {  
                $arrow = '<span style="color:#D4D0C8">&dArr;</span>';
                $newOrder = 'asc';
                $title = $terms['sort_asc'];
            }
            echo'
            <tr>
                <td style="text-align:center; height:28px; padding-top:5px; font:bold 13px Verdana; white-space:nowrap;">'.$arrow.'&#160;<a class="button" href="?sort&block='.$blockInfo['id'].'&sort[rec]='.$newOrder.$pagehrefQuery.'" title="'.$title.'">'.$terms['sort'].'</a></td>';
                foreach ($editingFields as $field) {
                    $order = Request::get($blockInfo['id'],'sort', $field); 
                    if ($order == 'asc') {
                        $arrow = '<span class="red">&dArr;</span>';
                        $newOrder = 'desc';
                        $title = $terms['sort_desc'];
                    } elseif ($order == 'desc') {
                        $arrow = '<span class="red">&uArr;</span>';
                        $newOrder = 'asc';
                        $title = $terms['sort_asc'];
                    } else {  
                        $arrow = '<span style="color:#D4D0C8">&dArr;</span>';
                        $newOrder = 'asc';
                        $title = $terms['sort_asc'];
                    }
                    echo'
                    <td>&#160;</td>
                    <td style="text-align:center; height:28px; padding-top:5px; font:bold 13px Verdana; white-space:nowrap;">'.$arrow.'&#160;<a class="button" href="?sort&block='.$blockInfo['id'].'&sort['.$field.']='.$newOrder.$pagehrefQuery.'" title="'.$title.'">'.$terms['sort'].'</a></td>';
                }
                echo'
            </tr>';
            foreach ($tab as $row => $dat) {
                if ($startSkipKey && $row==$startSkipKey){
                    for ($i=0; $i<3; $i++){
                        echo'<tr><td align="center">. . .</td>';
                        foreach ($editingFields as $field)
                            echo'<td>&#160;</td><td>. . .</td>';
                        echo'</tr>';}
                } elseif ($startSkipKey &&  $startSkipKey < $row && $row < $finishSkipKey) {
                    ;
                } else {
                    echo'
                    <tr>
                        <td align="center"'; if (Request::get($blockInfo['id'],'sort','rec')) echo' bgcolor="#ede9e0"'; echo'>'.$dat['rec'].'</td>';
                        foreach ($editingFields as $field) {
                            echo'
                            <td>&#160;</td>
                            <td'; if (Request::get($blockInfo['id'],'sort', $field)) echo' bgcolor="#ede9e0"'; echo'>';
                            if (substr($dataTypes[$field], 0, 9) == 'timestamp')
                                echo date('Y-m-d ', strtotime(substr_replace($dat[2], '', 8)));
                            else
                                echo Text::truncate(Text::stripTags($dat[$field],'strip-quotes'), 40, 'plain');
                            echo'
                            &#160;
                            </td>';
                        }
                        echo'
                    </tr>';
                }
            }
            echo'
        </table>
        <form action="?sort-update&block='.$blockInfo['id'].$reverseSortQuery.$pagehrefQuery.'" method="post">';
            if ($pickKeyFields)
                echo'<div class="small" style="margin:9px"><input name="ignore-picks" type="checkbox" /> '.$terms['sort_all'].'</div>';
            echo'
            '.$submitButtons.'
        </form>
</td></tr></table>
</div>';