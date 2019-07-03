<?php

/** 
 * @todo Append $filterQuery to links in "select"
 * @todo Append $filterQuery to links in <form action="?update&...', but output-multirec-buttons.php uses Query2::
 */

$mterms = $terms['multi-editing'];
    
# Not ajax
if (!isset($_GET['add-new-rec'])) {
    Blox::addToFoot(Blox::info('cms','url').'/assets/jquery.form.min.js');
    Blox::addToFoot(Blox::info('cms','url').'/assets/blox.ajax.js');
    Blox::addToFoot(Blox::info('cms','url').'/assets/blox.edit.multi.js');

    # Additional submit button on top
    if (count($tab) > 20) { ?>
        <div style="margin: 0px 0px 44px">
        <button class="submit" onclick="document.getElementById('dat-edit-form').submit()"><?=$terms['submit']?></button>
        <button class="submit" onclick="document.getElementById('cancel-form').submit()"><?=$terms['cancel']?></button>
        </div><?php
    }
    echo'
        <form action="?update&block='.$blockInfo['id'].$filtersQuery.$direction.$pagehrefQuery.'" method="post" enctype="multipart/form-data" id="dat-edit-form" class="multi-rec">
            <input type="hidden" name="select-list-submit-field" id="select-list-submit-field" value="0" />
            <input type="hidden" name="select-list-submit-rec"   id="select-list-submit-rec"   value="0" />
            <input type="hidden" id="add-new-rec-src" value="?edit&block='.$blockInfo['id'].$filtersQuery.'&add-new-rec'.$pagehrefQuery.'" />'; # For blox.edit.multi.js
            # Show all records of the block
            if (Blox::info('user','user-is-admin') && $disableMultiRecFilters_show) {
                echo'
                <div style="padding-bottom:4px">
                    <input type="hidden"   name="settings[disable-multi-rec-filters]" value="0" />
                    <input type="checkbox" name="settings[disable-multi-rec-filters]" value="1" id="disable-multi-rec-filters"'; if ($settings['disable-multi-rec-filters']) echo'checked'; echo' /><span><label for="disable-multi-rec-filters">'.$terms['disable-multi-rec-filters'].'</label></span>
                </div>';
            }
        echo'
        <table class="hor-separators">
            <thead>
            <tr class="small top">
                <td align="center" class="gray">'.$mterms['rec-id'].'</td>';
                include Blox::info('cms','dir').'/includes/output-multirec-tablehead-fields.php';# Table head (titles of fields)
                if ($params['sort']['editable']) 
                    echo'<td><div class="gray">sort</div>'.$terms['sort-num'].'</td>';
                echo'<td class="blox-vert-sep">&nbsp;</td>';
                echo'<td>&nbsp;</td>';
            echo'
            </tr>';
            $newRecRow = '            
            	<td align="center"><button name="button-ok" type="submit" data-blox-overlay-load-icon-target="" value="add-new-rec" title="'.$mterms['create-new-rec'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-new-rec.png" alt="&equiv;" /></button></td>
                <td>&nbsp;</td>
                <td colspan="'.($tdCounter*2+1).'" class="small gray">'.$mterms['create-new-rec'].'</td>
            ';
            
            if ($newRecRow && $params['backward'])
                echo'<tr>'.$newRecRow.'</tr>';
            echo'
            </thead>';
            if ($newRecRow && !$params['backward'])
                echo'<tfoot><tr>'.$newRecRow.'</tr></tfoot>';
            echo'
            <tbody id="multi-edit">';
}
            $editHrefBase = '?edit&block='.$blockInfo['id'].$filtersQuery.$pagehrefQuery;
            $deleteHrefBase = '?recs-delete&block='.$blockInfo['id'].$filtersQuery.$pagehrefQuery;
            foreach ($tab as $dat)
            {
                $recId = $dat['rec'];
                $editHref   = $editHrefBase.'&rec='.$recId;
                $deleteHref = $deleteHrefBase.'&rec='.$recId.'&which='.$recId;
                echo'
                <tr>
                	<td align="center">'.$recId.' <a class="button" href="'.$editHref.'" title="'.$mterms['edit-rec-separately'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-edit-rec.png" alt="&equiv;" /></a></td>';//'.$recId.'
                    foreach ($editingFields as $field)
                    {
                        $typeDetails = $typesDetails[$field];                    
                        $typeName = $typeDetails['name'];
                        $typeParams = $typeDetails['params'];
                        $style = ($mstyles[$field]) ? ' style="'.$mstyles[$field].'"' : '';
                        $title = ($notes[$field]) ? ' title="'.Text::truncate(Text::stripTags($notes[$field], 'strip-quotes'), 300, 'plain').'"' : '';
                        $readonly = ($fields[$field]['readonly'])
                            ? ' readonly' 
                            : ''
                        ;
                        if ($fields[$field]['secret']) {
                            ;           
                        } elseif ($fields[$field]['hidden']) {
                            if (($typeName == 'tinyint' && $typeParams['tinyint'][0] == 1) || in_array($typeName, $nonHiddenableTypes))
                                ; # like 'secret'
                            else
                                echo'<input type="hidden" name="data['.$recId.']['.$field.']" value="'.$dat[$field].'" />';
                        } else {   
                            echo'
                            <td>&nbsp;</td>
                            <td>';
            		            if($typeName == 'bit' or $typeName == 'bool' or (($typeName == 'tinyint') && $typeParams['tinyint'][0] == 1)) {
            		                echo'
            		                <input type="hidden" name="data['.$recId.']['.$field.']" value="0" />
            		                <input type="checkbox"  name="data['.$recId.']['.$field.']" value="1"';if ($dat[$field]) echo' checked'; echo $readonly.$title.' />';# title="'.$dataTitles[$field].'" - if you put it, then you need to undress and remove quotation marks
            		            } elseif ($typeName == 'file') {
                                    $style = ($mstyles[$field]) ? ';'.$mstyles[$field].';overflow:hidden' : '';
                                    if ($dat[$field])
                                        $fl = ($typeParams['destination'][0] ?: 'datafiles').'/'.$dat[$field];
                                    else
                                        $fl = '';                                
                                    echo'
                                    <div style="float:left'.$style.'">
                                		<input name="data['.$recId.']['.$field.']" type="file"  size="40"'.$readonly.$title.' /><br>
                                        <div class="explanation">';
                    		                if ($fl)
                    		                    echo'<input  type="checkbox"  name="delete-data['.$recId.']['.$field.']" value="1" id="delete-data-'.$recId.'-'.$field.'" title="'.$mterms['mark-file-to-del'].'" /><span class="red"><label for="delete-data-'.$recId.'-'.$field.'">'.$terms['delete-file'].'</label></span> &nbsp; '; 
                                            echo $fl ?: $terms['not-uploaded'];
                                            echo '
                                        </div>
                                    </div>';
                                    # Preview
                                    if (getimagesize($fl)) {//$aa = getimagesize($typeParams['stamp'][0]);$stampWidth = $aa[0];$stampHeight = $aa[1];                    
                                        #fancybox 2
                                        echo'
                                        <div style="float:right">                                        
                                            <a href="'.$fl.'" data-fancybox-group="field-'.$field.'" title="'.sprintf($mterms['record'], $recId).'">
                                                <img src="'.$fl.'" class="preview" />
                                            </a>
                                        </div>';
                                    }
            		            } elseif ($typeName == 'block') {
                                    include'edit.tpl.block-button.inc'; 
                                } elseif ($typeName == 'page') {   
                                    if ($dat[$field]) {
                                        echo'<a href="?page='.$dat[$field].'">'.$dat[$field];
                                        $aa = Router::getPageInfoById($dat[$field]);                            
                                        if (!(empty($aa['title']) || $aa['title'] == "&nbsp;" || $aa['title'] == "&nbsp;"))
                                            echo' <span class="small">('.Text::truncate(Text::stripTags($aa['name'],'strip-quotes'), 40, 'plain').')</span>';                                            
                                        echo'</a>';
                                    }
                                } elseif ($typeName == 'select') {
            		                $parentField = &$typeParams['parentfield'][0];
            		                if (empty($readonly))
            		                    if ($parentField && empty($dat[$parentField]))
            		                        $readonly = ' readonly';
            		                echo'
            		                <select  name="data['.$recId.']['.$field.']" size="1"'.($readonly ? ' disabled' : '').' onchange="submitBySelectListItem('.$field.','.$recId.')"'.$title.'>';
                		                if ($selects[$recId][$field]) {
                		                    echo'<option value="0">&middot;&middot;&middot;</option>';
                		                    foreach ($selects[$recId][$field] as $recId2 => $item) {
                		                        echo'
                                                <option '; if ($recId2==$dat['selects'][$field]) echo' selected="selected" '; echo'value="'.$recId2.'">
                                                    '.$item.'
                                                </option>';
                		                    }
                		                }
                		                echo'
                                    </select>';
            		                if (empty($readonly)) {
                                        if (Permission::ask('record', [$blockInfo['src-block-id'], $recId])['edit']) {//get
            		                        /*edit-rec*/  
            		                        if (in_array('edit', $_SESSION['Blox']['select-lists'][$recId][$field]['controls']) && $dat['selects'][$field]) 
            		                            echo'&nbsp;<a class="button button-small" href="?edit&block='.$_SESSION['Blox']['select-lists'][$recId][$field]['select-list-block-id'].'&rec='.$dat['selects'][$field].'&direction=right'.$_SESSION['Blox']['select-lists'][$recId][$field]['defaults-queries'].$pagehrefQuery.'" title="'.$terms['edit-the-item'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-edit-rec.png" alt="&equiv;" /></a>';
            		                        /*newRec*/   
            		                        if (in_array('add', $_SESSION['Blox']['select-lists'][$recId][$field]['controls']) && isset($selects[$recId][$field])) 
            		                            echo'&nbsp;<a class="button button-small" href="?edit&block='.$_SESSION['Blox']['select-lists'][$recId][$field]['select-list-block-id'].'&rec=new&direction=right'.$_SESSION['Blox']['select-lists'][$recId][$field]['defaults-queries'].'&edit-field='.$field.$pagehrefQuery.'" title="'.$terms['add-the-item'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-new-rec.png" alt="+" /></a>';
            		                    }
            		                    if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
            		                        /*deleteRec*/
            		                        if (in_array('delete', $_SESSION['Blox']['select-lists'][$recId][$field]['controls'])  && $dat['selects'][$field]) 
            		                            echo'&nbsp;<a class="button button-small" href="?recs-delete&block='.$_SESSION['Blox']['select-lists'][$recId][$field]['select-list-block-id'].'&rec='.$recId.'&which='.$dat['selects'][$field].'&edit-field=$field&is-pick-values-field='.$pickValuesFields[$field].$pagehrefQuery.'" title="'.$terms['delete-the-item'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-delete-rec.png" alt="&times;" /></a>';
            		                    }
            		                    if (Blox::info('user','user-is-admin')) {
            		                        /*change*/   
            		                        echo'&nbsp;<a class="button button-small" href="?selectlist-block&edit-block='.$blockInfo['id'].'&edit-rec='.$recId.'&edit-tpl='.urlencode($blockInfo['tpl']).'&edit-field='.$field.'&edit-field-title='.urlencode($dataTitles[$field]); 
            		                        if ($parentField) 
            		                            echo'&parent-field='.$parentField.'&parent-list-rec-id='.$dat['selects'][$parentField].'&parent-list-item='.$selects[$recId][$parentField][$dat[$parentField]].'&parent-list-block-id='.$_SESSION['Blox']['select-lists'][$recId][$field]['parent-list-block-id']; 
            		                        echo'&direction=right'.$pagehrefQuery.'" title="'.$terms['change-selectlist-block'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-no-tpl.png" alt="?" /></a>';
            		                    }
            		                }
            		            }
                                /**
            		            elseif ($typeName == 'timestamp')
            		                echo'<input type="text" name="data['.$recId.']['.$field.']" value="'.$dat[$field].'" size="20" '.$readonly.' />';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
            		            elseif ($typeName == 'datetime')
            		                echo'<input type="text" name="data['.$recId.']['.$field.']" value="'.$dat[$field].'" size="20" '.$readonly.' />';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
            		            elseif ($typeName == 'date')
            		                echo'<input type="text" name="data['.$recId.']['.$field.']" value="'.$dat[$field].'" size="11" '.$readonly.' />';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
            		            elseif ($typeName == 'time')
            		                echo'<input type="text" name="data['.$recId.']['.$field.']" value="'.$dat[$field].'" size="8" '.$readonly.' />';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
            		            elseif ($typeName == 'year')
            		                echo'<input type="text" name="data['.$recId.']['.$field.']" value="'.$dat[$field].'" size="5" '.$readonly.' />';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
                                */
            		            elseif ($typeName == 'set') {
                                    # A list of items stored in the database
            		                $setDataElements = explode(',', $dat[$field]);  # @todo Solve the register problem for "set". Here comes the original case
            		                foreach ($typeParams['set'] as $i => $item) {
                                        echo'<input type="hidden"   name="data['.$recId.']['.$field.']['.$item.']" value="0" />';
                                        echo'<label style="white-space: nowrap" title="'.$tooltips[$field][$item].'"><input type="checkbox" name="data['.$recId.']['.$field.']['.$item.']" value="1"'.$readonly.(in_array($item, $setDataElements) ? ' checked' : '').' />'.$item.'</label>&nbsp; ';
            		                }        		                
            		                
            		                /** 
                                     * @todo To count length of a string and put ellipsis with reference to a single edit window
       		                         *
            		                 * todo Variant 2
                                     * I wanted to make a compact version with <select multiple>, but it appears not as dropdown menu and the interface is not so friendly.
                		             *     Redo
                		             *         name="data['.$recId.']['.$field.']['.$item.']" value="1"
                                     *            to
                                     *         name="data['.$recId.']['.$field.']"  value="'.$item.'"
                                     *         http://www.erichynds.com/blog/jquery-ui-multiselect-widget
                                     *         
                		             *    echo'<select multiple style="height:50px" name="data['.$recId.']['.$field.'-select]" size="1" '.($readonly ? ' disabled' : '').'>';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
                                     *    foreach ($typeParams['set'] as $aa => $item) {        		                    
                		             *        echo'<option ';
                		             *        if (in_array($item, $setDataElements)) 
                                     *            echo'selected="selected" ';
                		             *        echo'value="'.$item.'">'.$item.'</option>';
                		             *    }
                		             * 	echo'</select>';
                                     */
            		            } elseif ($typeName == 'enum') {
            		                $quotes = "'\" "; # ok
                                    $title2 = ($tooltips[$field][$dat[$field]])
                                        ? ' title="'.$terms['stored-value'].' '.$dat[$field].' '.$tooltips[$field][$dat[$field]].'"'
                                        : $title
                                    ;   
            		                echo'<select  name="data['.$recId.']['.$field.']" size="1"'.($readonly ? ' disabled' : '').$title2.'>';# title="'.$dataTitles[$field].'"  - if you put it, then you need to undress and remove quotation marks
            		                foreach ($typeParams['enum'] as $item){
            		                    $item = trim($item, $quotes);
            		                    echo'<option title="'.$tooltips[$field][$item].'" ';
            		                    if ($item==$dat[$field]) echo'selected="selected" ';
            		                    echo'value="'.$item.'">'.$item.'</option>';}
            		            	echo'</select>';
                                } elseif ($typeName == 'tinytext' || $typeName == 'text' || $typeName == 'mediumtext' || $typeName == 'longtext')
                                    echo'<span'.$title.'>'.Text::truncate(Text::stripTags($dat[$field],'strip-quotes'), 33, 'plain').'</span>';
            		            else
            		                echo'<input type="text" name="data['.$recId.']['.$field.']" value="'.htmlspecialchars($dat[$field]).'"'.$readonly.$style.$title.' />';
            
                                # NULL
                                if ($nullableFields[$field]  && !($fields[$field]['readonly'])) {
                                    echo'
                                    <input type="hidden" name="null-data['.$recId.']['.$field.']" value="0" />
                                    <label for="null-data-'.$recId.'-'.$field.'">
                                        <input type="checkbox" name="null-data['.$recId.']['.$field.']" value="1" id="null-data-'.$recId.'-'.$field.'"';
                                            if (is_null($dat[$field])) 
                                                echo' checked';
                                        echo' /><span class="gray">null</span>
                                    </label>';
                                }
                                echo'
                            </td>';
                                
                        }
                    }   
                    if ($params['sort']['editable']) 
                        echo'<td><input type="text" name="data['.$recId.'][sort]" value="'.$dat['sort'].'" style="width:70px"/></td>';
                    echo'
                    <td>&nbsp;</td>
                    <td><a class="button button-delete" href="'.$deleteHref.'" title="'.$mterms['delete-record'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-delete-rec.png" alt="&times;" /></a></td>
                </tr>';
            }

# Not ajax
if (!isset($_GET['add-new-rec'])) { 
            echo'
            </tbody>
        </table>';
        # Transition to other parts
        $script = 'edit';
        include Blox::info('cms','dir').'/includes/parts-navigation.php';
        echo $submitButtons;
        echo'
        </form>';
}
