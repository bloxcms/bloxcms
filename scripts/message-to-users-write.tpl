<?php 
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['bar-title'].'</div>
    <form action="?message-to-users-send'.$pagehrefQuery.'" method="post">
        <table>
        	<tr><td class="name">'.$terms['subject'].'</td><td><input type="text" name="subject" value="" /></td></tr>
            <tr><td class="name">'.$terms['message'].'</td><td><textarea style="width:500px; height:200px" name="message"></textarea></td></tr>
            <tr><td></td><td>'.$submitButtons.'</td></tr>
        </table>
    </form>
</div>';