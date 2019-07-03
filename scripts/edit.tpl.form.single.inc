<?php

/** 
 * @todo
 *    $selects[...]  substitute by $dat['selects'][$field]  ?
 *    For single checkboxes: if($typeName == 'bit' || $typeName == 'bool' || (($typeName == 'tinyint') && $typeParams['tinyint'][0] == 1))
 *    put <label> on name column of the table
 *    
 */

$sterms = $terms['single-editing'];
Blox::addToFoot(Blox::info('cms','url').'/assets/blox.edit.single.js');
$recId = $dat['rec'];
echo'
<form action="?update&block='.$blockInfo['id'].$filtersQuery.'&rec='.$recId.$direction; if ($xprefix) echo'&xprefix='.$xprefix; echo $pagehrefQuery.'" method="post" enctype="multipart/form-data" id="dat-edit-form" class="wide-fields">
    <input type="hidden" name="select-list-submit-field" id="select-list-submit-field" value="0" />';
    if ($xprefix)
       echo'<input type="hidden" name="xprefix" value="'.$xprefix.'" />';
    if ($editingFields)
    {
        $getHumanDateTimeFormat = function($format, $dateTimeTerms) { # $dateTimeTerms = $terms['date-time']
            $humanFormat = $format;
            foreach (str_split($format) as $code) {
                if ($dateTimeTerms[$code])
                    $humanFormat = str_replace($code, $dateTimeTerms[$code], $humanFormat);
            }
            return $humanFormat;
        };
        
        echo'
        <table class="hor-separators">';
        if (!$params['no-edit-bar'] || Blox::info('user','user-is-admin')) { 
            echo'
            <tr class="small center middle">
                <td>'.$terms['field'].'</td><td>'.$terms['name'].'</td>
                <td>'.$terms['value'].'</td>
                <td>'; 
                if ($notes) 
                    echo $terms['note']; 
                    echo'
                </td>
            </tr>';
        }

        foreach ($editingFields as $field)
        {
            $typeDetails = $typesDetails[$field];
            $typeName = $typeDetails['name'];
            $typeParams = $typeDetails['params'];

            if ($fields[$field]['secret']) {
                ;
            } elseif ($fields[$field]['hidden']) {
                if (($typeName == 'tinyint' && $typeParams['tinyint'][0] == 1) || in_array($typeName, $nonHiddenableTypes))
                    ; # like 'secret'
                else
                    echo'<input type="hidden" name="dat['.$field.']" value="'.$dat[$field].'" title="" />';
            } else {
                if ($captions[$field]) {
                    echo'<tr><td colspan="4" class="caption">'.$captions[$field].'</td></tr>';
                    unset($captions[$field]); # In order to output the tail
                }
                echo'
                <tr>';
                echo'<td class="field">'.$field.'.</td>';
                /*
                if (!($params['no-edit-bar'])) # KLUDGE: perm
            	    echo'<td class="field">'.$field.'.</td>';
                else
            	    echo'<td class="field">'.++$sequenceNum.'.</td>';
                */
                echo'
            	<td class="name" style="vertical-align:top">';
                    if (empty($dataTitles[$field])) {
                        if ($typeName == 'block')
                            echo $terms['block'];
                        elseif ($typeName == 'page')
                            echo $terms['page'];
                        elseif ($typeName == 'file') {
                            if ($sourcefield = $typeParams['sourcefield'][0]) {
                                # Separate stamping for dependent images
                                if ($typeParams['stamp'][0]) {
                                    # the top-original source of images
                                    if ($originalSrcFields[$sourcefield])
                                        $origSrcField = $originalSrcFields[$sourcefield];
                                    else
                                        $origSrcField = $sourcefield; # original image
                                    $originalSrcFields[$field] = $origSrcField;
                                    $aa = $origSrcField;
                                    if ($origSrcField != $sourcefield)
                                        $aa .= ' '.$sterms['or'].' '.$sourcefield;
                                    $origSrcFieldAtr = ' data-origSrcField="'.$origSrcField.'"'; }
                                else
                                    $aa = $sourcefield;
                                echo'<span class="small">'.sprintf($sterms['file-of-field'], $aa).'</span>';
                            }
                        }
                    } elseif ($typeName == 'bit' or $typeName == 'bool' or (($typeName == 'tinyint') && $typeParams['tinyint'][0] == 1)) { # Label For Checkbox
                        echo'<label for="dat'.$field.'">'.$dataTitles[$field].'</label>';
                    } else 
                        echo $dataTitles[$field];
                    echo'
                        &nbsp;
                </td>';
                
                if(
                    $typeName == 'bit' || 
                    $typeName == 'bool' || 
                    (($typeName == 'tinyint') && $typeParams['tinyint'][0] == 1) ||
                    $typeName == 'block' ||
                    $typeName == 'page' ||
                    $typeName == 'set' 
                    //$typeName == 'enum'
                ) 
                    $colspan = ' colspan="2"';
                else
                    $colspan = '';
                    
                echo'
                <td class="inputs"'.$colspan.'>';
                    $style = ($styles[$field]) ? ' style="'.$styles[$field].'"' : '';
                    # Non-editable fields, only the default record
                    $readonly = ($fields[$field]['readonly'])
                        ? ' readonly' 
                        : ''
                    ;
                    $autofocus = ($params['autofocus-field'] == $field) ? ' autofocus' : '';
                        
                    if($typeName == 'bit' or $typeName == 'bool' or (($typeName == 'tinyint') && $typeParams['tinyint'][0] == 1)) {
                        echo'
                        <input type="hidden" name="dat['.$field.']" value="0" />
                        <label>
                            <input type="checkbox" name="dat['.$field.']" id="dat'.$field.'" value="1" title=""'.($dat[$field] ? ' checked' : '').$readonly.$autofocus.$style.' /> 
                            
                        </label>';
                    }
                    elseif ($typeName == 'file')
                    {
                        if ($dat[$field])
                            $fl = ($typeParams['destination'][0] ?: 'datafiles').'/'.$dat[$field];
                        else
                            $fl = '';
                        
                        # chunk file upload
                        if ($typeParams['chunk'][0])
                        {   
                            $toChunk = true;
                            ?>
                            <div id="fileupload<?=$field?>" style="border:dashed 1px #fff; padding: 5px 10px">
                                <div class="row">
                                    <div class="pull-left" style="margin-left:15px">
                                        <span class="btn btn-default btn-xs fileinput-button"><!-- The fileinput-button span is used to style the file input field as button // not button-->
                                            <span><?=$sterms['select-file']?></span>
                                            <input data="fileupload-input" type="file" name="files[]" multiple><!-- Only files[] or files --><!-- The file input field used as target for the file upload widget -->
                                            <input name="dat[<?=$field?>]" type="hidden"  value="" />
                                        </span>
                                    </div>
                                    <div class="pull-left" style="padding-top:4px; margin-left:10px">
                                        <div data="fileupload-filename" class="files"><?=$sterms['drag-file']?></div><!-- The container for the uploaded files -->
                                    </div>
                                </div>
                                <div id="progress" class="progress" style="margin-top:6px; margin-bottom:0; background:transparent!important; border-bottom:solid 1px rgba(255,255,255,0.4); height:10px"><!-- The global progress bar -->
                                    <div class="progress-bar progress-bar-primary" style="opacity:0.5"></div>
                                </div>                                
                                <?php
                                if ($fl) {
                                    echo'<div class="explanation">';
                                    echo'<input type="checkbox"  name="delete-dat['.$field.']" value="1" id="delete-dat'.$field.'" title="'.$sterms['mark-file-to-del'].'" /><span class="red"><label for="delete-dat'.$field.'">'.$terms['delete-file'].'</label></span> &nbsp;';
                                    echo $fl; //echo $fl ?: $terms['not-uploaded'];
                                    echo'</div>';
                                }
                                ?>
                            </div>
                            <?php
                            $acceptFileTypes =  '';
                            if ($typeParams['allowedformats']) {
                                foreach ($typeParams['allowedformats'] as $f)
                                    $aa.='|'.$f;
                                $acceptFileTypes =  ', acceptFileTypes: /(\.|\/)('.substr($aa,1).')$/i';
                            }
                            Blox::addToFoot('
                                <script>
                                $(function () {
                                    "use strict";
                                    var url = "?fileupload";
                                    $(\'#fileupload'.$field.' [data="fileupload-input"]\').fileupload({
                                        maxChunkSize: '.$typeParams['chunk'][0].',
                                        dropZone: $("#fileupload'.$field.'"),
                                        url: url,
                                        dataType: "json",
                                        done: function (e, data) {
                                            $.each(data.result.files, function (index, file) {
                                                $(\'#fileupload'.$field.' [name="dat['.$field.']"]\').val(file.name);
                                                $(\'#fileupload'.$field.' [data="fileupload-filename"]\').html("'.sprintf($sterms['file-is-ready-to-save'], '<b>"+file.name+"</b>').'");
                                            });
                                        },
                                        progressall: function (e, data) {
                                            var progress = parseInt(data.loaded / data.total * 100, 10);
                                            $("#progress .progress-bar").css(
                                                "width",
                                                progress + "%"
                                            );
                                        }
                                        '.$acceptFileTypes.'
                                    })
                                    .prop("disabled", !$.support.fileInput)
                                    .parent().addClass($.support.fileInput ? undefined : "disabled");
                                    
                                });
                                </script>'
                            );
                                    /** 
                                    //add after ".parent().addClass"
                                    //TODO for "acceptFileTypes"
                                    //https://stackoverflow.com/questions/17451629/maxfilesize-and-acceptfiletypes-in-blueimp-file-upload-plugin-do-not-work-why
                                    //https://stackoverflow.com/questions/12506459/blueimp-jquery-file-upload-how-do-i-get-the-error-message-if-the-file-type-was
                                    //http://zqsmm.qiniucdn.com/data/20130618191236/index.html
                                    .on('fileuploadadd', function (e, data) {
                                        //console.log(data.files[0].error); // "error" exists in the object butcannot be ouput!
                                    })
                                    */
                        }
                        else # ordinary file upload
                        {   
                            echo'
                            <div style="float:left">
                        		<input name="dat['.$field.']" type="file"  size="40" title=""'.$readonly.$style.' />';
                                if ($fl) {
                                    echo'<div class="explanation">';
                                    echo'<input  type="checkbox"  name="delete-dat['.$field.']" value="1" id="delete-dat'.$field.'" title="'.$sterms['mark-file-to-del'].'" /><span class="red"><label for="delete-dat'.$field.'">'.$terms['delete-file'].'</label></span> &nbsp;';
                                    echo $fl; //echo $fl ?: $terms['not-uploaded'];
                                    echo' <a href="'.$fl.'" download="" class="button button-xsmall" title="'.$terms['download-file'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-down.png" alt="" /></a>';
                                    echo'</div>';
                                }
                            echo'
                            </div>';                            
                            # Preview
                            if (getimagesize($fl)) {# if is image  //$aa = getimagesize($typeParams['stamp'][0]);$stampWidth = $aa[0];$stampHeight = $aa[1];                    
                                echo'<div style="float:left; position:relative; margin-left:5px">
                                    <a href="'.$fl.'" data-fancybox-group="field-'.$field.'" title="">
                                        <img src="'.$fl.'" class="preview" />
                                    </a>';
                                    if (empty($preparationOfImagesExists)){
                                        echo'<div style="position:absolute; top:-7px; right:-9px">'.Admin::tooltip('preparation-of-images.htm', $terms['preparation-of-images']).'</div>';
                                        $preparationOfImagesExists = true;}
                                echo'</div>';
                            }
                            /**
                            # MaxFilesize
                            if (empty($uploadMaxFilesize))
                                $uploadMaxFilesize = @ini_get('upload_max_filesize');
                            echo'<br><span style="padding-left:7px; font-size:9px;">'.$terms['upload-max-filesize'].': '.$uploadMaxFilesize.'</span>';
                            */


                            # stamp
                            if ($typeParams['stamp'][0]) # If the stamp file is declared, but other parameters are not declared in the tdd
                            {
                                if(file_exists($typeParams['stamp'][0]))
                                {
                                    echo'
                                    <div style="clear:both"></div>';
                                    if (!isset($typeParams['stamp'][1])) 
                                    {
                                        echo'
                                        <div class="stamp"'.$origSrcFieldAtr.'>';
                                        # Check at the bottom right by default
                                        if (!isset($settings['stamps'][$field])) {
                                            $settings['stamps'][$field]['placements']['bottom-right'] = 1;
                                            $settings['stamps'][$field]['scale'] = 20;
                                            $settings['stamps'][$field]['indents']['horizontal'] = 5;
                                            $settings['stamps'][$field]['indents']['vertical'] = 5;
                                        }
                                        echo'
                                        <table><tr class="small right middle">
                                            <td style="text-align:center"><div class="stamp-img-wrap"><img class="stamp-img" src="'.$typeParams['stamp'][0].'" /></div></td>
                                            <td class="title">'.$sterms['stamp_places'].'</td>
                                            <td>
                                                <div class="placement">
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][top-left]"    value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][top]"         value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][top-right]"   value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][left]"        value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][center]"      value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][right]"       value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][bottom-left]" value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][bottom]"      value="0" />
                                                    <input type="hidden" name="settings[stamps]['.$field.'][placements][bottom-right]"value="0" />
                                                    <div>
                                                        <input data-placement="top-left"  type="checkbox" name="settings[stamps]['.$field.'][placements][top-left]"  value="1"'; if ($settings['stamps'][$field]['placements']['top-left'])  echo' checked';echo' title="top-left" />
                                                        <input data-placement="top"       type="checkbox" name="settings[stamps]['.$field.'][placements][top]"       value="1"'; if ($settings['stamps'][$field]['placements']['top'])       echo' checked';echo' title="top" />
                                                        <input data-placement="top-right" type="checkbox" name="settings[stamps]['.$field.'][placements][top-right]" value="1"'; if ($settings['stamps'][$field]['placements']['top-right']) echo' checked';echo' title="top-right" />
                                                    </div>
                                                    <div>
                                                        <input data-placement="left"   type="checkbox" name="settings[stamps]['.$field.'][placements][left]"   value="1"'; if ($settings['stamps'][$field]['placements']['left'])   echo' checked';echo' title="left" />
                                                        <input data-placement="center" type="checkbox" name="settings[stamps]['.$field.'][placements][center]" value="1"'; if ($settings['stamps'][$field]['placements']['center']) echo' checked';echo' title="center" />
                                                        <input data-placement="right"  type="checkbox" name="settings[stamps]['.$field.'][placements][right]"  value="1"'; if ($settings['stamps'][$field]['placements']['right'])  echo' checked';echo' title="right" />
                                                    </div>
                                                    <div>
                                                        <input data-placement="bottom-left"  type="checkbox" name="settings[stamps]['.$field.'][placements][bottom-left]"  value="1"'; if ($settings['stamps'][$field]['placements']['bottom-left'])  echo' checked';echo' title="bottom-left" />
                                                        <input data-placement="bottom"       type="checkbox" name="settings[stamps]['.$field.'][placements][bottom]"       value="1"'; if ($settings['stamps'][$field]['placements']['bottom'])       echo' checked';echo' title="bottom" />
                                                        <input data-placement="bottom-right" type="checkbox" name="settings[stamps]['.$field.'][placements][bottom-right]" value="1"'; if ($settings['stamps'][$field]['placements']['bottom-right']) echo' checked'; echo' title="bottom-right" />
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="title">'.$sterms['stamp_size'].'</td>
                                            <td><input data-stamp="scale" type="text" name="settings[stamps]['.$field.'][scale]" value="'.$settings['stamps'][$field]['scale'].'" size="2" title="'.$sterms['stamp_percentage'].'" />%</td>

                                            <td class="title">'.$sterms['stamp-horiz-indent'].'<span class=""></span></td>
                                            <td><input data-stamp="indents_horizontal" type="text" name="settings[stamps]['.$field.'][indents][horizontal]" value="'.$settings['stamps'][$field]['indents']['horizontal'].'" size="2" title="'.$sterms['stamp-horiz-indent2'].'" />%</td>

                                            <td class="title">'.$sterms['stamp_vert-indent'].'</td>
                                            <td><input data-stamp="indents_vertical" type="text" name="settings[stamps]['.$field.'][indents][vertical]"   value="'.$settings['stamps'][$field]['indents']['vertical'].'"   size="2" title="'.$sterms['stamp_vert-indent2'].'" />%</td>
                                            <td class="title">
                                                <input type="hidden" name="settings[stamps]['.$field.'][stretch]" value="0" />
                                                '.$sterms['stamp_stretch'].' <input data-stamp="stretch" type="checkbox" name="settings[stamps]['.$field.'][stretch]" value="1"'; if ($settings['stamps'][$field]['stretch']) echo' checked'; echo' title="'.$sterms['stamp_stretch2'].'" />
                                            </td>

                                        </tr></table>
                                        </div>';
                                    }
                                }
                                else
                                    Blox::prompt(sprintf($sterms['stamp-no-file'], '<b>'.$typeParams['stamp'][0].'</b>'));
                            }
                        }
                    }
                    elseif ($typeName == 'block')  
                    {  
                        include'edit.tpl.block-button.inc'; 
                    }
                    elseif ($typeName == 'page') 
                    {   
                        if ($dat[$field]) {
                            echo'<a href="?page='.$dat[$field].'">'.$dat[$field];
                            $aa = Router::getPageInfoById($dat[$field]);                            
                            if (!(empty($aa['title']) || $aa['title'] == "&nbsp;" || $aa['title'] == "&nbsp;"))
                                echo' <span class="small">('.Text::truncate(Text::stripTags($aa['name'],'strip-quotes'), 40, 'plain').')</span>';                                            
                            echo'</a>';
                        }
                    }
                    elseif ($typeName == 'select')
                    {
                        $parentField = &$typeParams['parentfield'][0];
                        if (empty($readonly))
                            if ($parentField && empty($dat[$parentField]))
                                $readonly = ' readonly';
                        echo'
                        <select  name="dat['.$field.']" size="1"'.($readonly ? ' disabled' : '').$style.' onchange="submitBySelectListItem('.$field.')">';
                            if ($selects[$recId][$field])
                            {
                                echo'<option value="0">&middot;&middot;&middot;</option>';
                                foreach ($selects[$recId][$field] as $recId2 => $item) {
                                    echo'
                                    <option '; if ($recId2==$dat['selects'][$field]) echo' selected="selected" '; echo'value="'.$recId2.'">
                                        '.$item.'
                                    </option>';
                                }
                            }
                            #else
                                #echo'<option value="">'.$terms['no-select-list'].'</option>';
                            echo'
                        </select>';

                        if (empty($readonly))
                        {
                            echo' &nbsp; ';
                            if (Permission::ask('record', [$blockInfo['src-block-id'], $recId])['edit']) {//get
                                /*edit-rec*/  
                                if (in_array('edit', $_SESSION['Blox']['select-lists'][$recId][$field]['controls']) && $dat['selects'][$field]) 
                                    echo' &nbsp;<a class="button button-small" href="?edit&block='.$_SESSION['Blox']['select-lists'][$recId][$field]['select-list-block-id'].'&rec='.$dat['selects'][$field].'&direction=right'.$_SESSION['Blox']['select-lists'][$recId][$field]['defaults-queries'].$pagehrefQuery.'" title="'.$terms['edit-the-item'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-edit-rec.png" alt="&equiv;" /></a>';
                                /*newRec*/   
                                if (in_array('add', $_SESSION['Blox']['select-lists'][$recId][$field]['controls']) && isset($selects[$recId][$field])) 
                                    echo' &nbsp;<a class="button button-small" href="?edit&block='.$_SESSION['Blox']['select-lists'][$recId][$field]['select-list-block-id'].'&rec=new&direction=right'.$_SESSION['Blox']['select-lists'][$recId][$field]['defaults-queries'].'&edit-field='.$field.$pagehrefQuery.'" title="'.$terms['add-the-item'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-new-rec.png" alt="+" /></a>';
                            }

                            if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
                                /*deleteRec*/
                                if (in_array('delete', $_SESSION['Blox']['select-lists'][$recId][$field]['controls'])  && $dat['selects'][$field]) 
                                    echo' &nbsp;<a class="button button-small" href="?recs-delete&block='.$_SESSION['Blox']['select-lists'][$recId][$field]['select-list-block-id'].'&rec='.$recId.'&which='.$dat['selects'][$field].'&edit-field=$field&is-pick-values-field='.$pickValuesFields[$field].$pagehrefQuery.'" title="'.$terms['delete-the-item'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-delete-rec.png" alt="&times;" /></a>';
                            }
                            if (Blox::info('user','user-is-admin')) {
                                /*change*/   
                                echo' &nbsp;<a class="button button-small" href="?selectlist-block&edit-block='.$blockInfo['id'].'&edit-rec='.$recId.'&edit-tpl='.urlencode($blockInfo['tpl']).'&edit-field='.$field.'&edit-field-title='.urlencode($dataTitles[$field]); 
                                if ($parentField) 
                                    echo'&parent-field='.$parentField.'&parent-list-rec-id='.$dat['selects'][$parentField].'&parent-list-item='.$selects[$recId][$parentField][$dat[$parentField]].'&parent-list-block-id='.$_SESSION['Blox']['select-lists'][$recId][$field]['parent-list-block-id']; 
                                echo'&direction=right'.$pagehrefQuery.'" title="'.$terms['change-selectlist-block'].'"><img class="blox-edit-button-img" src="'.Blox::info('cms','url').'/assets/x-button-no-tpl.png" alt="?" /></a>';
                            }
                        }
                    }
                    #elseif ($typeName == 'timestamp' || $typeName == 'datetime'){
                    elseif ($typeName == 'datetime') {
                        echo'<input type="text" name="dat['.$field.']" value="'.$dat[$field].'" size="20"'.$readonly.$autofocus.$style.' title="" /><div class="explanation">'.$getHumanDateTimeFormat(Blox::info('site', 'date-time-formats', $typeName), $terms['date-time']).'</div>';
                    }
                    elseif ($typeName == 'date') {
                        echo'<input type="text" name="dat['.$field.']" value="'.$dat[$field].'" size="11"'.$readonly.$autofocus.$style.' title="" /><div class="explanation">'.$getHumanDateTimeFormat(Blox::info('site', 'date-time-formats', $typeName), $terms['date-time']).'</div>';
                    }
                    elseif ($typeName == 'time') {
                        echo'<input type="text" name="dat['.$field.']" value="'.$dat[$field].'" size="8"'.$readonly.$autofocus.$style.' title="" /><div class="explanation">'.$getHumanDateTimeFormat(Blox::info('site', 'date-time-formats', $typeName), $terms['date-time']).'</div>';
                    }
                    elseif ($typeName == 'year'){
                        echo'<input type="text" name="dat['.$field.']" value="'.$dat[$field].'" size="5"'.$readonly.$autofocus.$style.' title="" /><div class="explanation">'.$getHumanDateTimeFormat(Blox::info('site', 'date-time-formats', $typeName), $terms['date-time']).'</div>';
                    }
                    elseif ($typeName == 'set') {
                        # A list of items stored in the database
                        $setDataElements = explode(",", $dat[$field]);  # @todo Solve the register problem for "set". Here comes the original case
                        foreach ($typeParams['set'] as $i => $item) {
                            echo'<input type="hidden" name="dat['.$field.']['.$item.']" value="0" />';
                            echo'<label style="white-space: nowrap" title="'.$tooltips[$field][$item].'"><input type="checkbox" name="dat['.$field.']['.$item.']" value="1"'.$readonly.$style.' title="dat['.$field.']['.$item.']"'.(in_array($item, $setDataElements) ? ' checked' : '').' />'.$item.'</label>&nbsp; ';
                        }
                    }
                    elseif ($typeName == 'enum') {
                        $quotes = "'\" ";
                        echo'<select  name="dat['.$field.']" size="1"'.($readonly ? ' disabled' : '').$style.' title="'.$terms['stored-value'].' '.$dat[$field].' '.$tooltips[$field][$dat[$field]].'">';
                        foreach ($typeParams['enum'] as $item) {
                            $item = trim($item, $quotes);
                            echo'
                            <option title="'.$tooltips[$field][$item].'" ';
                                if ($item==$dat[$field]) 
                                    echo'selected="selected" ';
                                echo'value="'.$item.'">'.$item.'
                            </option>';
                        }
                    	echo'</select>';
                    }
                    elseif ($typeName == 'tinytext' || $typeName == 'text' || $typeName == 'mediumtext' || $typeName == 'longtext') {
                        if (
                            empty($readonly) && 
                            $textEditorDir && 
                            !$settings['disable-the-text-editor'][$field] && 
                            !$fields[$field]['no-text-editor'] &&
                            !isset($fields[$field]['strip-tags'])
                        ) {
                            $textEditorOutput = true;
                            include $textEditorDir.'/text-editor-output.php';
                        } else {
                            echo'<textarea name="dat['.$field.']" rows="2" cols="97"'.$readonly.$autofocus.$style.'>'.$dat[$field].'</textarea>';
                            $textareaFields[] = $field;
                        }
                        if (
                            empty($onlyPublic) && 
                            !$fields[$field]['no-text-editor'] && 
                            !isset($fields[$field]['strip-tags'])
                        ) { 
                            echo'<div class="explanation">';
                                if (Permission::ask('record', [$blockInfo['src-block-id']])['']['edit']) {//get
                                    echo'
                                    <input type="hidden"  name="settings[disable-the-text-editor]['.$field.']" value="0" title="settings[disable-the-text-editor]['.$field.']" />
                                    <input type="checkbox" name="settings[disable-the-text-editor]['.$field.']" value="1" id="textareaCheck'.$field.'"'; if ($settings['disable-the-text-editor'][$field]) echo'checked'; echo' /><span><label for="textareaCheck'.$field.'">'.$terms['disable-the-text-editor'].'</label></span>
                                    ';
                                    if (empty($outsideTextExists))
                                        echo Admin::tooltip('text-editing.htm', $terms['outside-text'], '#hiper-text');
                                }
                                
                                if ($fields[$field]['reconvert-url']) {
                                    echo'<div class="explanation" style="width:700px">';
                                        echo $terms['reconvert-url'];
                                    echo'</div>';
                                } elseif ($fields[$field]['convert-url']) {
                                    if (Blox::info('site', 'human-urls','on') && !Blox::info('site', 'human-urls','disabled'))
                                        echo'<p>'.$terms['convert-url']['text-hhref'].'</p>';
                                    else
                                        echo'<p>'.$terms['convert-url']['text-phref'].'</p>';
                                }
                                
                                if ($textEditorOutput)
                                    echo'<p>'.$sterms['line-break'].'</p>';
                                /** 
                                 * @todo Disable the visual editor
                                 *    <input onclick="disableTheTextEditor($field)" type="checkbox" name="settings[disable-the-text-editor]['.$field.']" value="1" id="textareaCheck'.$field.'" ...
                                 *    <script type="text/javascript">
                        	     *        function disableTheTextEditor(field)
                        	     *        {
                        	     *            var textareaCheck = document.getElementById('textareaCheck'+field);
                                 *            var textarea = document.getElementById('dat'+field);
                        	     *            if (textareaCheck.checked)
                                 *                Write here a script that switch off the visual editor
                                 *                {textarea.class ="";}
                        	     *            else
                                 *                Write here a script that switch on the visual editor
                                 *                {textarea.class ="";}
                        	     *        }
                                 *    </script>
                                 */
                            echo'</div>';
                        }
                    }
                    else { # varchar
                        echo'<input type="text" name="dat['.$field.']" value="'.htmlspecialchars($dat[$field]).'"'.$readonly.$autofocus.$style.' title="" />';

                        if ($fields[$field]['reconvert-url']) {
                            echo'<div class="explanation" style="width:700px">';
                                echo $terms['reconvert-url'];
                            echo'</div>';
                        } elseif ($fields[$field]['convert-url']) {
                            echo'<div class="explanation" style="width:700px">';
                                echo $terms['convert-url']['varchar'].'. ';
                                if (Blox::info('site','human-urls','on') && !Blox::info('site','human-urls','disabled'))
                                    echo $terms['convert-url']['varchar-hhref'];
                                else
                                    echo $terms['convert-url']['varchar-phref'];
                            echo'</div>';
                        }
                    }
                    # NULL
                    if ($nullableFields[$field] && !($fields[$field]['readonly'])) {
                        echo'
                        <input type="hidden" name="null-dat['.$field.']" value="0" />
                        <label for="null-dat'.$field.'">
                            <input type="checkbox" name="null-dat['.$field.']" value="1" id="null-dat'.$field.'"';
                                if (is_null($dat[$field])) 
                                    echo' checked';
                            echo' /><span class="gray">null <span class="small">('.$sterms['null'].')</span></span>
                        </label>';
                    }
                
                if (!$colspan) {
                    echo'</td><td valign="top">';
                    echo'<div class="explanation">'.$notes[$field].'</div>';    
                } else
                    echo'<span class="explanation">'.$notes[$field].'</span>';
                echo'
                </td>
                </tr>';
            }
        }

                
        if ($params['sort']['editable']) {
            echo'
            <tr>
            <td class="field">sort</td>
        	<td class="name" style="vertical-align:top">'.$terms['sort-num'].'</td>
            <td colspan="2" class="inputs"><input type="text" name="dat[sort]" value="'.$dat['sort'].'" style="width:70px; min-width:70px;" title="" />
                <span class="explanation">'.$terms['sort-num-explanation'].'</span>
            </td>
            </tr>';
        }


        # Additional caption after all
        if ($captions)
            foreach($captions as $f=>$caption)
                if ($f > $field)        
                    echo'<tr><td colspan="4"  class="caption">'.$captions[$f].'</td></tr>';
        echo'</table>';
    }
    echo $submitButtons;
echo'
</form>';


if ($toChunk) {
   /*
    * jQuery File Upload Plugin Basic Demo
    * https://github.com/blueimp/jQuery-File-Upload
    * Copyright 2013, Sebastian Tschan
    * https://blueimp.net
    * Licensed under the MIT license:
    * http://www.opensource.org/licenses/MIT
    */
    Blox::addToHead('//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css');#  Bootstrap styles
    Blox::addToHead('//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css');
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/css/style.css');#  Generic page styles
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/css/jquery.fileupload.css');#  CSS to style the file input field as button and adjust the Bootstrap progress bars
    /*
    http://stackoverflow.com/questions/5975335/jquery-fileuploadui-single-file-upload
    http://stackoverflow.com/questions/13304838/how-to-upload-a-file-only-once-with-blueimp-file-upload-plugin
    multiple 
        Uploading multiple files    http://stackoverflow.com/questions/29155697/uploading-multiple-files-from-multiple-input-fields-programatically-blueimp-jq
        independent file upload widgets with their own dropZone. https://github.com/blueimp/jquery-file-upload/wiki/multiple-file-upload-widgets-on-the-same-page
        http://stackoverflow.com/questions/26822869/jquery-file-upload-multiple-input-file-in-one-form
    */
    //Blox::addToHead('//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js');
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/js/vendor/jquery.ui.widget.js');# The jQuery UI widget factory, can be omitted if jQuery UI is already included
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/js/jquery.iframe-transport.js');#The Iframe Transport is required for browsers without support for XHR file uploads
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/js/jquery.fileupload.js');# The basic File Upload plugin
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/js/jquery.fileupload-process.js');
    Blox::addToHead(Blox::info('cms','url').'/vendor/blueimp/jQuery-File-Upload/js/jquery.fileupload-validate.js');# The File Upload validation plugin 
    ##Blox::addToHead('//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js');# Bootstrap JS is not required, but included for the responsive demo navigation    
}


# textarea auto resize
if ($textareaFields) {
    /**
     * @todo store width of textarea by: sessionStorage.setItem(textarea-width-'.$blockInfo['src-block-id'].'-'.$field.', width);
     * @todo Common $("textarea").textareaAutoSize();
     */
    Blox::addToFoot(Blox::info('cms','url').'/vendor/javierjulio/textarea-autosize/dist/jquery.textarea_autosize.min.js');
    $js ='<script>$(document).ready(function () {';
    foreach ($textareaFields as $field) {
        $js.='$(\'textarea[name="dat['.$field.']"]\').textareaAutoSize();';
    } 
    $js.='});</script>';
    Blox::addToFoot($js);
}

