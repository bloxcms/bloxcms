<?php
# TODO with Misc::paginate()
# Transition to the other parts
Blox::includeTerms($terms);
$request = Request::get($blockInfo['id']);
if ($request['part']['num-of-parts'] > 1) {
    echo'<br />
    <div class="smaller">';
         # prev
        if (empty($request['part']['prev']))
            echo' <span class="button">&nbsp; '.$terms['prev'].' &nbsp;</span>';
        else
            echo' <a class="button" href="?'.$script.'&block='.$blockInfo['id'].'&part='.$request['part']['prev'].$pagehrefQuery.'">&nbsp; '.$terms['prev'].' &nbsp;</a>';
        # by number
        foreach ($request['part']['parts'] as $p){
            if ($p == $request['part']['current']) 
                echo' <span class="button">'.$p.'</span>';
            else 
                echo' <a class="button" href="?'.$script.'&block='.$blockInfo['id'].'&part='.$p.$pagehrefQuery.'">'.$p.'</a>';}
        # next
        if (empty($request['part']['next']))
            echo' <span class="button">&nbsp; '.$terms['next'].' &nbsp;</span>';
        else
            echo' <a class="button" href="?'.$script.'&block='.$blockInfo['id'].'&part='.$request['part']['next'].$pagehrefQuery.'">&nbsp; '.$terms['next'].' &nbsp;</a>';
    echo'</div>';
}