<?php
# NOT FOR EXTRADATA!
$pagehref = Blox::getPageHref();
echo'
<div class="blox-edit">';
    echo'
    <div id="backToEdit">';
        if (empty($_GET['phase']))  
            echo'<a class="button" href="'.$backUrl.'" title="'.$terms['back-to-edit'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>';
        echo'&#160;
    </div>
    <div class="heading" style="margin-bottom:5px">';
        if ('file-uploaded-and-unzipped' == $_GET['phase'] || 'file-uploaded' == $_GET['phase'] || 'block-data-deleted' == $_GET['phase'])
            echo'<div class="loading"></div>';
        elseif (isset($_GET['reset']))
            ;
        else
            echo'<div class="loading" style="width:0px" id="loading"></div>';
        echo $terms['import-of-recs'].Admin::tooltip('import-rowwise.htm', $terms['import-of-recs']).'
    </div>
    <div class="small">
        '.$terms['block'].': <b>'.$blockInfo['src-block-id'].'</b>';if ($blockInfo['id'] != $blockInfo['src-block-id']) echo' ('.$terms['delegated-from-block'].' '.$blockInfo['id'].')';echo'<br />
        '.$terms['tpl'].': <b>'.$blockInfo['tpl'].'</b>
    </div>
    <br />
    <table>
        <tr>
        <td>
        <ol id="log">';
            foreach ($_SESSION['Blox']['import-rowwise']['log'] as $l) {
                echo'<li'; if ($l['color']) echo' style="color:'.$l['color'].'"'; echo'>';
                    echo $terms[$l['phase']];
                    echo'<div style="font-size:11px;color:#000">'.$l['note'].'</div>';
                echo'</li>';
            }
            echo'
        </ol>';
        if ('file-uploaded-and-unzipped' == $_GET['phase'] || 'file-uploaded' == $_GET['phase']) {
            if ('replace' == $_SESSION['Blox']['import-rowwise']['insert-mode'])
                $newPhase = 'deleteBlockData';
            else
                $newPhase = 'loadData';
        } elseif ('block-data-deleted' == $_GET['phase']) {
            $newPhase = 'loadData';
        } elseif ('data-loaded' == $_GET['phase']) {
            unset($_SESSION['Blox']['import-rowwise']['log']);
            echo $cancelButton;
        } else {
            unset($_SESSION['Blox']['import-rowwise']['log']);
            # Sort
            $sortable_tableId ='sortable';
            $sortable_formId ='ok';
            $sortable_inputId = 'sorted-fields-list';
            include Blox::info('cms','dir').'/includes/make-sortable.php';
            echo'
            <div class="small" style="padding: 5px 0px 5px 5px"><a href="?import-rowwise&block='.$blockInfo['id'].'&phase&reset'.$pagehrefQuery.'">• '.$terms['default-settings'].'</a></div>
            <form id="'.$sortable_formId.'" action="?import-rowwise&block='.$blockInfo['id'].'&phase=upload'.$pagehrefQuery.'" method="post" enctype="multipart/form-data"  onsubmit="loading.style.width=\'23px\'; this.style.visibility=\'hidden\'; backToEdit.style.visibility=\'hidden\'; log.style.visibility=\'hidden\';">
                <table class="hor-separators middle" id="import-form">';
                    if (empty($_SESSION['Blox']['import-rowwise']['insert-mode']))
                        $_SESSION['Blox']['import-rowwise']['insert-mode'] = 'replace';//insert
                    echo'
                    <tr>
                        <td>'.$terms['insert-mode'].'</td>
                        <td>
                            <table width="80%">
                                <tr>
                                <td width="33%"><input name="insert-mode" value="insert"  type="radio" id="insert"'; if ($_SESSION['Blox']['import-rowwise']['insert-mode'] == 'insert') echo' checked'; echo' /><label for="insert">'.$terms['add'].'</label></td>
                                <td width="33%"><input name="insert-mode" value="replace" type="radio" id="replace"'; if ($_SESSION['Blox']['import-rowwise']['insert-mode'] == 'replace') echo' checked'; echo' /><label for="replace">'.$terms['replace'].'</label></td>
                                </tr>
                            </table>
                        </td>
                        <td class="small">'.$terms['insert-mode-note'].'
                    </tr>
                    <tr>
                        <td>'.$terms['location-of-file'].'<div class="small">(csv, txt, zip)<div  class="alert orange">'.$terms['zip-disabled'].'</div></div></td>
                        <td>
                    	<input type="file" name="import_file" />
                        <div class="small">'.$terms['upload-max-filesize'].': '; echo @ini_get('upload_max_filesize'); echo'</div>
                        <td class="small">'.$terms['location-of-file-note'].'</td>
                        </td>
                    </tr>
                    <tr>
                        <td>'.$terms['ignore-lines'].'</td>
                        <td><input type="text" name="csv-ignore-lines" value="'.$_SESSION['Blox']['import-rowwise']['csv-ignore-lines'].'" size="4"  /></td>
                        <td class="small">'.$terms['ignore-lines-note'].'</td>
                    </tr>
                    <tr>
                        <td>'.$terms['csv-fields-order'].'</td>
                        <td style="padding: 7px 0px 7px 0px;">
                            <table class="hor-separators" id="'.$sortable_tableId.'">
                                <tbody>';
                                # Order of fields
                                if ($_SESSION['Blox']['import-rowwise'][$sortable_inputId])
                                    $sortedFields = explode(',', $_SESSION['Blox']['import-rowwise'][$sortable_inputId]);
                                $aa = array_keys($tdd['types']);
                                $maxField = max($aa);
                                $maxField_old = max($sortedFields);
                                if (empty($sortedFields) || $maxField_old != $maxField) {
                                    $sortedFields = [];
                                    for ($i=1; $i <= $maxField; $i++)
                                        $sortedFields[] = $i;
                                }
                                foreach ($sortedFields as $field) {
                                    echo'
                                    <tr id="'.$field.'">
                                        <td style="vertical-align:middle"><label class="handle"><div class="drag-handle"></div></label></td>';
                                        if ($title = $tdd['titles'][$field])
                                            $aa = $title;
                                        else
                                            $aa = $tdd['types'][$field];
                                        echo'<td class="small">'.$field.'. '.Text::truncate(Text::stripTags($aa,'strip-quotes'), 40, 'plain').'</td>';
                                    echo'
                                    </tr>';
                                }
                                echo'
                                </tbody>
                            </table>
                            <input type="hidden" name="'.$sortable_inputId.'" id="'.$sortable_inputId.'" />
                        </td>
                        <td class="small">'.sprintf($terms['csv-fields-order-note'], $maxField).'</td>
                    </tr>';
                    if (empty($_SESSION['Blox']['import-rowwise']['add-recid-column']))
                        $_SESSION['Blox']['import-rowwise']['add-recid-column'] = true;//insert
                    echo'
                    <tr>
                        <td>'.$terms['add-recid-column'].'<label for="add-recid-column2"></label></td>
                        <td><input type="checkbox" name="add-recid-column" value="1" id="add-recid-column2"';if($_SESSION['Blox']['import-rowwise']['add-recid-column']) echo' checked';echo' /></td>
                        <td class="small">'.$terms['add-recid-column-note'].'&#160;</td>
                    </tr>
                    <tr>
                        <td>'.$terms['fields-terminated'].'</td>
                        <td><input type="text" name="csv-terminated" value="'; if($_SESSION['Blox']['import-rowwise']['csv-terminated']) echo $_SESSION['Blox']['import-rowwise']['csv-terminated']; else echo';';echo'" size="2"  /></td>
                        <td class="small">'.$terms['fields-terminated-note'].'&#160;</td>
                    </tr>
                    <tr>
                        <td>'.$terms['fields-enclosed'].'</td>
                        <td><input type="text" name="csv-enclosed" value="';if($_SESSION['Blox']['import-rowwise']['csv-enclosed']) echo $_SESSION['Blox']['import-rowwise']['csv-enclosed']; echo'" size="2"  /></td>
                        <td class="small">'.$terms['fields-not-enclosed'].'&#160;</td>
                    </tr>
                    <tr>
                        <td>'.$terms['fields-escaped'].'</td>
                        <td><input type="text" name="csv-escaped" value="'; if ($_SESSION['Blox']['import-rowwise']['csv-escaped']) echo $_SESSION['Blox']['import-rowwise']['csv-escaped']; else echo'\\'; echo'" size="2"  /></td>
                        <td class="small">'.$terms['fields-escaped-note'].'</td>
                    </tr>
                    <tr>
                        <td>'.$terms['lines-terminated'].'</td>
                        <td><input type="text" name="csv-new-line" value="';if($_SESSION['Blox']['import-rowwise']['csv-new-line']) echo $_SESSION['Blox']['import-rowwise']['csv-new-line']; else echo'\\n';echo'" size="4"  /></td>
                        <td class="small">'.$terms['lines-terminated-note'].'</td>
                    </tr>
                </table>
                '.$submitButtons.'
                <div class="small" style="width:500px"><br />'.$terms['note-content1'].'</div>
            </form>';
        }
        echo'
        </td>
        </tr>
    </table>';
    if ($newPhase) {
        echo'
        <script type="text/javascript">
            location.href="?import-rowwise&block='.$blockInfo['id'].'&phase='.$newPhase.$pagehrefQuery.'"; target="blank";
        </script>';
    }
    echo'
</div>';