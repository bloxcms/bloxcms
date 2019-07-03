<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?statistics'.$pagehrefQuery.'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>
    <div class="heading">'.$pageHeading.'</div>
    <table>
    <tr>
    <td>
        <table class="chart">
        	<tr>
        	<td colspan="2">'.$statTableObjType.'</td>
            <td align="center" colspan="2">'.$terms['num'].'</td>
            <td>';if ($timeInterval != 'day') echo $terms['details'] ; echo'&nbsp;</td>
            </tr>';
            if ($statDat) {
                foreach ($statDat as $row) {
                    echo'
                	<tr>
                        <td align="left"><b>';
                            if ($row['title'])
                                echo $row['title'];
                            else
                                echo $row['obj'];
                            echo'&nbsp;';
                        echo'</b></td>
                        <td>'; if ($row['note']) echo' '.$row['note']; else echo'&nbsp;'; echo'</td>
                    	<td align="right">'.$row['sum'].'</td>
                        <td align="left" style="border-left: 1px solid #a44; border-right: 1px dotted #a44; padding:0; vertical-align:middle">';
                            $overloadCode = '';
                            if ($maxSum) {
                                if ($row['truncate']) {
                                     if ($row['sum'] >= $maxSum) {
                                         $row['sum'] = $maxSum;
                                         $overloadCode = '<span style="position:relative; top:-3px;color:#d4d0c8;font-weight:bold">/</span>';
                                     }
                                }
                                $width = round($row['sum']/$maxSum*150);
                            } else
                                $width=0;
                            echo'
                            <div style="width: '.$width.'px; background: #a44; height:9px; text-align:center">'.$overloadCode.'</div>
                        </td>
                        <td class="" align="center">'; 
                            if ($timeInterval != 'day') 
                                echo'<a class="button" href="?statistics-single&obj='.urlencode($row['obj']).'&add='.$row['note'].'&mark='.$row['mark'].$pagehrefQuery.'" title="'.$terms['by-time'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-right.png" alt="&gt;" /></a>';
                            echo'&nbsp;
                        </td>
                    </tr>';
                }
            }
            echo'
        	<tr>
            	<td colspan="2" align="right">'.$terms['total-sum'].':</td>
                <td align="right"><b>'.$totalSum.'</b></td>
                <td colspan="2">&nbsp;</td>
            </tr>
        </table>
        <br />
        '.$cancelButton.'
    </td>
    </tr>
    </table>
</div>';