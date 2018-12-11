<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">';
    include Blox::info('cms','dir').'/includes/output-multirec-buttons.php';
    if ($params['heading'])
        echo'<div class="heading" style="margin-bottom:5px">'.$params['heading'].'</div>';
    else {    
        echo'    
        <div class="heading" style="margin-bottom:5px">'.$terms['heading1'].' <b>'.$blockInfo['src-block-id'].'</b>. '.$terms['heading2'].'<br></div>
        <div class="small">';if ($blockInfo['id'] != $blockInfo['src-block-id']) echo' ('.$terms['delegated-from-block'].' '.$blockInfo['id'].')';echo'</div>';
    }
    echo'
    <br />
    <table>
    <tr>
    <td>';
        # Sort
        $sortable_tableId ='sortable';
        $sortable_formId ='ok';
        $sortable_inputId = 'sorted-rows-list';
        include Blox::info('cms','dir').'/includes/make-sortable.php';
        echo'
        <table class="hor-separators" id="'.$sortable_tableId.'">
            <thead>
                <tr class="small top">
                    <td style="vertical-align:middle; text-align:center">&uArr;&dArr;</td>
                    <td class="blox-vert-sep">&#160;</td>
                    <td align="center" class="gray">'.$terms['rec-id'].'</td>';
                    include Blox::info('cms','dir').'/includes/output-multirec-tablehead-fields.php';# Fields titles
                    echo'
                </tr>
            </thead>
            <tbody>';
                foreach ($tab as $row => $dat){
                    echo'
                    <tr id="'.$dat['rec'].'">
                        <td style="vertical-align:middle"><label class="handle"><div class="drag-handle"></div></label></td>
                        <td>&#160;</td>
                        <td align="center">'.$dat['rec'].'</td>';
                        foreach ($editingFields as $field) {
                            echo'
                            <td>&#160;</td>
                            <td>';
                                if (substr($dataTypes[$field], 0, 9) == 'timestamp')
                                    echo date('Y-m-d ', strtotime(substr_replace($dat[2], '', 8)));
                                else
                                    echo Text::truncate(Text::stripTags(trim($dat[$field]),'strip-quotes'), 40, 'plain');                                
                                echo'
                                &#160;
                            </td>';
                        }
                        echo'
                    </tr>';
                }
                echo'
            </tbody>
        </table>
        <form  id="'.$sortable_formId.'" action="?sort-manualy-update&block='.$blockInfo['id'].$pagehrefQuery.'" method="post" enctype="multipart/form-data">
            <input type="hidden" name="'.$sortable_inputId.'" id="'.$sortable_inputId.'" value="" />';
            echo $submitButtons;
            echo'
        </form>
    </td>
    </tr>
    </table>
</div>'; 