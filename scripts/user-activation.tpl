<?php
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['bar-title'].'</div>   
    <table>
    <tr>
    <td>
        <div>';           
            if ($userIsActivated)
                echo'<div class="alert green">'.$terms['you-are-activated'].'</div>';
            elseif ($error) {
                echo'<span class="red">'.$terms['code-is-wrong'].'</span>';
                foreach ($errors as $v)
                    echo'<span class="red">'.$v.'</span>';
            }
            echo'
        </div>
        '.$cancelButton.'
    </td>
    </tr>
    </table>
</div>';