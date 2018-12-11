<?php
/** 
 * External vars:
 *  $editingFields
 *  $typesDetails
 *  $dataTitles
 *  $terms
 */
     
Blox::includeTerms($terms);
$tdCounter = 0;
foreach ($editingFields as $field) {   
    $typeDetails = $typesDetails[$field];
    $typeName = $typeDetails['name'];
    $typeParams = $typeDetails['params'];    
    if (!($fields[$field]['secret'] || $fields[$field]['hidden'])) { 
        $tdCounter++;    
        $dt = '';
        $st = '';
        if ($dataTitles[$field]) {
            if ($typeName == 'bit' or $typeName == 'bool' or (($typeName == 'tinyint') && $typeParams['tinyint'][0] == 1))
                $st = ' style="width:35px"';        
            $dt = $dataTitles[$field];
            $dt = Str::getStringBeforeMark($dt, '. ') ?: $dt;
            $dataTitles[$field] = $dt;
        } elseif ($typeName == 'block') {
            $dt = $terms['block'];
        } elseif ($typeName == 'page') {
            $dt = $terms['page'];
        }
        echo'
        <td class="blox-vert-sep">&#160;</td>
        <td'.$st.'><div class="gray">'.$terms['field'].' '.$field.'</div>'.$dt;
            if ($typeName == 'timestamp')
                echo Admin::tooltip('',$terms['date-time-format']);
            elseif ($typeName == 'datetime')
                echo Admin::tooltip('',$terms['date-time-format']);
            elseif ($typeName == 'date')
                echo Admin::tooltip('',$terms['date-format']);
            elseif ($typeName == 'time')
                echo Admin::tooltip('',$terms['time-format']);
            elseif ($typeName == 'year')
                echo Admin::tooltip('',$terms['year-format']);
            //elseif ($typeName == 'set')
                //echo Admin::tooltip('',$terms['multiple-select']);
        echo'
        </td>';
    }
} 