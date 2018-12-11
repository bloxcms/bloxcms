<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">';
    include Blox::info('cms','dir').'/includes/output-multirec-buttons.php';//$backUrl
    if ($params['heading'])
        echo'<div class="heading" style="margin-bottom:5px">'.$params['heading'].'</div>';
    else {    
        echo'    
        <div class="heading" style="margin-bottom:5px">'.$terms['heading1'].' <b>'.$blockInfo['src-block-id'].'</b>. '.$terms['heading2'].'</div>
        <div class="small">';if ($blockInfo['id'] != $blockInfo['src-block-id']) echo' ('.$terms['delegated-from-block'].' '.$blockInfo['id'].')';echo'</div>';
    }
    echo'
    <br />        
    <form action="?recs-delete&block='.$blockInfo['id'].$filtersQuery.'&which=selected'.$pagehrefQuery.'" method="post" enctype="multipart/form-data">
        <table class="hor-separators">
            <thead>
            <tr class="small top">
                <td align="center" class="gray">'.$terms['rec-id'].'</td>';                
                include Blox::info('cms','dir').'/includes/output-multirec-tablehead-fields.php';# Fields titles
            echo'
            </tr>
            </thead>
            <tbody id="">';
                foreach ($tab as $dat) {
                    echo'
                    <tr>
                    	<td align="center">
                            <label for="delete-'.$dat['rec'].'" title="'.$term['mark'].'" style="display:block; border:1px solid rgb(127,0,0);  border:1px solid rgba(255,0,0,.4)">
                                <input type="checkbox" name="recs[]" value="'.$dat['rec'].'" title="" id="delete-'.$dat['rec'].'" />'.$dat['rec'].'
                            </label>
                        </td>';
                        foreach ($editingFields as $field) {
                            echo'
                            <td>&#160;</td>
                            <td>';
                                if (substr($dataType, 0, 9) == 'timestamp')
                                    echo date('Y-m-d ', strtotime(substr_replace($dat[2], '', 8)));
                                else
                                    echo Text::truncate(Text::stripTags($dat[$field],'strip-quotes'), 40, 'plain');
                                echo'
                            </td>';
                        }
                    echo'
                    </tr>';
                }
                echo'
            </tbody>
        </table>';
        # Parts
        $script = 'recs-select';
        include Blox::info('cms','dir').'/includes/parts-navigation.php';
        echo $submitButtons;
        echo'
    </form>
</div>';