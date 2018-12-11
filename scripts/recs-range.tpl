<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
$recId = Sql::sanitizeInteger($_GET['rec']);
echo'
<div class="blox-edit">
<table>
<tr>
<td>
    <a class="button" href="'.$backUrl.'" title=""><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>
    <div class="heading" style="margin-bottom:5px">'.$terms['heading2'].'</div>
    <div class="small">'.$terms['heading1'].': <b>'.$blockInfo['src-block-id'].'</b>';if ($blockInfo['id'] != $blockInfo['src-block-id']) echo' ('.$terms['delegated-from-block'].' '.$blockInfo['id'].')';echo'</div>
    <br />
    <table>
    <tr>
    <td>
        <table class="hor-separators">
            <form action="?recs-delete&block='.$blockInfo['id'].'&rec='.$recId.'&which=range'.$pagehrefQuery.'" method="post" enctype="multipart/form-data">
        	<tr>
                <td><b>'.$terms['field'].'</b></td>
                <td><b>'.$terms['name'].'</b></td>
                <td colspan="2"><b>'.$terms['value'].'</b></td>
                <td>&#160;</td>
            </tr>
            <tr>
                <td>&#160;</td><td>&#160;</td>
                <td>'.$terms['from'].'</td>
                <td>'.$terms['to'].'</td>
                <td>&#160;</td>
            </tr>';
            foreach ($editingFields as $field) {
                $tDetails = $typesDetails[$field];                    
                $typeName = $tDetails['name'];
                if (
                    $typeName == 'tinyblob' ||
                    $typeName == 'tinytext' ||
                    $typeName == 'longblob' ||
                    $typeName == 'longtext' ||
                    $typeName == 'mediumblob' ||
                    $typeName == 'mediumtext' ||
                    $typeName == 'blob' ||
                    $typeName == 'text'
                ) # not orderable data
                    $incomparable = true;
                else {
                    $incomparable = false;
                    if ($typeName == 'date' || $typeName == 'datetime')
                        echo'<input name="typeName['.$field.']" type="hidden" value="datetime" />';
                }
                echo'
                <tr>
                	<td class="field" align="center">'.$field.'.</td>
                	<td>'.$dataTitles[$field].'&#160;</td>';
                    # Fields for entering data
                    if ($incomparable) {
                        echo'<td colspan="2">&#160;</td>';
                    } else {
                        echo'
                        <td><input name="dat['.$field.'][from]" type="text" /></td>
                        <td><input name="dat['.$field.'][to]" type="text" /></td>';
                    }
                    # Names of data types
                    echo'
                    <td class="small">';//all types must be in lower case
                        if (
                            $typeName == 'bit' ||
                            $typeName == 'bool' ||
                            $typeName == 'tinyint(1)'
                        )   echo $terms['bit'];
                        elseif (
                            $typeName    == 'tinyint' ||
                            $typeName    == 'smallint' ||
                            $typeName    == 'mediumint' ||
                            $typeName    == 'int' ||
                            $typeName    == 'integer' ||
                            $typeName    == 'bigint' ||
                        	$typeName == 'block' ||
                        	$typeName == 'page'
                        )   echo $terms['integer'];
                        elseif (
                            $typeName    == 'float' ||
                            $typeName    == 'double' ||
                            $typeName    == 'real' ||
                            $typeName    == 'decimal' ||
                            $typeName    == 'dec' ||
                            $typeName    == 'numeric'
                        )   echo $terms['real'];
                        elseif (
                            $typeName    == 'char' ||
                            $typeName    == 'varchar' ||
                            $typeName == 'file'
                        )   echo $terms['chars'];
                        elseif ($typeName == 'time')
                            echo $terms['time'];
                        elseif ($typeName    == 'year')
                            echo $terms['year'];
                        elseif (
                            $typeName == 'timestamp' ||
                            $typeName == 'date' ||
                            $typeName == 'datetime'
                        )
                            echo $terms['datetime'];
                        elseif ($typeName == 'enum')
                            echo $terms['enum'];
                        elseif ($typeName  == 'set')
                            echo $terms['set'];
                        else
                            echo'&#160;';
                        echo'
                    </td>
                </tr>';
            }

        echo'
        </table>
    </td>
    <!--Samples-->
    <td style="padding: 72px 0px 0px 50px; vertical-align:top">
        <table class="hor-separators small gray">
            <tr><td><b>'.$terms['data-type'].'</b></td><td><b>'.$terms['sample'].'</b></td></tr>
            <tr><td>'.$terms['bit2'].'</td><td>0<br />1</td></tr>
            <tr><td>'.$terms['integer2'].'</td><td>123</td></tr>
            <tr><td>'.$terms['real2'].'</td><td>0,123</td></tr>
            <tr><td>'.$terms['chars2'].'</td><td>ABC</td></tr>
            <tr><td>'.$terms['time2'].'</td><td>23:59:59<br />235959</td></tr>
            <tr><td>'.$terms['year2'].'</td><td>1998</td></tr>
            <tr><td>'.$terms['datetime2'].'</td><td>2005-12-31<br />20051231<br />2005-12-31 23:59:59<br />20051231235959</td></tr>
        </table>
    </td>
    </tr>
    </table>
     '.$submitButtons.'
    </form>
</td>
</tr>
</table>
</div>';