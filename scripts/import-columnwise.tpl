<?php
/**
 * Insert beginning with the record...
 */
 
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
Query2::capture();
Query2::remove('import-columnwise');
Query2::remove('pagehref');
Query2::remove('rec');# usually "new"
$filtersQuery = Query2::build();
echo'
<div class="blox-edit">
<table><tr><td>
    <a class="button" href="'.$backUrl.'" title=""><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
    <div class="heading" style="margin-bottom:5px">
        <div id="loading" class="loading" style="visibility:hidden"></div>
        '.$terms['heading2'].''.Admin::tooltip('import-columnwise.htm', $terms['heading2']).'
    </div>
    <div class="small">
        '.$terms['heading1'].': <b>'.$blockInfo['src-block-id'].'</b>';
            if ($blockInfo['id'] != $blockInfo['src-block-id']) 
                echo' ('.$terms['delegated-from-block'].' '.$blockInfo['id'].')';
        echo'
        <br />'.$terms['tpl'].': <b>'.$blockInfo['tpl'].'</b>
        <br />'.$terms['start-rec'].' <b>'.$recId.'</b>'; if ($backward) echo'. <span style="color:red">'.$terms['backward'].'</span>'; echo'
    </div>
    <br />
    <form action="?update&'.$filtersQuery.'&insert-data-from-file-batches'.$pagehrefQuery.'" method="post" enctype="multipart/form-data"  onsubmit="loading.style.visibility = \'visible\';">
    <table>
        <tr>
        <td>
            <table class="hor-separators">
            	<tr class="small center middle">
                    <td>'.$terms['field'].'</td>
                    <td>'.$terms['name'].'</td>
                    <td>'.$terms['value'].'</td>
                </tr>';
                foreach ($typesDetails as $field => $tDetails) {
                    $typeName = $tDetails['name'];
                    echo'
                    <tr>
                	<td class="field" align="center">'.$field.'.</td>
                	<td>'.$dataTitles[$field].'&#160;</td>
                    <td>';
                    if ($typeName == 'block' || $typeName == 'page' || $typeName == 'select')
                        echo'&#160;';
                    else
                        echo'<input name="batch['.$field.']" type="file"  size="20" />';
                    echo'
                    </td>
                    </tr>';
                }
                echo'
            </table>
        </td>
        <td style="padding: 72px 0px 0px 50px; vertical-align:top">
        </td>
        </tr>
    </table>
    '.$submitButtons.'
    </form>
</td></tr></table>
</div>';