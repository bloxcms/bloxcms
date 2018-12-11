<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?events'.$pagehrefQuery.'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a>
    <div class="heading">'.$terms['heading'].'</div>
    <table>
    <tr>
    <td>
      	<form action="?events-show'.$pagehrefQuery.'" method="post">
            <table class="hor-separators">
            	<tr>
                	<td>'.$terms['date'].'</td>
                    <td>'.$terms['description'].'</td>
                    <td>'.$terms['edit'].'</td>
                    <td class="red">'.$terms['delete'].'</td>
                </tr>';
                if ($events) {
                    foreach ($events as $event) {
                        echo'
                    	<tr>
                        	<td>'.$event['date'].'</td>
                        	<td>'.$event['description'].'</td>
                            <td><a class="button" href="?event-edit&id='.$event['id'].$pagehrefQuery.'" title="'.$terms['editing'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-edit-rec.png" alt="&equiv;" /></a></td>
                        	<td><input type="checkbox"  name="eventsToDel['.$event['id'].']" value="1" title="'.$terms['deleting'].'" /></td>
                        </tr>';
                    }
                }
                echo'
            </table>
            '.$submitButtons.'
        </form>
    </td>
    </tr>
    </table>
</div>';