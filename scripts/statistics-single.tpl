<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<table>
<tr>
<td>
    <a class="button" href="?statistics-total'.$pagehrefQuery.'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
    <div class="heading">'.$pageHeading.'</div>';
    if ($showByMonthsToggle) {
        ?>
        <script type="text/javascript">
            function page(obj)
            {
            	var uri = obj.value+'<?=$sess?>';
            	if( uri != '' ) {
                    $('body').addClass('blox-overlay-load-icon');
            		document.location.href = uri;
                }
            }
        </script>
        <?php
        echo'
        <input onClick="page(this);" type="checkbox" id="by-months" name="aa" value="?statistics-single&by-months='.(-1*$byMonths).$pagehrefQuery.'"'.(1==$byMonths ? ' checked' : '').'/>
        <label for="by-months">'.$terms['sum-by-months'].'</label>';
    }
    echo'
    <br /><br />
    <table class="chart">
    	<tr>
        	<td colspan="3" align="center" '.(1==$byMonths ? $terms['month'] : $terms['date']).'</td>
            <td align="center" colspan="2">'.$terms['num'].'</td>
            <td>'.$terms['event'].'</td>
        </tr>';
        $yearColor=1; 
        $monthColor=-1;
        foreach ($statDat as $row) {
            if ($row['is-new-year']) 
                $yearColor = $yearColor*-1;
            if ($row['is-new-month']) 
                $monthColor = $monthColor*-1;
            echo'
        	<tr>
                <td style="padding-right:0;'.($yearColor > 0 ? 'color:#888' : '').'">'.$row['year'].'</td>
                <td'.($monthColor > 0 ? ' style="color:#888"' : '').'>'.$row['month'].'</td>
                <td style="padding-left:0;'.($row['is-sunday'] ? 'color:red' : '').'">'.$row['day'].'</td>
            	<td align="right">&#160; &#160; '.$row['value'].'</td>
                <td align="left" style="border-left: 1px solid #a44; border-right: 1px dotted #a44; padding:0; vertical-align:middle">';
                    if ($maxValue) 
                        $width = round($row['value']/$maxValue*150); 
                    else 
                        $width=0; 
                    echo'
                    <div style="width:'.$width.'px; height:7px; background: #a44;"></div>
                </td>
                <td>&#160;'.$row['event'].'</td>
            </tr>';
        }
        echo'
    </table>
    <br /><br />
    '.$cancelButton.'
</td>
</tr>
</table>
</div>';